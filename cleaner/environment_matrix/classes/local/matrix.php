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

namespace cleaner_environment_matrix\local;

use admin_setting_confightmleditor;
use admin_setting_configtextarea;
use admin_setting_heading;
use local_datacleaner\lib;
use stdClass;

/**
 * Environment matrix class.
 *
 * @package    cleaner_environment_matrix
 * @copyright  2017 Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class matrix {
    /** @var string Placeholder displayed instead of secret or forced production values. */
    const REDACTED = '*****';

    /** @var array|null Cache of secret settings, keyed by plugin then setting name. */
    private static $secretsettings = null;

    /**
     * Is the admin setting a password type setting?
     *
     * @param mixed $setting An admin_setting instance.
     * @return bool
     */
    public static function is_password_setting($setting): bool {
        return $setting instanceof \admin_setting_configpasswordunmask
            || $setting instanceof \admin_setting_encryptedpassword;
    }

    /**
     * Returns the list of settings that contain secrets (passwords, keys, tokens, etc).
     *
     * This is built from the password type settings in the admin tree and from the
     * admin presets "sensible settings" list.
     *
     * @return array Keyed by plugin ('core' for core settings), then setting name.
     */
    public static function get_secret_settings(): array {
        global $CFG;

        if (self::$secretsettings !== null) {
            return self::$secretsettings;
        }

        $secrets = [];

        // Settings flagged as sensitive by admin presets, in the format name@@plugin.
        $sensible = get_config('adminpresets', 'sensiblesettings');
        if (!empty($sensible)) {
            foreach (explode(',', $sensible) as $entry) {
                $parts = explode('@@', trim($entry));
                if (count($parts) != 2 || $parts[0] === '') {
                    continue;
                }
                [$name, $plugin] = $parts;
                $plugin = ($plugin === '' || $plugin === 'none') ? 'core' : $plugin;
                $secrets[$plugin][$name] = true;
            }
        }

        // Password type settings from the admin tree.
        require_once($CFG->libdir . '/adminlib.php');
        self::collect_password_settings(admin_get_root(), $secrets);

        self::$secretsettings = $secrets;
        return $secrets;
    }

    /**
     * Recursively collect password settings from an admin tree node.
     *
     * @param mixed $node An admin tree node.
     * @param array $secrets Collected secrets, keyed by plugin then setting name.
     */
    private static function collect_password_settings($node, array &$secrets): void {
        if ($node instanceof \admin_settingpage) {
            foreach ((array) $node->settings as $setting) {
                if (self::is_password_setting($setting)) {
                    $plugin = empty($setting->plugin) ? 'core' : $setting->plugin;
                    $secrets[$plugin][$setting->name] = true;
                }
            }
        } else if ($node instanceof \admin_category && method_exists($node, 'get_children')) {
            foreach ($node->get_children() as $child) {
                self::collect_password_settings($child, $secrets);
            }
        }
    }

    /**
     * Returns true if the setting holds a secret value.
     *
     * @param string $configname
     * @param string $plugin
     * @return bool
     */
    public static function is_secret(string $configname, string $plugin): bool {
        $plugin = empty($plugin) ? 'core' : $plugin;
        $secrets = self::get_secret_settings();
        return !empty($secrets[$plugin][$configname]);
    }

    /**
     * Returns true if the setting name looks like a secret.
     *
     * @param string $configname
     * @return bool
     */
    public static function is_secret_like(string $configname): bool {
        return preg_match(
            '/pass(word|wd)?|secret|token|apikey|api_key|privatekey|salt|credential/i',
            $configname
        );
    }

    /**
     * Returns true if the value of this setting must not be displayed.
     *
     * Only production values are redacted, as non-production values are not real.
     * On production, secrets and values forced in config.php are redacted.
     *
     * @param string $configname
     * @param string $plugin
     * @return bool
     */
    public static function should_redact(string $configname, string $plugin): bool {
        return lib::is_original_wwwroot() &&
            (
                self::is_forced_value($configname, $plugin) ||
                self::is_secret($configname, $plugin) ||
                self::is_secret_like($configname)
            );
    }

    /**
     * Redact a value if the setting is a secret or forced. Empty values are left as is.
     *
     * @param mixed $value
     * @param string $configname
     * @param string $plugin
     * @return mixed
     */
    public static function redact_value($value, string $configname, string $plugin) {
        if ($value === null || $value === false || $value === '') {
            return $value;
        }
        return self::should_redact($configname, $plugin) ? self::REDACTED : $value;
    }

    /**
     * Does the search term match the setting by anything other than its value?
     *
     * Used to prevent the search from being used as an oracle to discover secret values.
     *
     * @param string $search
     * @param array $fields Strings to match against (name, plugin, visible name, description).
     * @return bool - True if the search term matches the setting by anything other than its value, false otherwise.
     */
    private static function matches_non_value(string $search, array $fields): bool {
        $search = \core_text::strtolower($search);
        foreach ($fields as $field) {
            if ($field !== null && strpos(\core_text::strtolower((string) $field), $search) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check that the data cleaner and Environment bar production URLs are both set and equal.
     *
     * The matrix relies on Environment bar to identify production (the Production column), and
     * on the data cleaner production URL for redaction. These must agree.
     *
     * @param bool $html Escape the URLs in the message for HTML output.
     * @return string|null A warning message if there is a problem, null if the configuration is consistent.
     */
    public static function get_production_config_warning(bool $html = false): ?string {
        $original = lib::get_original_wwwroot();
        $envbar = self::environmentbar_exists() ? \local_envbar\local\envbarlib::getprodwwwroot() : '';

        if (empty($original)) {
            $key = 'prodconfig_nooriginalwwwroot';
        } else if (empty($envbar)) {
            $key = 'prodconfig_noenvbarprodwwwroot';
        } else if ($original !== $envbar) {
            $key = 'prodconfig_mismatch';
        } else {
            return null;
        }

        $a = (object) [
            'original' => $html ? s($original) : $original,
            'envbar' => $html ? s($envbar) : $envbar,
        ];
        return get_string($key, 'cleaner_environment_matrix', $a);
    }

    /**
     * Checks to see if environment bar is installed and exists.
     * @return bool
     */
    public static function environmentbar_exists() {
        if (class_exists('\local_envbar\local\envbarlib')) {
            return true;
        }

        return false;
    }

    /**
     * Search the database for the item specified.
     *
     * This uses the admin search feature to obtain the list of configurable items.
     *
     * @param string $search
     * @param array $configitems
     * @return array
     */
    public static function search($search, $configitems = []) {
        global $DB;
        $result = [];

        $adminroot = admin_get_root();
        $findings = $adminroot->search($search);

        foreach ($findings as $found) {
            $settings = $found->settings;

            foreach ($settings as $setting) {
                // Prevent heading types from populating the list. There is nothing to configure.
                if ($setting instanceof admin_setting_heading) {
                    continue;
                }

                $record = new stdClass();

                $record->plugin = (empty($setting->plugin) ? 'core' : $setting->plugin);
                $record->name = $setting->name;

                $redact = self::should_redact($record->name, $record->plugin);

                // The admin search also matches on values, do not reveal that a redacted value matched.
                if (
                    $redact && !self::matches_non_value(
                        $search,
                        [$record->name, $record->plugin, $setting->visiblename, $setting->description]
                    )
                ) {
                    continue;
                }

                $record->value = self::redact_value(get_config($record->plugin, $record->name), $record->name, $record->plugin);

                $record->textarea = false;
                $record->display = true;

                // Identify that this is a text area, during search.
                if (
                    $setting instanceof admin_setting_configtextarea ||
                    $setting instanceof admin_setting_confightmleditor
                ) {
                    $record->textarea = true;
                }

                // Have we passed an array of config items, does the plugin type exist in that array?
                if (array_key_exists($record->plugin, $configitems)) {
                    // Does the config name exist in the type array?
                    if (array_key_exists($record->name, $configitems[$record->plugin])) {
                        // Setting a flag to indicate that we should not show this in the list of found items.
                        $record->display = false;
                    }
                }

                $result[$record->plugin][$record->name] = $record;
            }
        }

        // Get all configs that match the search term from config_plugins.
        $likeplugin = $DB->sql_like('plugin', ':likeplugin');
        $likename = $DB->sql_like('name', ':likename');
        $notlikename = $DB->sql_like('name', ':notlikename', true, true, true);
        $likevalue = $DB->sql_like('value', ':likevalue');
        $nontreefindings = $DB->get_records_sql(
            "SELECT *
               FROM {config_plugins}
              WHERE ({$likeplugin}
                 OR {$likename}
                 OR {$likevalue})
                AND $notlikename",
            [
                'likeplugin' => '%' . $DB->sql_like_escape($search) . '%',
                'likename' => '%' . $DB->sql_like_escape($search) . '%',
                'likevalue' => '%' . $DB->sql_like_escape($search) . '%',
                'notlikename' => 'version',
            ]
        );

        // Add those to the result array as well.
        foreach ($nontreefindings as $setting) {
            $record = new stdClass();
            $record->plugin = (empty($setting->plugin) ? 'core' : $setting->plugin);
            $record->name = $setting->name;

            // Do not reveal that a redacted value matched the search term.
            if (
                self::should_redact($record->name, $record->plugin) &&
                !self::matches_non_value($search, [$record->name, $record->plugin])
            ) {
                continue;
            }

            $value = get_config($record->plugin, $record->name);
            $record->value = self::redact_value($value, $record->name, $record->plugin);
            $record->textarea = false;
            $record->display = true;
            // Make sure we don't overwrite things that have already been added to the result array,
            // as they might have been created as admin_setting_configtextarea or admin_setting_confightmleditor,
            // and we won't be able to tell that here.
            if (empty($result[$record->plugin][$record->name])) {
                $result[$record->plugin][$record->name] = $record;
            }
        }

        return $result;
    }

    /**
     * Obtains the visible list of environments from envbar.
     * @return array
     */
    public static function get_environments() {
        global $CFG;

        self::populate_envbar_environments();

        $records = self::filter_envbar_environments();

        $data = [];

        $prod = new stdClass();
        $prod->id = -1;
        $prod->environment = 'Production';
        $prod->wwwroot = \local_envbar\local\envbarlib::getprodwwwroot();

        // If we are on the production system, apply the production environment to assist with setting config data.
        if ($prod->wwwroot == $CFG->wwwroot) {
            $data = ['-1' => $prod];
        }

        // The Production environment will be the first element of this array.
        if (!empty($records)) {
            return $data + $records;
        }

        return $data;
    }

    /**
     * Populates the list of available environments.
     *
     * @return bool
     */
    public static function populate_envbar_environments() {
        global $DB;

        if (!self::environmentbar_exists()) {
            return false;
        }

        $environments = \local_envbar\local\envbarlib::get_records();

        foreach ($environments as $env) {
            $select = $DB->sql_compare_text('wwwroot') . ' = ' . $DB->sql_compare_text(':wwwroot');
            $params = ['wwwroot' => $env->matchpattern];
            $record = $DB->get_record_select('cleaner_environment_matrix', $select, $params);

            $data = new stdClass();
            $data->environment = $env->showtext;
            $data->wwwroot = $env->matchpattern;

            if (empty($record)) {
                $DB->insert_record('cleaner_environment_matrix', $data);
            } else {
                $data->id = $record->id;
                $DB->update_record('cleaner_environment_matrix', $data);
            }
        }

        return true;
    }

    /**
     * Returns a filtered list of environments that has been configured in Environment bar.
     *
     * When when environments have been deleted in the Environment bar config, do not display them here.
     *
     * @return array|bool
     */
    public static function filter_envbar_environments() {
        global $DB;

        if (!self::environmentbar_exists()) {
            return false;
        }

        $environments = \local_envbar\local\envbarlib::get_records();

        $records = $DB->get_records('cleaner_environment_matrix');

        $display = [];

        foreach ($environments as $env) {
            foreach ($records as $key => $record) {
                if ($record->wwwroot == $env->matchpattern) {
                    $display[$key] = $records[$key];
                }
            }
        }

        // Build the display array from $records.
        return $display;
    }

    /**
     * Obtains the saved matrix values for all or a specified environment.
     *
     * Values for secret or forced settings are redacted in the production column.
     *
     * @param stdClass|null $environment
     * @return array
     */
    public static function get_matrix_data($environment = null) {
        global $DB;

        $data = [];

        $params = [];

        if (!empty($environment)) {
            $params['envid'] = $environment->id;
        }

        $records = $DB->get_records('cleaner_environment_matrixd', $params);

        $isprod = lib::is_original_wwwroot();

        foreach ($records as $record) {
            if ($isprod) {
                // This is a production site.
                // Create a copy of the record that will be displayed in the first column.
                $prodrecord = clone $record;
                $prodrecord->name = $record->config;
                $prodrecord->value = self::redact_value(
                    get_config($record->plugin, $record->config),
                    $record->config,
                    $record->plugin
                );
                $prodrecord->envid = '-1';
                $prodrecord->id = '-1';

                $data[$record->plugin][$record->config]['-1'] = $prodrecord;
            }

            $data[$record->plugin][$record->config][$record->envid] = $record;
        }

        return $data;
    }

    /**
     * Returns true if the config value is forced (defined in config.php).
     *
     * @param string $configname
     * @param string $plugin
     * @return bool
     */
    public static function is_forced_value(string $configname, string $plugin): bool {
        global $CFG;
        if ($plugin == 'core' || empty($plugin)) {
            return array_key_exists($configname, $CFG->config_php_settings);
        } else {
            return array_key_exists($plugin, $CFG->forced_plugin_settings) &&
                   array_key_exists($configname, $CFG->forced_plugin_settings[$plugin]);
        }
    }

    /**
     * During the cleaning process we will purge other configured environments.
     *
     * @param integer $environment
     */
    public static function purge_data_except_environment($environment) {
        global $DB;

        $select = "envid != $environment";

        $params = ['envid' => $environment];

        $DB->delete_records_select('cleaner_environment_matrixd', $select, $params);
    }
}
