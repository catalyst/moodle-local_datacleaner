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
    protected $needscascadedelete = true;

    /**
     * Do the hard work of cleaning up users.
     */
    static public function execute() {
        global $DB;

        // Get the settings, handling the case where new ones (dev) haven't been set yet.
        $config = get_config('cleaner_delete_users');
        $numusers = self::get_user_count($config);

        if (!$numusers) {
            echo "No users to delete.\n";
            return;
        }

        // Get on with the real work!
        if (self::$options['dryrun']) {
            echo 'Would delete ' . $numusers . " users.\n";
        } else {
            self::new_task($numusers);
            $users = self::get_user_chunk($config);
            while (!empty($users)) {
                list($sql, $params) = $DB->get_in_or_equal($users);
                $DB->delete_records_select('user', 'id ' . $sql, $params);
                self::next_step(count($users));
                $users = self::get_user_chunk($config);
            }
            echo 'Deleted ' . $numusers . " users.\n";
        }
    }
}
