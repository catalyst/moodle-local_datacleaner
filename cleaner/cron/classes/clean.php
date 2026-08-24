<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace cleaner_cron;

/**
 * Main executor class.
 *
 * @package    cleaner_cron
 * @copyright  2026 Catalyst IT
 * @author     Jason den Dulk <jasondendulk@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clean extends \local_datacleaner\clean {
    /** @var string Do nothing with the selected tasks. */
    const TASKACTION_NONE = 'none';

    /** @var string Run the selected tasks immediately. */
    const TASKACTION_IMMEDIATE = 'immediate';

    /** @var string Run the selected tasks on the next cron. */
    const TASKACTION_NEXTCRON = 'nextcron';

    /**
     * Execute the cleaning process.
     *
     * Removes every trace of running and queued tasks that was carried over
     * from the source environment. This deliberately does NOT use
     * \core\task\manager::cleanup_metadata(): that function is a conservative
     * orphan-reaper for live sites. It skips tasks started within the last hour
     * and only acts on tasks whose lock it can acquire, so on a freshly cloned
     * database it leaves behind exactly the running-task state we want gone.
     */
    public static function execute() {
        $config = get_config('cleaner_cron');
        $dryrun = (bool) self::$options['dryrun'];
        $verbose = (bool) self::$options['verbose'];

        self::clean_metadata($config);

        // Recompute next run times so the scheduled tasks we just cleared resume
        // on their normal schedule, instead of all firing at once from the stale
        // (past) nextruntime values inherited from the source database.
        if (!empty($config->rescheduletasks)) {
            if ($verbose) {
                mtrace("Rescheduling tasks");
            }

            if (!$dryrun) {
                foreach (\core\task\manager::get_all_scheduled_tasks() as $task) {
                    // TODO If backporting, replace set_scheduled_task_nextruntime with clear_fail_delay.
                    \core\task\manager::set_scheduled_task_nextruntime($task, $task->get_next_scheduled_time());
                }
            }
        }

        self::run_tasks($config);
    }

    /**
     * Clean metadata from scheduled tasks.
     *
     * @param object $config
     */
    public static function clean_metadata(object $config) {
        if (empty($config->deletemetadata)) {
            return;
        }

        self::debug('Removing all traces of running and queued tasks.');

        // Unconditionally scrub running-task metadata and fail delays from
        // scheduled tasks. No one-hour guard and no per-task lock dependency.
        self::execute_sql('
            UPDATE {task_scheduled}
               SET timestarted = NULL,
                   hostname = NULL,
                   pid = NULL,
                   faildelay = 0
        ');

        // Drop the entire ad-hoc queue: running, queued and failed alike.
        self::execute_sql("DELETE FROM {task_adhoc}");

        // Purge lock rows carried over from the source environment so a stale
        // lock can never block a task from starting (or block the reaper) here.
        // Harmless no-op for sites using a non-DB lock factory.
        self::execute_sql("DELETE FROM {lock_db} where resourcekey like 'cron_%'");
    }

    /**
     * Run the scheduled tasks that are selected in the configuration.
     *
     * @param object $config
     */
    public static function run_tasks(object $config) {
        $dryrun = (bool) self::$options['dryrun'];
        $verbose = (bool) self::$options['verbose'];
        $time = \core\di::get(\core\clock::class)->time();

        // Apply the configured action to the selected scheduled tasks. This runs
        // after the reset above so a "run on next cron" choice is not overwritten.
        $taskaction = $config->taskaction ?? self::TASKACTION_NONE;
        $selected = array_filter(explode(',', $config->scheduledtasks ?? ''));

        if ($taskaction === self::TASKACTION_NONE || empty($selected)) {
            return;
        }

        foreach ($selected as $classname) {
            $task = \core\task\manager::get_scheduled_task($classname);
            if (!$task) {
                continue;
            }

            if ($taskaction === self::TASKACTION_IMMEDIATE) {
                if ($verbose) {
                    mtrace("Running scheduled task immediately: {$classname}");
                }
                if (!$dryrun) {
                    self::run_scheduled_task_with_locking($task);
                }
            } else if ($taskaction === self::TASKACTION_NEXTCRON) {
                if ($verbose) {
                    mtrace("Resetting next run time to now: {$classname}");
                }
                if (!$dryrun) {
                    \core\task\manager::set_scheduled_task_nextruntime($task, $time);
                }
            }
        }
    }

    /**
     * Run a scheduled task through the full cron machinery.
     *
     * This is an alternative to calling $task->execute() directly (as the
     * TASKACTION_IMMEDIATE branch of execute() currently does). Unlike the bare
     * call, this acquires the task lock, captures the task's log output, and
     * records success or failure — including setting the fail delay on failure —
     * exactly as a normal cron run would, by delegating to
     * \core\cron::run_inner_scheduled_task().
     *
     * @param \core\task\scheduled_task $task The task to run.
     * @return bool True if the task ran, false if its lock could not be acquired.
     */
    public static function run_scheduled_task_with_locking(\core\task\scheduled_task $task): bool {
        $cronlockfactory = \core\lock\lock_config::get_lock_factory('cron');

        // Use the same resource key core uses (DB classname, no leading slash)
        // so we contend on the same per-task lock as a real cron run.
        $classname = trim(\core\task\manager::get_canonical_class_name($task), '\\');

        if (!$lock = $cronlockfactory->get_lock($classname, 10)) {
            self::debug("Could not obtain lock to run scheduled task: {$classname}");
            return false;
        }

        // Serialise against the global cron lock exactly as
        // \core\task\manager::get_next_scheduled_task() does: acquire it briefly
        // then release it before running.
        if (!$cronlock = $cronlockfactory->get_lock('core_cron', 10)) {
            $lock->release();
            throw new \moodle_exception('locktimeout');
        }
        $cronlock->release();

        $task->set_lock($lock);

        // The function run_inner_scheduled_task() records the start, starts/ finalises logging,
        // marks the task complete or failed (applying the fail delay on failure),
        // and releases the task lock itself.
        \core\cron::run_inner_scheduled_task($task);

        return true;
    }
}
