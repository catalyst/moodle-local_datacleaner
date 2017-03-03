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

defined('MOODLE_INTERNAL') || die();

class clean extends \local_datacleaner\clean {
    const TASK = 'Email cleaner';

    /**
     * Do the work.
     */
    static public function execute() {
        global $DB;

        $config = get_config('cleaner_email');
        $dryrun = (bool)self::$options['dryrun'];
        $verbose = (bool)self::$options['verbose'];

        self::debugmemory();
        self::new_task(2);

        // Set the defined $CFG items.
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

        self::next_step();

        // Append the email suffix.
        $dbtype = $DB->get_dbfamily();
        $suffix = $config->emailsuffix;

        $query = '';

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

        self::next_step();
    }
}