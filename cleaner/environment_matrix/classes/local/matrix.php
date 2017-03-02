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

use stdClass;

require_once(__DIR__ . '/../../../../../../config.php');

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

class matrix {
    const MAX_LIMIT = 5;

    public static function environmentbar_exists() {
        if (class_exists('\local_envbar\local\envbarlib')) {
            return true;
        }

        return false;
    }

    public static function search($search, $configitems = []) {
        global $DB;

        $result = [];

        $query = explode(' ', $search);
        if (count($query) == 2) {
            $name = $query[0];
            $plugin = $query[1];
        } else {
            $name = $search;
        }

        $select = $DB->sql_like('name', ':name', false);
        $params = ['name' => '%' . $name . '%'];

        $records = $DB->get_records_select('config', $select, $params, 'name', 'id, name', 0 , self::MAX_LIMIT + 1);

        foreach ($records as $record) {
            if (!array_key_exists($record->name, $configitems)) {
                $record->plugin = 'core';

                // If the plugin is empty then will only append core results.
                if (empty($plugin)) {
                    $result[] = $record;
                }
            }
        }

        // If plugin has been set, we will modify the SQL query to include it.
        if (!empty($plugin)) {
            $select .= ' AND '. $DB->sql_like('plugin', ':plugin', false);
            $params['plugin'] = '%' . $plugin . '%';
        } else {
            // Search for the plugin name or value instead.
            $select .= ' OR '. $DB->sql_like('plugin', ':name2', false);
            $params['name2'] = '%' . $name . '%';
        }

        $records = $DB->get_records_select('config_plugins', $select, $params, 'name', 'id, plugin, name', 0 , self::MAX_LIMIT + 1);

        foreach ($records as $record) {
            if (!array_key_exists($record->name, $configitems)) {
                // If the plugin was specified as a search query, prepend the results to the list as we have a limited set.
                if (!empty($plugin)) {
                    array_unshift($result, $record);
                } else {
                    $result[] = $record;
                }
            }
        }

        return $result;
    }

    public static function get_environments() {
        global $DB;

        self::populate_envbar_environments();

        $records = $DB->get_records('cleaner_env_matrix');

        if (!empty($records)) {
            return $records;
        }

        return [];
    }

    public static function populate_envbar_environments() {
        global $DB;

        if (!self::environmentbar_exists()) {
            return false;
        }

        $environments = \local_envbar\local\envbarlib::get_records();

        foreach ($environments as $env) {
            $select = $DB->sql_compare_text('wwwroot') . ' = ' . $DB->sql_compare_text(':wwwroot');
            $params = ['wwwroot' => $env->matchpattern];
            $record = $DB->get_record_select('cleaner_env_matrix', $select, $params);

            $data = new stdClass();
            $data->environment = $env->showtext;
            $data->wwwroot = $env->matchpattern;

            if (empty($record)) {
                $DB->insert_record('cleaner_env_matrix', $data);
            } else {
                $data->id = $record->id;
                $DB->update_record('cleaner_env_matrix', $data);
            }
        }

        return true;
    }

    public static function get_matrix_data($environment = null) {
        global $DB;

        $data = [];

        $params = [];

        if (!empty($environment)) {
            $params['envid'] = $environment->id;
        }

        $records = $DB->get_records('cleaner_env_matrix_data', $params);

        foreach ($records as $record) {
            $data[$record->config][$record->envid] = $record;
        }

        return $data;
    }
}
