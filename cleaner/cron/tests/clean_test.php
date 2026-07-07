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

use cleaner_cron\task\ran_marker_task;
use core\task\manager;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the cron cleaner executor.
 *
 * @package    cleaner_cron
 * @copyright  2026 Catalyst IT
 * @author     Jason den Dulk <jasondendulk@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(clean::class)]
final class clean_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        // The fixture task lives outside classes/, so it is not autoloaded.
        require_once(__DIR__ . '/fixtures/ran_marker_task.php');

        // Reset the shared static options to a known state (they persist between
        // tests because \local_datacleaner\clean::$options is static).
        new clean(['dryrun' => false, 'verbose' => false]);
        ran_marker_task::$timesrun = 0;
    }

    /**
     * Register the marker fixture task in task_scheduled and return its canonical class name.
     *
     * @return string
     */
    private function register_marker_task(): string {
        global $DB;

        $task = new ran_marker_task();
        $task->set_component('cleaner_cron');
        $record = manager::record_from_scheduled_task($task);
        $DB->insert_record('task_scheduled', $record);

        return manager::get_canonical_class_name($task);
    }

    /**
     * Seed a lock_db row directly (bypassing the lock factory).
     *
     * @param string $resourcekey
     */
    private function seed_lock(string $resourcekey): void {
        global $DB;
        $DB->insert_record('lock_db', (object)['resourcekey' => $resourcekey, 'expires' => 0, 'owner' => 'x']);
    }

    /**
     * clean_metadata() should scrub running metadata, empty the ad-hoc queue and
     * remove only the cleaner_ lock rows.
     */
    public function test_clean_metadata_scrubs_running_state(): void {
        global $DB;

        $classname = $this->register_marker_task();
        $DB->set_field('task_scheduled', 'timestarted', 123, ['classname' => $classname]);
        $DB->set_field('task_scheduled', 'hostname', 'oldhost', ['classname' => $classname]);
        $DB->set_field('task_scheduled', 'pid', 4242, ['classname' => $classname]);
        $DB->set_field('task_scheduled', 'faildelay', 3600, ['classname' => $classname]);

        $DB->insert_record('task_adhoc', (object)[
            'component' => 'cleaner_cron',
            'classname' => '\\core\\task\\adhoc_test_task',
            'nextruntime' => 100,
        ]);

        $this->seed_lock('cron_foo');
        $this->seed_lock('core_cron');

        clean::clean_metadata((object)['deletemetadata' => 1]);

        // Running metadata and fail delay cleared.
        $record = $DB->get_record('task_scheduled', ['classname' => $classname]);
        $this->assertNull($record->timestarted);
        $this->assertNull($record->hostname);
        $this->assertNull($record->pid);
        $this->assertEquals(0, (int)$record->faildelay);

        // Ad-hoc queue emptied.
        $this->assertEquals(0, $DB->count_records('task_adhoc'));

        // Only the cron_foo lock row is removed.
        $this->assertFalse($DB->record_exists('lock_db', ['resourcekey' => 'cron_foo']));
        $this->assertTrue($DB->record_exists('lock_db', ['resourcekey' => 'core_cron']));
    }

    /**
     * clean_metadata() should be a no-op when deletemetadata is not set.
     */
    public function test_clean_metadata_does_nothing_when_disabled(): void {
        global $DB;

        $classname = $this->register_marker_task();
        $DB->set_field('task_scheduled', 'timestarted', 123, ['classname' => $classname]);
        $DB->set_field('task_scheduled', 'faildelay', 3600, ['classname' => $classname]);
        $DB->insert_record('task_adhoc', (object)[
            'component' => 'cleaner_cron',
            'classname' => '\\core\\task\\adhoc_test_task',
            'nextruntime' => 100,
        ]);
        $this->seed_lock('cron_foo');

        clean::clean_metadata((object)['deletemetadata' => 0]);

        $record = $DB->get_record('task_scheduled', ['classname' => $classname]);
        $this->assertEquals(123, (int)$record->timestarted);
        $this->assertEquals(3600, (int)$record->faildelay);
        $this->assertEquals(1, $DB->count_records('task_adhoc'));
        $this->assertTrue($DB->record_exists('lock_db', ['resourcekey' => 'cron_foo']));
    }

    /**
     * run_tasks() with TASKACTION_NONE should not touch or run the selected tasks.
     */
    public function test_run_tasks_none_leaves_tasks_untouched(): void {
        global $DB;

        $classname = $this->register_marker_task();
        $DB->set_field('task_scheduled', 'nextruntime', 555, ['classname' => $classname]);

        clean::run_tasks((object)[
            'taskaction' => clean::TASKACTION_NONE,
            'scheduledtasks' => $classname,
        ]);

        $record = $DB->get_record('task_scheduled', ['classname' => $classname]);
        $this->assertEquals(555, (int)$record->nextruntime);
        $this->assertEquals(0, ran_marker_task::$timesrun);
    }

    /**
     * run_tasks() with an empty selection should do nothing, even for a run action.
     */
    public function test_run_tasks_empty_selection_does_nothing(): void {
        global $DB;

        $classname = $this->register_marker_task();
        $DB->set_field('task_scheduled', 'nextruntime', 555, ['classname' => $classname]);

        clean::run_tasks((object)[
            'taskaction' => clean::TASKACTION_NEXTCRON,
            'scheduledtasks' => '',
        ]);

        $record = $DB->get_record('task_scheduled', ['classname' => $classname]);
        $this->assertEquals(555, (int)$record->nextruntime);
    }

    /**
     * run_tasks() with TASKACTION_NEXTCRON should reset the next run time to now
     * without executing the task.
     */
    public function test_run_tasks_nextcron_resets_nextruntime_to_now(): void {
        global $DB;

        $this->mock_clock_with_frozen(1000000);

        $classname = $this->register_marker_task();
        $DB->set_field('task_scheduled', 'nextruntime', 555, ['classname' => $classname]);

        clean::run_tasks((object)[
            'taskaction' => clean::TASKACTION_NEXTCRON,
            'scheduledtasks' => $classname,
        ]);

        $record = $DB->get_record('task_scheduled', ['classname' => $classname]);
        $this->assertEquals(1000000, (int)$record->nextruntime);
        $this->assertEquals(0, ran_marker_task::$timesrun);
    }

    /**
     * run_tasks() with TASKACTION_IMMEDIATE should execute the selected task.
     */
    public function test_run_tasks_immediate_runs_selected_task(): void {
        global $CFG;

        $this->preventResetByRollback();
        $CFG->task_logtostdout = true;
        \core\cron::reset_user_cache();

        $classname = $this->register_marker_task();

        ob_start();
        clean::run_tasks((object)[
            'taskaction' => clean::TASKACTION_IMMEDIATE,
            'scheduledtasks' => $classname,
        ]);
        ob_get_clean();

        $this->assertEquals(1, ran_marker_task::$timesrun);
    }

    /**
     * run_tasks() must not run anything in dry-run mode.
     */
    public function test_run_tasks_immediate_respects_dryrun(): void {
        // Switch the shared options into dry-run mode.
        new clean(['dryrun' => true]);

        $classname = $this->register_marker_task();

        clean::run_tasks((object)[
            'taskaction' => clean::TASKACTION_IMMEDIATE,
            'scheduledtasks' => $classname,
        ]);

        $this->assertEquals(0, ran_marker_task::$timesrun);
    }

    /**
     * run_tasks() should silently skip class names that do not resolve to a task.
     */
    public function test_run_tasks_skips_unknown_classnames(): void {
        clean::run_tasks((object)[
            'taskaction' => clean::TASKACTION_IMMEDIATE,
            'scheduledtasks' => '\\cleaner_cron\\task\\does_not_exist',
        ]);

        $this->assertEquals(0, ran_marker_task::$timesrun);
    }

    /**
     * run_scheduled_task_with_locking() should run the task through the cron
     * machinery and release the lock afterwards.
     */
    public function test_run_scheduled_task_with_locking_runs_and_releases(): void {
        global $CFG;

        $this->preventResetByRollback();
        $CFG->task_logtostdout = true;
        \core\cron::reset_user_cache();

        $classname = $this->register_marker_task();
        $task = manager::get_scheduled_task($classname);

        ob_start();
        $result = clean::run_scheduled_task_with_locking($task);
        ob_get_clean();

        $this->assertTrue($result);
        $this->assertEquals(1, ran_marker_task::$timesrun);

        // The task lock must have been released: we can re-acquire it immediately.
        $factory = \core\lock\lock_config::get_lock_factory('cron');
        $lock = $factory->get_lock(trim($classname, '\\'), 0);
        $this->assertNotFalse($lock);
        $lock->release();
    }

    /**
     * execute() should clean the metadata, reset next run times, and then apply
     * the configured task action (which must win over the blanket reset).
     */
    public function test_execute_end_to_end(): void {
        global $DB, $CFG;

        $this->preventResetByRollback();
        $CFG->task_logtostdout = true;
        \core\cron::reset_user_cache();

        $this->mock_clock_with_frozen(1000000);

        $classname = $this->register_marker_task();
        set_config('deletemetadata', 1, 'cleaner_cron');
        set_config('taskaction', clean::TASKACTION_NEXTCRON, 'cleaner_cron');
        set_config('scheduledtasks', $classname, 'cleaner_cron');

        // Seed leftover running/queued state from a "cloned" database.
        $DB->set_field('task_scheduled', 'timestarted', 123, ['classname' => $classname]);
        $DB->set_field('task_scheduled', 'faildelay', 3600, ['classname' => $classname]);
        $DB->insert_record('task_adhoc', (object)[
            'component' => 'cleaner_cron',
            'classname' => '\\core\\task\\adhoc_test_task',
            'nextruntime' => 100,
        ]);
        $this->seed_lock('cron_foo');

        ob_start();
        clean::execute();
        ob_get_clean();

        $record = $DB->get_record('task_scheduled', ['classname' => $classname]);

        // Metadata scrubbed by clean_metadata().
        $this->assertNull($record->timestarted);
        $this->assertEquals(0, (int)$record->faildelay);
        $this->assertEquals(0, $DB->count_records('task_adhoc'));
        $this->assertFalse($DB->record_exists('lock_db', ['resourcekey' => 'cron_foo']));

        // The NEXTCRON action wins over the blanket next-run-time reset.
        $this->assertEquals(1000000, (int)$record->nextruntime);

        // NEXTCRON must not execute the task.
        $this->assertEquals(0, ran_marker_task::$timesrun);
    }
}
