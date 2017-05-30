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
 * Environment matrix class.
 *
 * @package    cleaner_environment_matrix
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  2017 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace cleaner_environment_matrix\local;

use admin_setting_confightmleditor;
use admin_setting_configtextarea;
use admin_setting_heading;
use stdClass;

require_once(__DIR__ . '/../../../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

/**
 * Environment matrix class.
 *
 * @package    cleaner_environment_matrix
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  2017 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class matrix {
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

                $record->value = get_config($record->plugin, $setting->name);

                $record->name = $setting->name;

                $record->textarea = false;

                $record->display = true;

                // Identify that this is a text area, during search.
                if ($setting instanceof admin_setting_configtextarea ||
                    $setting instanceof admin_setting_confightmleditor) {
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
     * When when environments have been removed in the Environment bar config, do not display them here.
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

        // $records now contains the tables display
        return $display;

    }

    /**
     * Obtains the saved matrix values for all or a specified environment.
     *
     * @param null $environment
     * @return array
     */
    public static function get_matrix_data($environment = null) {
        global $CFG, $DB;

        $data = [];

        $params = [];

        if (!empty($environment)) {
            $params['envid'] = $environment->id;
        }

        $records = $DB->get_records('cleaner_environment_matrixd', $params);

        foreach ($records as $record) {
            if (envbarlib::getprodwwwroot() === $CFG->wwwroot) {

                // Create a copy of the record that will be displayed in the first column.
                $prodrecord = clone $record;
                $prodrecord->value = get_config($record->plugin, $record->config);
                $prodrecord->envid = '-1';
                $prodrecord->id = '-1';

                $data[$record->plugin][$record->config]['-1'] = $prodrecord;
            }

            $data[$record->plugin][$record->config][$record->envid] = $record;
        }

        return $data;
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
