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
 * @package    cleaner_replace_urls
 * @copyright  2015 Catalyst IT
 * @author     Nigel Cunningham
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_replace_urls;

defined('MOODLE_INTERNAL') || die();

class clean extends \local_datacleaner\clean {
    const TASK = 'Replacing URLs';

    static public function execute() {
        global $DB;

        // Get the settings, handling the case where new ones (dev) haven't been set yet.
        $config = get_config('cleaner_replace_urls');

        if (self::$dryrun) {
            /* Based on code in lib/adminlib.php */
            $tables = $DB->get_tables();
            $count = count($tables) - 11; /* 11 = count($skiptables) in db_replace */
            echo "Would replace URLs in {$count} tables.\n";
        } else {
            self::new_task(1);
            ob_start();
            db_replace($config->origsiteurl, $config->newsiteurl);
            ob_end_clean();
            self::next_step();
        }
    }
}

