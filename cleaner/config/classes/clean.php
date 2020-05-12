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
 * @package    cleaner_config
 * @copyright  2015 Catalyst IT
 * @author     Nigel Cunningham
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_config;

defined('MOODLE_INTERNAL') || die();

class clean extends \local_datacleaner\clean {
    const TASK = 'Removing config settings';

    /**
     * Define the config names and values to be cleaned.
     */
    public static function get_where() {

        // Get the settings, handling the case where new ones (dev)
        // haven't been set yet.
        $config = get_config('cleaner_config');

        $where = '';
        $params = [];

        $names = isset($config->names) ? explode("\n", $config->names) : array();
        foreach ($names as $name) {
            $name = trim($name);
            if (empty($name)) {
                continue;
            }
            if ($where) {
                $where .= " OR ";
            }
            $where .= " name LIKE ?";
            $params[] = $name;
        }
        $values = isset($config->vals) ? explode("\n", $config->vals) : array();
        foreach ($values as $val) {
            $val = trim($val);
            if (empty($val)) {
                continue;
            }
            if ($where) {
                $where .= " OR ";
            }
            $where .= " value LIKE ?";
            $params[] = $val;
        }

        return [$where, $params];
    }

    /**
     * Do the hard work of removing config settings.
     */
    static public function execute() {
        global $DB;

        list($where, $params) = self::get_where();

        if ($where) {
            self::new_task(2);
            if (self::$options['dryrun']) {
                $count = $DB->count_records_select('config', $where, $params);
                echo "Would delete {$count} records from the config table.\n";
            } else {
                $DB->delete_records_select("config", $where, $params);
            }
            self::next_step();
            if (self::$options['dryrun']) {
                $count = $DB->count_records_select('config_plugins', $where, $params);
                echo "Would delete {$count} records from the config_plugins table.\n";
            } else {
                $DB->delete_records_select("config_plugins", $where, $params);
            }
            self::next_step();
        }
    }
}
