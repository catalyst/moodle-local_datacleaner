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

        $verbose = (bool)self::$options['verbose'];
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
                } // End foreach columns as column.

                // Find wysiwyg columns that have a paired 'format' column.
                if (self::$config->cleanwysiwyg) {
                    foreach ($columns as $col) {
                        if (preg_match('/(.*)format$/', $col->name, $matches)) {
                            if (!empty($matches[1])) {
                                $wysiwyg[$col->name] = $matches[1];
                            }
                        }
                    }
                } // End cleanwysiwyg.

                // Add found wysiwyg columns to the list of things to clean.
                foreach ($wysiwyg as $name) {
                    if (array_key_exists($name, $columns)) {
                        $column = $columns[$name];
                        $replacing[$table][$column->name] = $column;
                    }
                }
            } // End db get columns on table.
        } // End foreach tables.

        $maxwidth = 0;
        $minlen = max(strlen(self::$config->origsiteurl), strlen(self::$config->newsiteurl));
        foreach ($replacing as $table => $columns) {
            foreach ($columns as $column) {
                $maxwidth = max($maxwidth, strlen("{$table}::{$column->name}"));
            }
        }

        self::new_task(count($replacing));
        foreach ($replacing as $table => $columns) {
            foreach ($columns as $column) {
                // Only text-like columns can contain URLs.
                if ($column->type !== 'text' && $column->type !== 'varchar' && $column->type !== 'longtext') {
                    continue;
                }
                // Skip varchar columns too short to contain either URL.
                if ($column->type === 'varchar' && $column->max_length < $minlen) {
                    continue;
                }
                $count = $DB->count_records_select(
                    $table,
                    $DB->sql_like($column->name, ':search', false),
                    ['search' => '%' . $DB->sql_like_escape(self::$config->origsiteurl) . '%']
                );
                $label = str_pad("{$table}::{$column->name}", $maxwidth);
                if ($count > 0) {
                    if ($verbose) {
                        mtrace("  {$label} — {$count} row(s)");
                    }
                    $DB->replace_all_text($table, $column, self::$config->origsiteurl, self::$config->newsiteurl);
                } else if ($verbose) {
                    mtrace("  {$label} — 0 rows, skipping");
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
     * Replaces the old URL in one piece of text.
     *
     * If the new URL contains the old one (e.g. example.com to example.com/staging), addresses that are
     * already the new URL are left alone, so they don't get doubled up.
     *
     * @param string $text
     * @return string
     */
    private static function replace_in_text($text) {
        $orig = self::$config->origsiteurl;
        $new = self::$config->newsiteurl;
        // Plain case: the new address doesn't contain the old one, so a simple swap is safe.
        if (strpos($new, $orig) === false) {
            return str_replace($orig, $new, $text);
        }
        // Look for the new address first, so when we find one we know to leave it alone.
        // It only counts as the new address if the URL doesn't carry on (e.g. /staging-news or /staging.old is still
        // an old path). A new address ending in a slash always carries on into a path, so it needs no such check.
        $boundary = substr($new, -1) === '/' ? '' : '(?![\w%-]|\.\w)';
        $pattern = '/' . preg_quote($new, '/') . $boundary . '|' . preg_quote($orig, '/') . '/';
        return preg_replace_callback($pattern, function ($matches) use ($new) {
            return $new;
        }, $text);
    }

    /**
     * Replaces the old URL in every text piece of a serialized string, fixing each length number.
     *
     * Text pieces look like s:12:"some text"; and are read using their declared length, so quotes and
     * semicolons inside the text can't confuse it.
     *
     * @param string $raw Serialized data.
     * @return string|null The updated serialized data, or null if it doesn't look safe to touch.
     */
    private static function replace_in_serialized($raw) {
        $out = '';
        $pos = 0;
        // Find each stored text piece.
        while (preg_match('/s:(\d+):"/', $raw, $m, PREG_OFFSET_CAPTURE, $pos)) {
            $tokenstart = $m[0][1];
            $textstart = $tokenstart + strlen($m[0][0]);
            $length = (int)$m[1][0];
            // The text must be exactly that long and then end with ";. If not, it isn't safe to touch.
            // Check the length against what's left first, so a huge bogus number can't break the maths.
            if ($length > strlen($raw) - $textstart - 2 || substr($raw, $textstart + $length, 2) !== '";') {
                return null;
            }
            $original = substr($raw, $textstart, $length);
            $text = self::replace_in_text($original);
            // Serialized data stored inside a text piece would end up with wrong inner length numbers.
            if ($text !== $original && preg_match('/^[a-zA-Z]:\d+[:;]/', $original)) {
                return null;
            }
            // Copy everything before this piece, then the piece itself with a fresh length number.
            $out .= substr($raw, $pos, $tokenstart - $pos) . 's:' . strlen($text) . ':"' . $text . '";';
            // Carry on after this piece, skipping over its text so we never look inside it.
            $pos = $textstart + $length + 2;
        }
        return $out . substr($raw, $pos);
    }

    /**
     * Replaces URLs in block_instances.configdata for all block types.
     *
     * configdata is base64(serialize()), so a plain replace would break the string length prefixes.
     * Instead the serialized text is edited directly and each rewritten string gets its length recalculated.
     * Rows are only written if the result still unserializes. Rows that can't be handled safely are skipped
     * and reported.
     */
    private static function generic_block_configdata_replace() {
        global $DB;

        // The old address we are hunting for.
        $search = (string)self::$config->origsiteurl;
        if ($search === '') {
            mtrace("WARNING: Original site URL is empty, not replacing in block configuration.");
            return;
        }
        // Verbose output of the block configdata clean.
        $verbose = !isset(self::$options['verbose']) || self::$options['verbose'] == true;

        // Get every block that has settings saved.
        $rs = $DB->get_recordset_select('block_instances', 'configdata IS NOT NULL', null, 'id', 'id, blockname, configdata');
        // Tracking block instances we updated or skipped.
        $updated = 0;
        $skipped = 0;
        foreach ($rs as $instance) {
            // Unwrap the saved settings from their base64 state.
            $raw = base64_decode((string)$instance->configdata, true);
            // Can't unwrap it, or the old address isn't in there? Nothing to do, move on.
            if ($raw === false || strpos($raw, $search) === false) {
                continue;
            }

            // Fix every piece of text that holds the old address.
            $new = self::replace_in_serialized($raw);
            // Double check that PHP can still read the result.
            // Classes are not allowed to load, so this can't run anything nasty.
            // Also make sure no old address is left anywhere (e.g. in a custom serialized object).
            $valid = $new !== null
                && self::replace_in_text($new) === $new
                && @unserialize($new, ['allowed_classes' => false]) !== false;
            if (!$valid) {
                // Not sure it's safe? Don't touch it, and always tell the user.
                $skipped++;
                mtrace("WARNING: Skipping block instance {$instance->id} ({$instance->blockname}): could not safely replace.");
                continue;
            }

            // Nothing changed (e.g. it already has the new address)? Nothing to save.
            if ($new === $raw) {
                continue;
            }

            // Good. Put it back to its base64 state and save it.
            $DB->update_record('block_instances', (object)[
                'id' => $instance->id,
                'configdata' => base64_encode($new),
                'timemodified' => time(),
            ]);
            $updated++;
        }
        // Close the list of blocks.
        $rs->close();

        // Report the tracking data.
        if ($verbose) {
            mtrace("Block configdata: updated $updated, skipped $skipped.");
        }

        // Blocks are cached, so clear the caches to make them show the new settings.
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
        // Never saved means on, which is the default.
        $willblocks  = !isset(self::$config->cleanblocks) || !empty(self::$config->cleanblocks);

        if (!$willconfig && !$willtext && !$willwysiwyg && !$willblocks) {
            mtrace("WARNING: No replacement targets enabled. " .
                "Enable 'Replace in config tables', 'Replace in text/varchar', 'Replace in wysiwyg' " .
                "or 'Replace in block configuration' in settings.");
            return;
        }

        $prefix = $dryrun ? 'Would replace' : 'Replacing';

        if ($verbose) {
            mtrace("{$prefix} '{$orig}' => '{$new}' in {$count} tables.");
            mtrace("  config tables:   " . ($willconfig ? 'YES' : 'NO'));
            mtrace("  text/varchar:    " . ($willtext ? 'YES' : 'NO'));
            mtrace("  wysiwyg fields:  " . ($willwysiwyg ? 'YES' : 'NO'));
            mtrace("  block config:    " . ($willblocks ? 'YES' : 'NO'));
        }

        if (!$dryrun) {
            // With only block config switched on there are no tables to replace in.
            if ($willconfig || $willtext || $willwysiwyg) {
                self::db_replace();
            }
            self::blocks_replace();
            if ($willblocks) {
                self::generic_block_configdata_replace();
            }
        }
    }
}
