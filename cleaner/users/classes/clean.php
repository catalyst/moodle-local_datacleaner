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
        'firstname fields' => array('firstname', 'firstnamephonetic', 'alternatename'),
        'middlename fields' => array('middlename'),
        'surname fields' => array('lastname', 'lastnamephonetic'),
        'department' => array('institution', 'department'),
        'address' => array('address', 'city', 'country', 'lang', 'calendartype', 'timezone')
    );

    private static $functions = array(
        'usernames' => 'username_substitution',
    );

    /**
     * The last prime number used in scrambling field contents.
     */
    private static $lastprime = 0;

    /**
     * Constructor - hash the password.
     */
    public function __construct() {
        self::$fixedmods['password'] = hash_internal_user_password(self::PASS);
    }

    /**
     * username_substitution - Replace usernames with user_XXX
     *
     * @param array $users - The userIDs on which to operate.
     */
    private static function username_substitution($users = array()) {
        global $DB;

        $userids = array_keys($users);
        $chunks = array_chunk($userids, 65000);
        foreach ($chunks as $chunk) {
            list($sql, $params) = $DB->get_in_or_equal($chunk);
            $DB->execute('UPDATE {user} SET username = CONCAT(\'user\', id) WHERE id ' . $sql, $params);
        }
    }

    /**
     * Find next prime number above n
     *
     * Note that n is assumed to be relatively low. If you have 50,000 users,
     * you will be sending sqr(50,000) = 223.
     *
     * Table taken from http://www.factmonster.com/math/numbers/prime.html
     *
     * @param int lowerlimit - The returned value will be greater than this number.
     * @return int - The next prime number
     */
    private static function next_prime($lowerlimit) {
        $primes = array(
            2, 3, 5, 7, 11, 13, 17, 19, 23,
            29, 31, 37, 41, 43, 47, 53, 59, 61, 67,
            71, 73, 79, 83, 89, 97, 101, 103, 107, 109,
            113, 127, 131, 137, 139, 149, 151, 157, 163, 167,
            173, 179, 181, 191, 193, 197, 199, 211, 223, 227,
            229, 233, 239, 241, 251, 257, 263, 269, 271, 277,
            281, 283, 293, 307, 311, 313, 317, 331, 337, 347,
            349, 353, 359, 367, 373, 379, 383, 389, 397, 401,
            409, 419, 421, 431, 433, 439, 443, 449, 457, 461,
            463, 467, 479, 487, 491, 499, 503, 509, 521, 523,
            541, 547, 557, 563, 569, 571, 577, 587, 593, 599,
            601, 607, 613, 617, 619, 631, 641, 643, 647, 653,
            659, 661, 673, 677, 683, 691, 701, 709, 719, 727,
            733, 739, 743, 751, 757, 761, 769, 773, 787, 797,
            809, 811, 821, 823, 827, 829, 839, 853, 857, 859,
            863, 877, 881, 883, 887, 907, 911, 919, 929, 937,
            941, 947, 953, 967, 971, 977, 983, 991, 997);

        $base = 0;
        for ($i = intval((count($primes) / 2) + .5); $i > 1; $i = intval(($i / 2) + .5)) {
            if ($primes[$base + $i] <= $lowerlimit) {
                $base += $i;
            }
        }

        return $primes[$base + 1] <= $lowerlimit ? $primes[$base + 2] : $primes[$base + 1];
    }

    /**
     * Load an install.xml file, checking that it exists, and that the structure is OK.
     *
     * This is copied from lib/ddl/database_manager.php because it's a private method there.
     *
     * @param string $file the full path to the XMLDB file.
     * @return xmldbfile the loaded file.
     */
    static private function load_xmldbfile($file) {
        global $CFG;

        $xmldbfile = new \xmldb_file($file);

        if (!$xmldbfile->fileExists()) {
            throw new ddl_exception('ddlxmlfileerror', null, 'File does not exist');
        }

        $loaded = $xmldbfile->loadXMLStructure();
        if (!$loaded || !$xmldbfile->isLoaded()) {
            // Show info about the error if we can find it.
            if ($structure = $xmldbfile->getStructure()) {
                if ($errors = $structure->getAllErrors()) {
                    throw new ddl_exception('ddlxmlfileerror', null, 'Errors found in XMLDB file: '. implode (', ', $errors));
                }
            }
            throw new ddl_exception('ddlxmlfileerror', null, 'not loaded??');
        }

        return $xmldbfile;
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
     * @param array $users  - The UIDS of users who will be modified and from whom data will be selected
     * @param array $fields - The names of user tables fields that will be changed. More than one means
     *                        values will be changed together  (ie keep the city, country and timezone
     *                        making sense together).
     */
    static public function randomise_fields($users = array(), $fields = array()) {
        global $DB, $CFG;
        $tmptables = array();
        $dbmanager = $DB->get_manager();

        // Get the user table definition from the XMLDB file so we can pull
        // out fields and create temporary tables with the same definition.
        $xmldbfile = self::load_xmldbfile($CFG->dirroot . '/lib/db/install.xml');
        $xmldbstructure = $xmldbfile->getStructure();
        $userstructure = $xmldbstructure->getTable('user');
        $userkeys = $userstructure->getKeys();

        $numusers = count($users);
        $lastprime = max(sqrt($numusers), self::$lastprime);

        $thisprime = self::next_prime($lastprime);
        self::$lastprime = $thisprime;

        // Create a temporary table into which to pull the values
        // Get the details of the field config from the XMLDB structure.
        $temptablestruct = new \xmldb_table('temp_table');

        $fieldlist = array($userstructure->getField('id'));

        for ($i = 0; $i < count($fields); $i++) {
            $fieldlist[] = $userstructure->getField($fields[$i]);
        }

        $temptablestruct->setFields($fieldlist);

        // Copy the userID key and index. This assumes they are the first key/index.
        $temptablestruct->setKeys(array($userkeys[0]));
        $dbmanager->create_temp_table($temptablestruct);

        $fieldlist = implode(',', $fields);

        $sql = "INSERT INTO {temp_table} ({$fieldlist}) (
            SELECT DISTINCT ${fieldlist} FROM {user}
                   ORDER BY ${fieldlist}
                      LIMIT {$thisprime}
                      )";
        $DB->execute($sql);

        $distinctvalues = $DB->count_records('temp_table');

        // Now that we have the temporary tables, use them to update the original table.
        $sets = array();
        $conditions = array();

        foreach ($fields as $field) {
            $sets[] = "{$field} = {temp_table}.{$field}";
        }

        $userids = array_keys($users);
        $chunks = array_chunk($userids, 65000);
        foreach ($chunks as $chunk) {
            list($inequalsql, $params) = $DB->get_in_or_equal($chunk);

            $sql = 'UPDATE {user} u SET ' . implode(',', $sets) .
                " FROM {temp_table} WHERE (1 + (u.id % {$distinctvalues})) = {temp_table}.id
                AND u.id {$inequalsql}";

            $DB->execute($sql, $params);
        }

        $dbmanager->drop_table($temptablestruct);
    }

    /**
     * Do the hard work of cleaning up users.
     */
    static public function execute() {

        global $DB, $CFG;

        // Get the settings, handling the case where new ones (dev) haven't been set yet.
        $config = get_config('cleaner_users');

        $criteria = self::get_criteria($config);

        // Get the list of users on which to work.
        $users = self::get_users($criteria);
        $numusers = count($users);

        if (!$numusers) {
            echo "No users require data scrambling.\n";
            return;
        }

        echo "Scrambling the data of {$numusers} users.\n";

        // Scramble the eggs.
        $numsteps = count(self::$scramble) + count(self::$fixedmods) + 1;
        self::update_status(self::TASK, 0, $numsteps);
        $thisstep = 1;
        foreach (self::$scramble as $description => $setoffields) {
            self::randomise_fields($users, $setoffields);
            self::update_status(self::TASK, $thisstep, $numsteps);
            $thisstep++;
        }

        // Apply the fixed values. One step for what remains because this is fast.
        $userids = array_keys($users);
        $chunks = array_chunk($userids, 65000);
        foreach (self::$fixedmods as $field => $value) {
            foreach ($chunks as $chunk) {
                list($sql, $params) = $DB->get_in_or_equal($chunk);
                $DB->set_field_select('user', $field, $value, 'id ' . $sql, $params);
            }
            self::update_status(self::TASK, $thisstep, $numsteps);
            $thisstep++;
        }

        // Apply the functions.
        foreach (self::$functions as $field => $fnname) {
            self::$fnname($users);
        }
        self::update_status(self::TASK, $thisstep, $numsteps);
    }
}
