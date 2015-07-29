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
 * @package    local_datacleaner
 * @copyright  2015 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/tablelib.php');

admin_externalpage_setup('local_datacleaner');

// Allows the admin to configure subplugins (enable/disable, configure).

$hide     = optional_param('hide', '', PARAM_ALPHAEXT);
$show     = optional_param('show', '', PARAM_ALPHAEXT);

// Print headings.

$strmanage = get_string('info');
$strversion = get_string('version');
$strenabledisable = get_string('enabledisable', 'local_datacleaner');
$strenable = get_string('enable', 'local_datacleaner');
$strdisable = get_string('disable', 'local_datacleaner');
$strsettings = get_string('settings');
$strname = get_string('name');

// If data submitted, then process and store.

if ((!empty($hide) || !empty($show)) && confirm_sesskey()) {
    $plugins = core_plugin_manager::instance()->get_plugins_of_type('cleaner');
    $pluginname = empty($hide) ? $show : $hide;
    $state = empty($hide);

    if (!isset($plugins[$pluginname])) {
        print_error('plugindoesnotexist', 'error');
    }
    set_config('enabled', $state, 'cleaner_' . $pluginname);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($strmanage);

// Main display starts here.

$plugins = core_plugin_manager::instance()->get_plugins_of_type('cleaner');

if (!$plugins) {
    print_error('noplugins', 'error');  // Should never happen.
}

// Print the table of all subplugins.

$table = new html_table();
$table->head = array(
    get_string('enabledisable', 'local_datacleaner'),
    get_string('name'),
    get_string('plugin'),
    get_string('priority', 'local_datacleaner'),
    get_string('settings'),
);
$table->attributes['class'] = 'admintable generaltable';
$data = array();

$plugins = core_plugin_manager::instance()->get_plugins_of_type('cleaner');


foreach ($plugins as $plugin) {

    $settings = $plugin->get_settings_section_url();
    if (!is_null($settings)) {
        $settings = html_writer::link('/admin/settings.php?section=' . $settings, $strsettings);
    }

    if ($plugin->enabled()) {
        $visible = '<a href="index.php?hide='.$plugin->name.'&amp;sesskey='.sesskey().'" title="'.$strdisable.'">'.
            '<img src="'.$OUTPUT->pix_url('t/hide') . '" class="iconsmall" alt="'.$strdisable.'" /></a>';
    } else {
        $visible = '<a href="index.php?show='.$plugin->name.'&amp;sesskey='.sesskey().'" title="'.$strenable.'">'.
            '<img src="'.$OUTPUT->pix_url('t/show') . '" class="iconsmall" alt="'.$strenable.'" /></a>';
        $class = 'dimmed_text';
    }

    $row = new html_table_row(array(
                $visible,
                $plugin->displayname,
                $plugin->name,
                $plugin->get_priority(),
                $settings,
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

