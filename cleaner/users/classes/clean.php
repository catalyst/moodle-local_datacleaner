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
    /** All users will have this password after cleaning. */
    const PASSWORD_AFTER_CLEANING = 'F4k3p3s5w0rD%';

    /** All usernames will use this prefix followed by their id number. */
    const USERNAME_PREFIX = 'user_';

    const TASK = 'Scrambling user data';

    /**
     * A SQL string with the comma-separated IDs of the users to update.
     *
     * @var string
     */
    protected static $updateuserssql;

    private static $scramble = [
        'firstname fields'  => ['firstname', 'firstnamephonetic', 'alternatename'],
        'middlename fields' => ['middlename'],
        'surname fields'    => ['lastname', 'lastnamephonetic'],
        'department'        => ['institution', 'department'],
        'address'           => ['address', 'city', 'country', 'lang', 'calendartype', 'timezone'],
    ];

    public static function execute() {
        self::$updateuserssql = self::create_update_users_sql();
        self::set_fixed_fields();
        self::replace_usernames();
        // self::scramble_fields();
    }

    /**
     * Do the hard work of cleaning up users.
     */
    static public function execute_old() {
        global $DB;

        // Get the settings, handling the case where new ones (dev) haven't been set yet.
        $config = get_config('cleaner_users');
        $numusers = self::get_user_count($config);

        if (!$numusers) {
            echo "No users require data scrambling.\n";
            return;
        }

        if (self::$options['dryrun']) {
            echo "Would scramble the data of {$numusers} users.\n";
            return;
        }

        echo "Scrambling the data of {$numusers} users.\n";

        // Scramble the eggs.
        $stepsperuser = count(self::$scramble) + count(self::$fixedmods) + count(self::$functions);
        $numsteps = $numusers * $stepsperuser;

        // Set up the prime numbers.
        $thisnum = intval(sqrt($numusers));
        $newscramble = [];

        foreach (self::$scramble as $description => $setoffields) {
            $thisnum = self::next_prime($thisnum);
            $newscramble[$thisnum] = $setoffields;
        }
        self::$scramble = $newscramble;

        self::new_task($numsteps);
        $users = self::get_user_chunk($config);
        $offset = 0;
        while (!empty($users)) {
            list($inequalsql, $params) = $DB->get_in_or_equal($users);

            foreach (self::$scramble as $prime => $setoffields) {
                self::randomise_fields('user', $setoffields, $prime, $inequalsql, $params);
                self::next_step(count($params));
            }

            // Apply the fixed values. One step for what remains because this is fast.
            foreach (self::$fixedmods as $field => $value) {
                $DB->set_field_select('user', $field, $value, 'id '.$inequalsql, $params);
                self::next_step(count($params));
            }

            // Apply the functions.
            foreach (self::$functions as $field => $fnname) {
                self::$fnname($users, $inequalsql, $params);
                self::next_step(count($params));
            }

            // Get the next batch of users.
            $offset += count($users);
            $users = self::get_user_chunk($config, $offset);
        }
    }

    /**
     * Scramble the contents of a set of fields.
     *
     * The algorithm is:
     * - Take the number of users and get the next largest prime after the sqrt of that number or the last one used.
     * - This primes are used to control the cycle frequency of the field(s)
     * - Copy the required number of unique values into a temporary table. If there are not enough
     *   unique values, replace the prime number with the number we have.
     * - Use SQL to replace existing values with the mixed up data from the tables.
     *
     * So for 10,000 users with 2 fields - firstname and lastname, the process is:
     * - sqrt(10,000) = 100. Next 2 primes are 101 and 103. 101 will be used on the first call to this function, 103 on the second.
     * - Copy first 101 unique firstnames to 1 temporary table and first 103 unique lastnames to another
     * - Use a single SQL statement to update the original fields in a cycle.
     *
     * @param string $tablename  The name of the user table.
     * @param array  $fields     The names of user tables fields that will be changed. More than one means
     *                           values will be changed together  (ie keep the city, country and timezone
     *                           making sense together).
     * @param int    $prime      The prime number to use for this set of fields.
     * @param string $inequalsql SQL where fragment for the batch.
     * @param array  $ineqparam  The array of parameters for $inequalsql
     * @internal param array $users The UIDS of users who will be modified and from whom data will be selected
     */
    static public function randomise_fields($tablename, $fields, $prime, $inequalsql, $ineqparam) {
        global $DB, $CFG;
        static $userstructure = null, $userkeys;
        $dbmanager = $DB->get_manager();

        if (is_null($userstructure)) {
            // Get the user table definition from the XMLDB file so we can pull
            // out fields and create temporary tables with the same definition.
            $xmldbfile = self::load_xmldbfile($CFG->dirroot.'/lib/db/install.xml');
            $xmldbstructure = $xmldbfile->getStructure();
            $userstructure = $xmldbstructure->getTable('user');
            $userkeys = $userstructure->getKeys();
        }

        // Create a temporary table into which to pull the values
        // Get the details of the field config from the XMLDB structure.
        $temptablestruct = new \xmldb_table('temp_table');

        $fieldlist = [$userstructure->getField('id')];

        for ($i = 0; $i < count($fields); $i++) {
            $fieldlist[] = $userstructure->getField($fields[$i]);
        }

        $temptablestruct->setFields($fieldlist);

        // Copy the userID key and index. This assumes they are the first key/index.
        $temptablestruct->setKeys([$userkeys[0]]);
        $dbmanager->create_temp_table($temptablestruct);

        $fieldlist = implode(',', $fields);

        $sql = "INSERT INTO {temp_table} ({$fieldlist}) (
            SELECT DISTINCT ${fieldlist} FROM {".$tablename."}
                   ORDER BY ${fieldlist}
                      LIMIT {$prime}
                      )";
        $DB->execute($sql);

        $distinctvalues = $DB->count_records('temp_table');

        $sql = self::randomize_fields_build_sql($tablename, $fields, $inequalsql, $distinctvalues);

        $DB->execute($sql, $ineqparam);

        $dbmanager->drop_table($temptablestruct);
    }

    private static function create_update_users_sql() {
        global $DB;

        $criteria = self::get_user_criteria(static::$options);
        list($where, $whereparams) = self::get_user_where_sql($criteria);

        $ids = $DB->get_records_select('user', 'id > 2 '.$where, $whereparams, 'id', 'id');
        $ids = array_keys($ids);
        $ids = implode(',', $ids);

        return $ids;
    }

    /**
     * Replace all usernames.
     */
    private static function replace_usernames() {
        global $DB;
        $where = 'id IN ('.self::$updateuserssql.')';
        $prefix = self::USERNAME_PREFIX;
        $sql = <<<SQL
UPDATE {user}
SET username = CONCAT('{$prefix}', id)
WHERE $where
SQL;
        $DB->execute($sql);
    }

    private static function set_fixed_fields() {
        global $DB;

        $fields = [
            'auth'         => 'manual',
            'mnethostid'   => 1,
            'password'     => hash_internal_user_password(self::PASSWORD_AFTER_CLEANING),
            'email'        => 'cleaned@local.datacleaner',
            'emailstop'    => 1,
            'firstaccess'  => 0,
            'lastlogin'    => 0,
            'currentlogin' => 0,
            'picture'      => 0,
            'description'  => '',
            'lastip'       => '',
            'icq'          => '',
            'skype'        => '',
            'yahoo'        => '',
            'aim'          => '',
            'msn'          => '',
            'phone1'       => '',
            'phone2'       => '',
            'idnumber'     => '',
        ];

        $select = 'id IN ('.self::$updateuserssql.')';
        foreach ($fields as $field => $value) {
            $DB->set_field_select('user', $field, $value, $select);
        }
    }
}
