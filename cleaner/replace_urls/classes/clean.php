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

namespace cleaner_replace_urls;

/**
 * Data cleaner class for URLs.
 *
 * @package    cleaner_replace_urls
 * @copyright  2015 Nigel Cunningham <nigelc@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clean extends \local_datacleaner\clean {
    /**
     * Task.
     *
     * @var string
     */
    const TASK = 'Replacing URLs';

    /**
     * Config.
     *
     * @var mixed
     */
    protected static $config;

    /**
     * Tables.
     *
     * @var array
     */
    protected static $tables = [];

    /**
     * Skiptables.
     *
     * @var array
     */
    protected static $skiptables = [];

    /**
     * Constructor.
     *
     * @param array $options Optional initialization options.
     */
    public function __construct($options = []) {
        parent::__construct($options);
        self::$config = get_config('cleaner_replace_urls');
        self::$skiptables = self::get_skiptables(self::$config);
        self::$tables = self::get_tables(self::$skiptables);
        self::set_newsiteurl();
    }

    /**
     * Set URLs from envbar or config, falling back to sensible defaults.
     *
     * If local_envbar is installed, the production URL is read from its config
     * and the new site URL is the current wwwroot — no manual configuration needed.
     */
    private static function set_newsiteurl() {
        global $CFG;

        if (class_exists('\local_envbar\local\envbarlib')) {
            if (empty(self::$config->origsiteurl)) {
                self::$config->origsiteurl = \local_envbar\local\envbarlib::getprodwwwroot();
            }
            if (empty(self::$config->newsiteurl)) {
                self::$config->newsiteurl = $CFG->wwwroot;
            }
            return;
        }

        if (empty(self::$config->newsiteurl)) {
            self::$config->newsiteurl = $CFG->wwwroot;
        }
    }

    /**
     * Returns a list of tables to replace in.
     *
     * @param array $skiptables A list of tables to skip.
     * @return array
     */
    private static function get_tables($skiptables) {
        global $DB;

        $finaltables = [];

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
        $skiptables = [];
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
        // Check for MOODLE_26 and below.
        if (class_exists('core_php_time_limit')) {
            \core_php_time_limit::raise();
        }

        $replacing = [];

        foreach (self::$tables as $table) {
            if ($columns = $DB->get_columns($table)) {
                $wysiwyg = [];

                foreach ($columns as $column) {
                    // Clean all columns in tables with the name 'config'.
                    if (self::$config->cleanconfig) {
                        if (strpos($table, 'config') !== false) {
                            $replacing[$table][$column->name] = $column;
                        }
                    }

                    // Clean all columns of type 'text' or 'varchar'.
                    if (self::$config->cleantext) {
                        if ($column->type === "text" || $column->type === "varchar") {
                            $replacing[$table][$column->name] = $column;
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
                    }
                }
            } // End db get columns on table.
        } // End foreach tables.

        foreach ($replacing as $table => $columns) {
            self::new_task(count($columns));
            foreach ($columns as $column) {
                // Only text-like columns can contain URLs.
                if ($column->type !== 'text' && $column->type !== 'varchar') {
                    self::next_step();
                    continue;
                }
                $count = $DB->count_records_select(
                    $table,
                    $DB->sql_like($column->name, ':search', false),
                    ['search' => '%' . $DB->sql_like_escape(self::$config->origsiteurl) . '%']
                );
                if ($count > 0) {
                    mtrace("  {$table}::{$column->name} — {$count} row(s)");
                    $DB->replace_all_text($table, $column, self::$config->origsiteurl, self::$config->newsiteurl);
                } else if (!empty(self::$options['verbose'])) {
                    mtrace("  {$table}::{$column->name} — 0 rows, skipping");
                }
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
    private static function blocks_replace() {
        global $CFG;

        $blocks = \core_component::get_plugin_list('block');
        $blockfunctions = [];

        foreach ($blocks as $blockname => $fullblock) {
            if ($blockname === 'NEWBLOCK') {
                continue;
            }

            if (!is_readable($fullblock . '/lib.php')) {
                continue;
            }

            $function = 'block_' . $blockname . '_global_db_replace';
            include_once($fullblock . '/lib.php');
            if (!function_exists($function)) {
                continue;
            }

            $blockfunctions[] = $function;
        }

        self::new_task(count($blockfunctions));

        foreach ($blockfunctions as $function) {
            if (!isset(self::$options['verbose']) || self::$options['verbose'] == true) {
                mtrace("Replacing using $function function ...");
            }
            $function(self::$config->origsiteurl, self::$config->newsiteurl);
            self::next_step();
        }

        purge_all_caches();
    }

    /**
     * Execute the cleaning process.
     */
    public static function execute() {
        $orig    = self::$config->origsiteurl;
        $new     = self::$config->newsiteurl;
        $count   = count(self::$tables);
        $verbose = (bool)self::$options['verbose'];
        $dryrun  = (bool)self::$options['dryrun'];

        if (empty($orig)) {
            mtrace("ERROR: origsiteurl is not set. Configure it at the Replace URLs settings page or install local_envbar.");
            return;
        }

        if (empty($new)) {
            mtrace("ERROR: newsiteurl is not set.");
            return;
        }

        $willconfig  = !empty(self::$config->cleanconfig);
        $willtext    = !empty(self::$config->cleantext);
        $willwysiwyg = !empty(self::$config->cleanwysiwyg);

        if (!$willconfig && !$willtext && !$willwysiwyg) {
            mtrace("WARNING: No replacement targets enabled. Enable 'Replace in config tables', 'Replace in text/varchar' or 'Replace in wysiwyg' in settings.");
            return;
        }

        $prefix = $dryrun ? 'Would replace' : 'Replacing';
        mtrace("{$prefix} '{$orig}' => '{$new}' in {$count} tables.");

        if ($verbose) {
            mtrace("  config tables:   " . ($willconfig  ? 'YES' : 'NO'));
            mtrace("  text/varchar:    " . ($willtext    ? 'YES' : 'NO'));
            mtrace("  wysiwyg fields:  " . ($willwysiwyg ? 'YES' : 'NO'));
        }

        if (!$dryrun) {
            self::db_replace();
            self::blocks_replace();
        }
    }
}
