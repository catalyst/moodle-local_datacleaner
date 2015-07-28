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
 * Version details.
 *
 * @package    local_datacleaner
 * @copyright  2015 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/tablelib.php');

admin_externalpage_setup('local_datacleaner');

// Allows the admin to configure subplugins (enable/disable, configure)

$hide     = optional_param('hide', 0, PARAM_INT);
$show     = optional_param('show', 0, PARAM_INT);

/// Print headings

$strmanage = get_string('info');
$strversion = get_string('version');
$strenabledisable = get_string('enabledisable');
$strsettings = get_string('settings');
$strname = get_string('name');
$strsettings = get_string('settings');

/// If data submitted, then process and store.

if (!empty($hide) && confirm_sesskey()) {
    if (!$block = $DB->get_record('block', array('id'=>$hide))) {
        print_error('blockdoesnotexist', 'error');
    }
    //@TODO $DB->set_field('block', 'visible', '0', array('id'=>$block->id));      // Hide block
    core_plugin_manager::reset_caches();
    admin_get_root(true, false);  // settings not required - only pages
}

if (!empty($show) && confirm_sesskey() ) {
    if (!$block = $DB->get_record('block', array('id'=>$show))) {
        print_error('blockdoesnotexist', 'error');
    }
    $DB->set_field('block', 'visible', '1', array('id'=>$block->id));      // Show block
    core_plugin_manager::reset_caches();
    admin_get_root(true, false);  // settings not required - only pages
}

echo $OUTPUT->header();
echo $OUTPUT->heading($strmanage);

/// Main display starts here

/// Get and sort the existing plugins

$plugins = core_plugin_manager::instance()->get_plugins_of_type('cleaner');

if (!$plugins) {
    print_error('noplugins', 'error');  // Should never happen
}

/// Print the table of all subplugins

$table = new html_table();
$table->head = array(
    get_string('name'),
    get_string('plugin'),
    get_string('settings'),
    get_string('settings'),
);
$table->attributes['class'] = 'admintable generaltable';
$data = array();
foreach (core_plugin_manager::instance()->get_plugins_of_type('cleaner') as $plugin) {

    $settings = $plugin->get_settings_section_url();
    if (!is_null($settings)) {
        $settings = html_writer::link($settings, $strsettings);
    }
    $row = new html_table_row(array(
                $plugin->displayname,
                $plugin->name,
                $settings,
                'Enable / disable',
                // TODO relates to core or plugin?
    ));

    // TODO is plugin refers to a real plugin which is not installed.
    $disabled = false;
    if ($disabled) {
        $row->attributes['class'] = 'disabled';
    }
    $data[] = $row;
}
$table->data = $data;
echo html_writer::table($table);

echo $OUTPUT->footer();


