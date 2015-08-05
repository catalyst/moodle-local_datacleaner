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
    const TASK = 'Scrambling user data';

    // Make all accounts
    // - Manual auth
    // - Non mnet
    // - Reset password
    // - Email address undeliverable
    // - Emailstop on
    // - Clear firstaccess, lastaccess, lastlogin, currentlogin, picture, description  and lastip
    // - Empty other contact details.

    const PASS = 'F4k3p3s5w0rD%';

    private static $fixedmods = array(
        'auth' => 'manual',
        'mnethostid' => 1,
        'password' => '',
        'email' => 'dev_null@localhost',
        'emailstop' => 1,
        'firstaccess' => 0,
        'lastaccess' => 0,
        'lastlogin' => 0,
        'currentlogin' => 0,
        'picture' => 0,
        'description' => '',
        'lastip' => '',
        'icq' => '',
        'skype' => '',
        'yahoo' => '',
        'aim' => '',
        'msn' => '',
        'phone1' => '',
        'phone2' => '',
        'idnumber' => '',
    );

    // Plus scramble
    // - usernames
    // - idnumbers
    // - name fields
    // - city, country and timezone.
    private static $scramble = array(
        'idnumbers' => array('idnumber'),
        'firstname fields' => array('firstname'),
        'surname fields' => array('lastname'),
        'department' => array('institution', 'department'),
        'address' => array('address', 'city', 'country', 'lang', 'calendartype', 'timezone')
    );

    private static $functions = array(
        'usernames' => 'username_substitution',
    );

    public function __construct() {
        self::$fixedmods['password'] = hash_internal_user_password(self::PASS);
    }

    private static function username_substitution($users = array()) {
        global $DB;

        list($sql, $params) = $DB->get_in_or_equal(array_keys($users));
        $DB->execute('UPDATE {user} SET username = CONCAT(\'user\', id) WHERE id ' . $sql, $params);
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

        if (isset($criteria['ignored'])) {
            list($newextrasql, $extraparams) = $DB->get_in_or_equal($criteria['ignored'], SQL_PARAMS_NAMED, 'userid_', false);
            $extrasql .= ' AND id ' . $newextrasql;
            $params = array_merge($params, $extraparams);
        }

        if (isset($criteria['deleted'])) {
            $extrasql .= ' AND deleted = :deleted ';
            $params['deleted'] = $criteria['deleted'];
        }

        return $DB->get_records_select_menu('user', 'id > 2 ' . $extrasql, $params, '', 'id, id');
    }

    /**
     * scramble the contents of a field.
     *
     * The algorithm is:
     * - Randomly pick up to 50 rows to use as the values for the resulting data (keep memory use lower)
     * - Get the values from those rows
     * - For each user being scrambled, assign one of the randomised values but never
     *   use the original value of the field.
     *
     * @param array $users  - The UIDS of users who will be modified and from whom data will be selected
     * @param array $fields - The names of user tables fields that will be changed. More than one means
     *                        values will be changed together  (ie keep the city, country and timezone
     *                        making sense together).
     */
    static public function randomise_fields($users = array(), $fields = array()) {
        global $DB;

        // Pick the rows to use.
        if (count($users) > 50) {
            $pickedusers = array_rand($users, 50);
        } else {
            $pickedusers = $users;
        }

        // Get data for picked users.
        $data = $DB->get_records_list('user', 'id', $pickedusers);
        $data = array_values($data);

        // We want to do this quickly, so we first figure out what values will be used for each
        // record, then do set_field_select for the records that will receive each value.
        // Thus, a maximum of 50 queries per field being updated.

        $mappings = array();

        foreach ($users as $uid => $user) {
            $mappings[rand(0, 49)][] = $uid;
        }

        // TODO: make this into a smaller number of queries that will work with either PGSql or MySQL.
        foreach ($mappings as $id => $uids) {

            if (empty($uids)) {
                continue;
            }

            list($sql, $params) = $DB->get_in_or_equal($uids);

            foreach ($fields as $field) {
                $DB->set_field_select('user', $field, $data[$id]->$field, 'id ' . $sql, $params);
            }
        }
    }
    /**
     * Do the hard work of cleaning up users.
     */
    static public function execute() {

        global $DB, $CFG;

        // Get the settings, handling the case where new ones (dev) haven't been set yet.
        $config = get_config('cleaner_users');

        $keepsiteadmins = isset($config->keepsiteadmins) ? $config->keepsiteadmins : true;
        $keepuids = trim(isset($config->keepuids) ? $config->keepuids : "");

        // Build the array of ids to keep.
        $keepuids = empty($keepuids) ? array() : explode(',', $keepuids);

        if ($keepsiteadmins) {
            $keepuids = array_merge($keepuids, explode(',', $CFG->siteadmins));
        }

        // Build the array of criteria.
        $criteria = array();

        if (!empty($keepuids)) {
            $criteria['ignored'] = $keepuids;
        }

        // Get the list of users on which to work.
        $users = self::get_users($criteria);
        $numusers = count($users);

        if (!$numusers) {
            echo "No users require data scrambling.\n";
            return;
        }

        echo "Scrambling the data of {$numusers} users.\n";

        // Scramble the eggs.
        $numsteps = count(self::$fixedmods) + count(self::$scramble);
        self::update_status(self::TASK, 0, $numsteps);
        $thisstep = 1;
        foreach (self::$scramble as $description => $setoffields) {
            self::randomise_fields($users, $setoffields);
            self::update_status(self::TASK, $thisstep, $numsteps);
            $thisstep++;
        }

        // Apply the fixed values.
        list($sql, $params) = $DB->get_in_or_equal(array_keys($users));
        foreach (self::$fixedmods as $field => $value) {
            $DB->set_field_select('user', $field, $value, 'id ' . $sql, $params);
            self::update_status(self::TASK, $thisstep, $numsteps);
            $thisstep++;
        }

        // Apply the functions.
        foreach (self::$functions as $field => $fnname) {
            self::$fnname($users);
        }
    }
}
