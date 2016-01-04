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

    static protected $config;
    static protected $tables = array();
    static protected $skiptables = array();

    /**
     * Constructor.
     */
    public function __construct($dryrun = true, $verbose = false) {
        parent::__construct($dryrun, $verbose);
        self::$config = get_config('cleaner_replace_urls');
        self::$skiptables = self::get_skiptables(self::$config);
        self::$tables = self::get_tables(self::$skiptables);
    }

    /**
     * Returns a list of tables to replace in.
     *
     * @param array $skiptables A list of tables to skip.
     * @return array
     */
    private static function get_tables($skiptables) {
        global $DB;

        $finaltables = array();

        if (!$tables = $DB->get_tables()) {
            return $finaltables;
        }

        foreach ($tables as $table) {
            if (!in_array($table, $skiptables)) {
                $finaltables[] = $table;
            }
        }

        return $finaltables;
    }

    /**
     * Returns a list of tables to skip.
     *
     * @param object $config Config object.
     * @return array
     */
    private static function get_skiptables($config) {
        // Default skip tables.
        $defaultskiptables = array('config', 'config_plugins', 'config_log', 'upgrade_log', 'log',
                            'filter_config', 'sessions', 'events_queue', 'repository_instance_config',
                            'block_instances', '');
        $skiptables = array();
        if (isset($config->skiptables)) {
            $skiptables = array_map('trim', explode(",", $config->skiptables));
        }

        return array_unique(array_merge($defaultskiptables, $skiptables));
    }

    /**
     * Replaces URLs.
     * It's pretty much a copy of core db_replace() function from lib/adminlib.php
     */
    private static function db_replace() {
        global $DB;

        // Turn off time limits.
        \core_php_time_limit::raise();

        self::new_task(count(self::$tables) + 1); // Blocks as one task.

        foreach (self::$tables as $table) {

            mtrace("Replacing in $table ...");

            if ($columns = $DB->get_columns($table)) {
                foreach ($columns as $column) {
                    $DB->replace_all_text($table, $column,  self::$config->origsiteurl, self::$config->newsiteurl);
                }
            }
            self::next_step();
        }

        // Delete modinfo caches.
        rebuild_course_cache(0, true);
    }

    /**
     * Replaces URLs using block_XXXX_global_db_replace function.
     * It's pretty much a copy of core db_replace() function from lib/adminlib.php
     */
    static private function blocks_replace() {
        global $CFG;

        $blocks = \core_component::get_plugin_list('block');

        mtrace("Replacing using block_XXXX_global_db_replace function ...");

        foreach ($blocks as $blockname => $fullblock) {
            if ($blockname === 'NEWBLOCK') {
                continue;
            }

            if (!is_readable($fullblock.'/lib.php')) {
                continue;
            }

            $function = 'block_'.$blockname.'_global_db_replace';
            include_once($fullblock.'/lib.php');
            if (!function_exists($function)) {
                continue;
            }

            $function(self::$config->origsiteurl, self::$config->newsiteurl);
        }

        purge_all_caches();

        self::next_step();
    }

    /**
     * Executes clean.
     */
    static public function execute() {
        if (self::$dryrun) {
            $count = count(self::$tables);
            mtrace("Would replace URLs in {$count} tables.");
        } else {
            self::db_replace();
            self::blocks_replace();
        }
    }
}
