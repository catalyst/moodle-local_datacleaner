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

namespace cleaner_logstore_standard;

defined('MOODLE_INTERNAL') || die();

/**
 * Data cleaner class for logstore standard.
 *
 * @package    cleaner_logstore_standard
 * @copyright  2015 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clean extends \local_datacleaner\clean {
    /**
     * Task description.
     *
     * @var string
     */
    const TASK = 'Truncating standard logs';

    /**
     * Execute the cleaning process.
     */
    public static function execute() {

        global $DB;

        if (self::$options['dryrun']) {
            echo "Would truncate the logstore_standard_log table.\n";
        } else {
            self::new_task(1);
            $DB->delete_records('logstore_standard_log');
            self::next_step();
        }
    }
}
