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

