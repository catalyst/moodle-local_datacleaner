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

        $userids = array_keys($users);
        list($sql, $params) = $DB->get_in_or_equal($userids);
        $DB->set_field_select('user', 'deleted', 0, 'id ' . $sql, $params);
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

        if (empty($users)) {
            return;
        }

        // Clean up Assignment stuff.
        $userids = array_keys($users);
        list($userinequal, $userparams) = $DB->get_in_or_equal($userids);
        $userinequal = 'userid ' . $userinequal;

        $submissions = $DB->get_fieldset_select("assign_submission", "id", $userinequal, $userparams);
        if (!empty($submissions)) {
            // TODO: Actually delete the files.
            $DB->delete_records_list('assignsubmission_file', 'submission', $submissions);
            $DB->delete_records_list('assignsubmission_onlinetext', 'submission', $submissions);
        }

        $DB->delete_records_list('assign_submission', 'userid', $userids);

        $grades = $DB->get_fieldset_select("assign_grades", "id", $userinequal, $userparams);
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

        // Clean up other tables that might be around and need it.
        $dbman = $DB->get_manager();

        foreach (array('local_messages_sent', 'block_leaderboard_data', 'block_leaderboard_points') as $table) {
            if ($dbman->table_exists($table)) {
                $DB->delete_records_list($table, 'userid', $userids);
            }
        }

        // This transaction is purely for speed, hence the committing in the middle of the loop.
        $transaction = $DB->start_delegated_transaction();

        $index = 0;
        $numusers = count($users);
        $steps = max($numusers / 20, 5);
        $interval = $numusers / $steps;

        foreach ($users as $user) {
            delete_user($user);

            $index ++;
            if (!($index % $interval)) {
                self::update_status(self::TASK, $index, $numusers);
            }
        }

        $transaction->allow_commit();

        // Finally clean up user table.
        $DB->delete_records_list('user', 'id', $userids);

        foreach ($users as $user) {
            mtrace(" Deleted user for ".fullname($user, true)." ($user->id)");
        }
    }

    /**
     * Get an array of user objects meeting the criteria provided
     *
     * @param  array $criteria An array of criteria to apply.
     * @return array $result   The array of matching user objects.
     */
    private static function get_users($criteria = array()) {
        global $DB;

        $extrasql = '';
        $params = array();

        if (isset($criteria['timestamp'])) {
            $extrasql = ' AND lastaccess < :timestamp ';
            $params['timestamp'] = $criteria['timestamp'];
        }

        if (isset($criteria['ignored'])) {
            list($newextrasql, $extraparams) = $DB->get_in_or_equal($criteria['ignored'], SQL_PARAMS_NAMED, 'userid_', false);
            $extrasql .= ' AND id ' . $newextrasql;
            $params = array_merge($params, $extraparams);
        }

        if (isset($criteria['deleted'])) {
            $extrasql .= ' AND deleted = :deleted ';
            $params['deleted'] = $criteria['deleted'];
        }

        return $DB->get_records_select('user', 'id > 2 ' . $extrasql, $params);
    }

    /**
     * Do the hard work of cleaning up users.
     */
    static public function execute() {

        global $DB, $CFG;

        // Get the settings, handling the case where new ones (dev) haven't been set yet.
        $config = get_config('cleaner_delete_users');

        $interval = isset($config->minimumage) ? $config->minimumage : 365;
        $keepsiteadmins = isset($config->keepsiteadmins) ? $config->keepsiteadmins : true;
        $keepuids = trim(isset($config->keepuids) ? $config->keepuids : "");

        // Build the array of ids to keep.
        $keepuids = empty($keepuids) ? array() : explode(',', $keepuids);

        if ($keepsiteadmins) {
            $keepuids = array_merge($keepuids, explode(',', $CFG->siteadmins));
        }

        // Build the array of criteria.
        $criteria = array();
        $criteria['timestamp'] = time() - ($interval * 24 * 60 * 60);

        if (!empty($keepuids)) {
            $criteria['ignored'] = $keepuids;
        }

        // Any users need undeleting before we properly delete them?
        $criteria['deleted'] = true;
        $users = self::get_users($criteria);

        self::undelete_users($users);

        unset($criteria['deleted']);

        // Get on with the real work!
        $users = self::get_users($criteria);
        $numusers = count($users);

        if ($numusers) {
            self::update_status(self::TASK, 0, $numusers);

            self::delete_users($users);

            self::update_status(self::TASK, $numusers, $numusers);
        }

        echo 'Deleted ' . count($users) . " users.\n";
    }
}
