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
 * Settings for Environment matrix.
 *
 * @package    cleaner_environment_matrix
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  2017 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('cleaner_environment_matrix');

global $DB;

$configitems = \cleaner_environment_matrix\local\matrix::get_matrix_data();
$environments = \cleaner_environment_matrix\local\matrix::get_environments();
$searchitems = [];

// Lookup possible {config} table entries.
$search = optional_param('search', null, PARAM_TEXT);
if (!empty($search)) {
    $searchitems = \cleaner_environment_matrix\local\matrix::search($search, $configitems);
}

$customdata = [
    'searchitems' => $searchitems,
    'configitems' => $configitems,
    'environments' => $environments,
];

$post = new moodle_url('/local/datacleaner/cleaner/environment_matrix/index.php');
$matrix = new \cleaner_environment_matrix\form\matrix($post, $customdata);

// We have created the form with the correct fields and data, but we don't want to display this one.
if ($matrix->is_cancelled()) {
    redirect($post);
} else if ($data = $matrix->get_data()) {
    // Find which items are are enabled or disabled.
    $enabled = [];
    $disabled = [];
    foreach ($data as $key => $item) {
        preg_match('/enable_[sc]_(.*)/', $key, $match);

        // Checking for enabled.
        if (!empty($match) && $item == 1) {
            $config = $match[count($match) - 1];
            $enabled[] = $config;
            // Checking for disabled.
        } else if (!empty($match) && $item == 0) {
            $config = $match[count($match) - 1];
            $disabled[] = $config;
        }
    }

    // Find items that have been set.
    foreach ($data as $key => $item) {
        preg_match('/[search|config]_(\d*)_(.*)/', $key, $match);

        // Search for the $config name in the list of setting with an enabled checkbox.
        if (!empty($match)) {
            $config = $match[count($match) - 1];
            $envid = $match[count($match) - 2];

            $entry = new stdClass();
            $entry->envid = $envid;
            $entry->config = $config;
            $entry->value = $item;

            $select = 'envid = :envid AND ' . $DB->sql_compare_text('config') . ' = ' . $DB->sql_compare_text(':config');
            $params = ['config' => $config, 'envid' => $envid];
            $record = $DB->get_record_select('cleaner_env_matrix_data', $select, $params);

            if (in_array($match[count($match) - 1], $enabled)) {
                if (empty($record)) {
                    $DB->insert_record('cleaner_env_matrix_data', $entry);
                } else {
                    $entry->id = $record->id;
                    $DB->update_record('cleaner_env_matrix_data', $entry);
                }

            } else if (in_array($match[count($match) - 1], $disabled)) {
                $select = $DB->sql_compare_text('config') . ' = ' . $DB->sql_compare_text(':config');
                $params = ['config' => $config];
                $DB->delete_records_select('cleaner_env_matrix_data', $select, $params);
            }

        }
    }

}

$configitems = \cleaner_environment_matrix\local\matrix::get_matrix_data();
$environments = \cleaner_environment_matrix\local\matrix::get_environments();
$searchitems = [];

// Lookup possible {config} table entries.
$search = optional_param('search', null, PARAM_TEXT);
if (!empty($search)) {
    $searchitems = \cleaner_environment_matrix\local\matrix::search($search, $configitems);
}

$customdata = [
    'searchitems' => $searchitems,
    'configitems' => $configitems,
    'environments' => $environments,
];

$post = new moodle_url('/local/datacleaner/cleaner/environment_matrix/index.php');
$matrix = new \cleaner_environment_matrix\form\matrix($post, $customdata);

echo $OUTPUT->header();
$matrix->display();
echo $OUTPUT->footer();
