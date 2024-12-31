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

// Workaround see issue #95.
header('X-XSS-Protection:0');

$PAGE->requires->css('/local/datacleaner/cleaner/environment_matrix/styles.css');
$PAGE->requires->js_call_amd('cleaner_environment_matrix/matrix', 'init');
$PAGE->add_body_class('cleaner_environment_matrix');

// Check to see if the environment bar exists.
if (!class_exists('\local_envbar\local\envbarlib')) {
    echo $OUTPUT->header();
    echo html_writer::tag('h2', get_string('missingenvbar', 'cleaner_environment_matrix'));
    echo $OUTPUT->footer();
    exit;
}

$configitems = \cleaner_environment_matrix\local\matrix::get_matrix_data();
$environments = \cleaner_environment_matrix\local\matrix::get_environments();
$searchitems = [];

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

    $select = $DB->sql_compare_text('config') . ' = ' . $DB->sql_compare_text(':config');
    $select .= ' AND ' . $DB->sql_compare_text('plugin') . ' = ' . $DB->sql_compare_text(':plugin');
    $select .= ' AND envid = :envid';

    $selected = !empty($data->selected) ? $data->selected : [];
    $config = !empty($data->config) ? $data->config : [];

    foreach ($selected as $plugin => $configs) {
        foreach ($configs as $name => $ticked) {

            $envs = [];
            if (!empty($config[$plugin])) {
                if (!empty($config[$plugin][$name])) {

                    // This stores the configuration values for posted environments data.
                    $envs = $config[$plugin][$name];
                }
            }

            // The checkbox has been ticked. Update this field for all environments.
            if ($ticked == '1') {
                foreach ($envs as $envid => $value) {

                    // Do not save the production envid data to the system.
                    if ($envid == '-1') {
                        continue;
                    }

                    $entry = [
                        'plugin' => $plugin,
                        'config' => $name,
                        'envid' => $envid,
                        'value' => $value,
                    ];

                    // Sometimes the element is listed in the search items, lets update the textarea data for this.
                    if (array_key_exists($plugin, $searchitems)) {
                        if (array_key_exists($name, $searchitems[$plugin])) {
                            $entry['textarea'] = $searchitems[$plugin][$name]->textarea;
                        }
                    }

                    $record = $DB->get_record_select('cleaner_environment_matrixd', $select, $entry);

                    if (empty($record)) {
                        $DB->insert_record('cleaner_environment_matrixd', $entry);
                    } else {
                        $entry['id'] = $record->id;
                        $DB->update_record('cleaner_environment_matrixd', $entry);
                    }

                }

                // Else we will reset / delete all the unticked groups.
            } else {
                foreach ($envs as $envid => $value) {

                    $entry = [
                        'plugin' => $plugin,
                        'config' => $name,
                        'envid' => $envid,
                        'value' => $value,
                    ];

                    $record = $DB->get_record_select('cleaner_environment_matrixd', $select, $entry);

                    if (!empty($record)) {
                        $DB->delete_records_select('cleaner_environment_matrixd', $select, $entry);
                    }
                }
            }
        }
    }
}

// After data has been posted, we obtain the list of new values and variables.
$configitems = \cleaner_environment_matrix\local\matrix::get_matrix_data();
$environments = \cleaner_environment_matrix\local\matrix::get_environments();
$searchitems = [];

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
