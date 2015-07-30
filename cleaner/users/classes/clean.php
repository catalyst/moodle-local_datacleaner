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
 * @package    cleaner_users
 * @copyright  2015 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_users;

defined('MOODLE_INTERNAL') || die();

class clean extends \local_datacleaner\clean {
    /**
     * Return the ID for an item.
     *
     * @param  object item The item from which to return the ID.
     * @return int    id   The item id.
     */
    protected static function get_item_id($item) {
        return $item->id;
    }

    /**
     * Delete a group of users
     *
     * Based on the Ducere migration code originally written by Dima.
     *
     * @param array $users User IDs to delete.
     */
    private function delete_users(array $users) {
        global $DB;

        if (empty($users)) {
            return;
        }

        foreach ($users as $user) {
            delete_user($user);
        }

        // Clean up Assignment stuff.
        $userids = array_map(self::get_item_id, $users);
        list($userinequal, $userparams) = $DB->get_in_or_equal($userids);

        $submissions = $DB->get_fieldset_select("assign_submission", "id", $userinequal, $userparams);
        if (!empty($submissions)) {
            // TODO: Actually delete the files.
            $DB->delete_records_list('assignsubmission_file', 'submission', $submissions);
            $DB->delete_records_list('assignsubmission_onlinetext', 'submission', $submissions);
        }

        $DB->delete_records_list('assign_submission', 'userid', $userids);

        $grades = $DB->get_fieldset_select("assign_grades", "userid", $userinequal, $userparams);
        if (!empty($grades)) {
            $DB->delete_records_list('assignfeedback_comments', 'grade', $grades);
            // TODO: Actually delete the files.
            $DB->delete_records_list('assignfeedback_file', 'grade', $grades);
            $DB->delete_records_list('assignfeedback_editpdf_annot', 'gradeid', $grades);
            $DB->delete_records_list('assignfeedback_editpdf_cmnt', 'gradeid', $grades);
        }
        $DB->delete_records_list('assign_grades', 'userid', $userids);
        $DB->delete_records_list('assign_user_flags', 'userid', $userids);
        $DB->delete_records_list('assign_user_mapping', 'userid', $userids);
        $DB->delete_records_list('assignfeedback_editpdf_quick', 'userid', $userids);
        // Clean up local messages.
        $DB->delete_records_list('local_messages_sent', 'userid', $userids);
        // Clean up leaderboard.
        $DB->delete_records_list('block_leaderboard_data', 'userid', $userids);
        $DB->delete_records_list('block_leaderboard_points', 'userid', $userids);
        // Finally clean up user table.
        $DB->delete_records_list('user', 'id', $userids);

        foreach ($users as $user) {
            mtrace(" Deleted user for ".fullname($user, true)." ($user->id)");
        }
    }

    /**
     * Do the hard work of cleaning up users.
     */
    static public function execute() {

        global $DB;

        $task = 'Removing old users';

        self::update_status($task, 0, 5);
        sleep(1);
        self::update_status($task, 1, 5);
        sleep(1);
        self::update_status($task, 2, 5);
        sleep(1);
        self::update_status($task, 3, 5);
        sleep(1);
        self::update_status($task, 4, 5);
        sleep(1);
        self::update_status($task, 5, 5);

    }
}

