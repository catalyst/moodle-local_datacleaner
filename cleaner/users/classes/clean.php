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

namespace cleaner_users;

use local_datacleaner\table_scrambler;

defined('MOODLE_INTERNAL') || die();

require(__DIR__ . '/../../../classes/table_scrambler.php');

/**
 * Data cleaner class for users.
 *
 * @package    cleaner_users
 * @copyright  2015 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clean extends \local_datacleaner\clean {
    /**
     * Task.
     */
    const TASK = 'Scrambling user data';

    /**
     * A prefix to use for all usernames.
     *
     * @var string
     */
    const USERNAME_PREFIX = 'user_';

    /**
     * A SQL string with the comma-separated IDs of the users to update.
     *
     * @var string
     */
    protected static $idstoupdate;

    /**
     * Execute the cleaning process.
     */
    public static function execute() {
        if (!isset(self::$options['dryrun'])) {
            cli_error("Missing options information, cannot continue.");
        }

        self::$idstoupdate = self::create_user_id_list_to_update();

        if (empty(self::$idstoupdate)) {
            echo "No users to update.\n";
            return;
        }

        if (self::$options['dryrun']) {
            echo "Dry run mode, no records were updated.\n";
            return;
        }

        self::set_fixed_fields();
        self::replace_usernames();
        self::scramble_fields();
    }

    /**
     * Get the list of user IDs to update, based on the criteria defined in the settings.
     */
    private static function create_user_id_list_to_update() {
        global $DB;

        echo "Fetching users to update...\n";

        $criteria = self::get_user_criteria(get_config('cleaner_users'));
        [$where, $whereparams] = self::get_user_where_sql($criteria);

        $ids = $DB->get_records_select('user', 'id > 2 ' . $where, $whereparams, 'id', 'id');
        self::debug(sprintf("* Users to update: %d\n", count($ids)));

        $ids = array_keys($ids);
        $ids = implode(',', $ids);

        return $ids;
    }

    /**
     * Replace all usernames.
     */
    private static function replace_usernames() {
        global $DB;

        echo "Updating all usernames...\n";

        $ids = explode(',', self::$idstoupdate);
        [$insql, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'uid');
        $prefix = self::USERNAME_PREFIX;
        $sql = <<<SQL
UPDATE {user}
SET username = CONCAT('{$prefix}', id)
WHERE id $insql
SQL;
        $DB->execute($sql, $inparams);
    }

    /**
     * Scramble all other fields.
     */
    private static function scramble_fields() {
        $fieldset = [
            'main names'  => ['firstname', 'lastname'],
            'other names' => ['firstnamephonetic', 'alternatename', 'middlename', 'lastnamephonetic'],
            'department'  => ['institution', 'department'],
            'address'     => ['address', 'city', 'country', 'lang', 'calendartype', 'timezone'],
        ];

        foreach ($fieldset as $title => $fields) {
            echo "Scrambling: {$title} ...\n";
            $scrambler = new table_scrambler('user', $fields);
            $scrambler->set_change_only_ids(self::$idstoupdate);
            $scrambler->execute();
        }
    }

    /**
     * Set fields to fixed values.
     */
    private static function set_fixed_fields() {
        global $DB;

        echo "Erasing extra information...\n";

        $fields = [
            'auth'         => 'nologin',
            'password'     => AUTH_PASSWORD_NOT_CACHED,
            'email'        => 'cleaned@datacleaner.example',
            'emailstop'    => 1,
            'firstaccess'  => 0,
            'lastlogin'    => 0,
            'currentlogin' => 0,
            'picture'      => 0,
            'description'  => '',
            'lastip'       => '',
            'phone1'       => '',
            'phone2'       => '',
            'idnumber'     => '',
        ];

        $ids = explode(',', self::$idstoupdate);
        [$insql, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'uid');
        $select = "id $insql";
        foreach ($fields as $field => $value) {
            self::debug("* Erasing contents for: {$field} ...\n");
            $DB->set_field_select('user', $field, $value, $select, $inparams);
        }
    }
}
