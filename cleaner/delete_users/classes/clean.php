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

/**
 * @package    cleaner_delete_users
 * @copyright  2015 Catalyst IT
 * @author     Nigel Cunningham
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_delete_users;

defined('MOODLE_INTERNAL') || die();

class clean extends \local_datacleaner\clean {
    const TASK = 'Removing old users';
    private static $needs_cascade_delete = true;

    /**
     * Calculate the number of steps that will be displayed
     */
    protected static function num_steps($numusers) {
        $steps = max(intval($numusers / 20), 5); /* Consider 14 => 5 */
        $interval = intval($numusers / $steps); /* 2 */
        if (!$interval) {
            $interval = 1;
        }
        $steps = intval($numusers / $interval); /* 7 - the actual number of steps that will be shown */

        return array($interval, $steps);
    }

    /**
     * Undelete a group of users
     *
     * There's an undelete_user function in Totara, but it only does one user at a time and
     * fires events that we don't care about.
     *
     * @param array $users Users who need to have their delete flag reset.
     */
    protected static function undelete_users(array $users) {
        global $DB;

        if (empty($users)) {
            return;
        }

        foreach ($users as $chunk) {
            $DB->set_field_select('user', 'deleted', 0, 'id ' . $chunk['sql'], $chunk['params']);
        }
    }

    /**
     * Delete a group of users
     *
     * Based on the Ducere migration code originally written by Dima.
     *
     * @param array $users User IDs to delete.
     */
    private static function delete_users(array $users) {
        global $DB;

        // Clean up Assignment stuff.
        foreach ($users as $chunk) {
            $userinequal = 'userid ' . $chunk['sql'];
            $userparams = $chunk['params'];

            $submissions = $DB->get_fieldset_select("assign_submission", "id", $userinequal, $userparams);
            self::next_step();

            if (!empty($submissions)) {
                // TODO: Actually delete the files.
                static::delete_records_list_chunked('assignsubmission_file', 'submission', $submissions);
                static::delete_records_list_chunked('assignsubmission_onlinetext', 'submission', $submissions);
            }

            self::next_step();

            $DB->delete_records_select('assign_submission', 'userid ' . $chunk['sql'], $chunk['params']);

            self::next_step();

            $grades = $DB->get_fieldset_select("assign_grades", "id", $userinequal, $userparams);
            if (!empty($grades)) {
                static::delete_records_list_chunked('assignfeedback_comments', 'grade', $grades);
                // TODO: Actually delete the files.
                static::delete_records_list_chunked('assignfeedback_file', 'grade', $grades);
                static::delete_records_list_chunked('assignfeedback_editpdf_annot', 'gradeid', $grades);
                static::delete_records_list_chunked('assignfeedback_editpdf_cmnt', 'gradeid', $grades);
            }

            self::next_step();

            $DB->delete_records_select('assign_grades', 'userid ' . $chunk['sql'], $chunk['params']);

            self::next_step();

            $DB->delete_records_select('assign_user_flags', 'userid ' . $chunk['sql'], $chunk['params']);

            self::next_step();

            $DB->delete_records_select('assign_user_mapping', 'userid ' . $chunk['sql'], $chunk['params']);

            self::next_step();

            $DB->delete_records_select('assignfeedback_editpdf_quick', 'userid ' . $chunk['sql'], $chunk['params']);

            self::next_step();

            // Clean up other tables that might be around and need it.
            $dbman = $DB->get_manager();

            foreach (array('userid' => array('local_messages_sent', 'block_leaderboard_data', 'block_leaderboard_points',
                            'assignment_submissions', 'block_totara_stats', 'config_log', 'course_completion_crit_compl',
                            'course_completions', 'course_modules_completion', 'facetoface_signups',
                            'grade_grades', 'grade_grades_history', 'log', 'logstore_standard_log', 'message_contacts',
                            'my_pages', 'post', 'prog_completion', 'prog_pos_assignment', 'prog_user_assignment',
                            'report_builder_saved', 'role_assignments', 'scorm_scoes_track', 'sessions', 'stats_user_daily',
                            'stats_user_monthly', 'stats_user_weekly'
                            ),
                        'useridfrom' => array('message', 'message_read'),
                        'useridto' => array('message', 'message_read')) as $field => $tables) {
                foreach ($tables as $table) {
                    if ($dbman->table_exists($table)) {
                        static::delete_records_list_chunked($table, $field, $chunk['params']);
                    }
                    self::next_step();
                }
            }
        }

        // This transaction is purely for speed, hence the committing in the middle of the loop.
        $transaction = $DB->start_delegated_transaction();

        $index = 0;
        $numusers = self::get_num_users();
        list($interval, $steps) = self::num_steps($numusers);

        foreach ($users as $chunk) {
            $recs = $DB->get_records_select('user', 'id ' . $chunk['sql'], $chunk['params']);
            foreach ($recs as $user) {
                delete_user($user);

                $index++;
                if (!($index % $interval)) {
                    $transaction->allow_commit();
                    $transaction = $DB->start_delegated_transaction();

                    self::next_step();
                }
            }
        }

        $transaction->allow_commit();
        self::next_step();

        // Finally clean up user table.
        foreach ($users as $chunk) {
            $DB->delete_records_select('user', 'id ' . $chunk['sql'], $chunk['params']);
            self::next_step();
        }
    }

    /**
     * Do the hard work of cleaning up users.
     */
    static public function execute() {
        // Get the settings, handling the case where new ones (dev) haven't been set yet.
        $config = get_config('cleaner_delete_users');

        $criteria = self::get_criteria($config);

        // Any users need undeleting before we properly delete them?
        $criteria['deleted'] = true;
        $users = self::get_users($criteria);

        self::undelete_users($users);

        unset($criteria['deleted']);

        // Get on with the real work!
        $users = self::get_users($criteria);
        $numusers = self::get_num_users();

        if ($numusers) {
            list($interval, $steps) = self::num_steps($numusers);
            self::new_task((count($users) * 39) + $steps + 1 + count($users));

            self::delete_users($users);
        }

        echo 'Deleted ' . $numusers . " users.\n";
    }
}
