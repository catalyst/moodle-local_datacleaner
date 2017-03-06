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
 * Cleaner.
 *
 * @package    cleaner_email
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  2017 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace cleaner_email;

use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Cleaner.
 *
 * @package    cleaner_email
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  2017 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clean extends \local_datacleaner\clean {

    /** The task name */
    const TASK = 'Email cleaner';

    /**
     * Do the work.
     */
    static public function execute() {

        $config = get_config('cleaner_email');
        $dryrun = (bool)self::$options['dryrun'];
        $verbose = (bool)self::$options['verbose'];

        self::debugmemory();

        self::new_task(3);

        self::execute_configreplace($config, $verbose, $dryrun);
        self::next_step();

        self::execute_appendsuffix($config, $verbose, $dryrun);
        self::next_step();

        self::execute_ignoresuffix($config, $verbose, $dryrun);
        self::next_step();
    }

    /**
     * Replace the defined $CFG settings.
     *
     * @param stdClass $config
     * @param bool $verbose
     * @param bool $dryrun
     *
     */
    public static function execute_configreplace($config, $verbose, $dryrun) {
        $cfgsettings = [
            'noemailever',
            'divertallemailsto',
            'divertallemailsexcept',
        ];

        foreach ($cfgsettings as $setting) {
            $value = $config->$setting;

            if ($verbose) {
                mtrace("Executing: set_config('$setting', ********);");
            }

            if (!$dryrun) {
                set_config($setting, $value);
            }
        }
    }

    /**
     * Append a suffix to all {user} email addresses.
     *
     * @param stdClass $config
     * @param bool $verbose
     * @param bool $dryrun
     *
     * @return bool
     */
    public static function execute_appendsuffix($config, $verbose, $dryrun) {
        global $DB;

        $dbtype = $DB->get_dbfamily();
        $suffix = $config->emailsuffix;

        $query = '';

        if (empty($suffix)) {
            return false;
        }

        if ($dbtype == 'postgres') {
            $query = "UPDATE {user} SET email = email || '$suffix'";
        } else if ($dbtype == 'mysql') {
            $query = "UPDATE {user} SET email = CONCAT(email, '$suffix') ";
        } else {
            mtrace("Database not supported: $dbtype");
        }

        if ($verbose) {
            mtrace("Executing: $query");
        }

        if (!$dryrun) {
            $DB->execute($query);
        }

        return true;
    }

    /**
     * Remove the suffix from a set of users based on the defined regular expression.
     *
     * @param stdClass $config
     * @param bool $verbose
     * @param bool $dryrun
     *
     * @return bool
     */
    public static function execute_ignoresuffix($config, $verbose, $dryrun) {
        global $DB;

        $dbtype = $DB->get_dbfamily();
        $suffix = $config->emailsuffix;
        $emailsuffixignore = $config->emailsuffixignore;

        $query = '';

        if (empty($suffix) || empty($emailsuffixignore)) {
            return false;
        }

        if ($dbtype == 'postgres') {
            $query = "UPDATE {user} SET email = regexp_replace(email, '$suffix', '', 'g') WHERE email ~ '$emailsuffixignore'";
        } else if ($dbtype == 'mysql') {
            $query = "UPDATE {user} SET email = TRIM(TRAILING '$suffix' FROM email) WHERE email REGEXP '$emailsuffixignore'";
        } else {
            mtrace("Database not supported: $dbtype");
        }

        if ($verbose) {
            mtrace("Executing: $query");
        }

        if (!$dryrun) {
            $DB->execute($query);
        }

        return true;
    }
}