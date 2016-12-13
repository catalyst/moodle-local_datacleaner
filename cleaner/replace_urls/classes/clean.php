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
    public function __construct($options = array()) {
        parent::__construct($options);
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
        $skiptables = array();
        if (isset($config->skiptables)) {
            $skiptables = array_map('trim', explode(",", $config->skiptables));
        }

        if (self::$config->cleanconfig) {
            foreach ($skiptables as $key => $table) {
                if (strpos($table, 'config') !== false) {
                    unset($skiptables[$key]);
                }
            }
        }

        return $skiptables;
    }

    /**
     * Replaces URLs.
     * It's pretty much a copy of core db_replace() function from lib/adminlib.php
     */
    private static function db_replace() {
        global $DB;

        // Turn off time limits.
        \core_php_time_limit::raise();

        $replacing = array();
        $count = 1; // Blocks as one task.

        foreach (self::$tables as $table) {

            if ($columns = $DB->get_columns($table)) {
                $wysiwyg = array();

                foreach ($columns as $column) {

                    // Clean all columns in tables with the name 'config'.
                    if (self::$config->cleanconfig) {
                        if (strpos($table, 'config') !== false) {
                            $replacing[$table][$column->name] = $column;
                            $count += 1;
                        }

                    }

                    // Clean all columns of type 'text' or 'varchar'.
                    if (self::$config->cleantext) {
                        if ($column->type === "text" || $column->type === "varchar") {
                            $replacing[$table][$column->name] = $column;
                            $count += 1;
                        }
                    }

                    // Clean oof wysiwyg columns that have a pair 'format' column.
                    if (self::$config->cleanwysiwyg) {
                        foreach ($columns as $column) {
                            if (preg_match('/(.*)format$/', $column->name, $matches)) {

                                if (!empty($matches[1])) {
                                    $wysiwyg[$column->name] = $matches[1];
                                }
                            }
                        }
                    } // End cleanwysiwyg.
                } // End foreach columns as column.

                // Add found wysiwyg columns to the list of things to clean.
                foreach ($wysiwyg as $name) {
                    if (array_key_exists($name, $columns)) {
                        $column = $columns[$name];
                        $replacing[$table][$column->name] = $column;
                        $count += 1;
                    }
                }

            } // End db get columns on table.
        } // End foreach tables.

        self::new_task($count);
        foreach ($replacing as $table => $columns) {
            foreach ($columns as $column) {
                mtrace("Replacing in $table::$column->name ...");
                $DB->replace_all_text($table, $column, self::$config->origsiteurl, self::$config->newsiteurl);
                self::next_step();
            }
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
        if (self::$options['dryrun']) {
            $count = count(self::$tables);
            mtrace("Would replace URLs in {$count} tables.");
        } else {
            self::db_replace();
            self::blocks_replace();
        }
    }
}
