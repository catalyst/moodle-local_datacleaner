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

$table = new flexible_table('admin-blocks-compatible');

$table->define_columns(array('name', 'version', 'hideshow', 'settings'));
$table->define_headers(array($strname, $strversion, $strenabledisable, $strsettings));
$table->define_baseurl($CFG->wwwroot.'/local/datacleaner/index.php');
$table->set_attribute('class', 'admintable pluginstable generaltable');
$table->set_attribute('id', 'compatibleblockstable');
$table->setup();
$tablerows = array();

// @TODO Sort plugins by priority.
$cleaners = array();
foreach ($plugins as $plugin) {
	//require_once $plugin->typerootdir . '/' . $plugin->name . '/lib.php';
    $pluginclass = '\local_datacleaner\plugininfo\\' . $plugin->name . '_cleaner';
    $cleaners[$plugin->name] = new $pluginclass;
}
core_collator::asort($cleanernames);

foreach ($cleanernames as $pluginid=>$pluginname) {
    $plugin = $plugins[$pluginid];
    $pluginname = $plugin->name;
    $dbversion = $plugin->versiondisk;

    $settings = ''; // By default, no configuration
    if ($plugin and $plugin->has_config()) {
        $pluginsettings = admin_get_root()->locate('pluginsetting' . $plugin->name);

        if ($pluginsettings instanceof admin_externalpage) {
            $settings = '<a href="' . $pluginsettings->url .  '">' . get_string('settings') . '</a>';
        } else if ($pluginsettings instanceof admin_settingpage) {
            $settings = '<a href="'.$CFG->wwwroot.'/'.$CFG->admin.'/settings.php?section=pluginsetting'.$plugin->name.'">'.$strsettings.'</a>';
        } else {
            $settings = '<a href="block.php?block='.$blockid.'">'.$strsettings.'</a>';
        }
    }

    $class = ''; // Nothing fancy, by default

    if (!$blockobject) {
        // ignore
        $visible = '';
    } else if ($plugins[$blockid]->visible) {
        $visible = '<a href="plugins.php?hide='.$blockid.'&amp;sesskey='.sesskey().'" title="'.$strhide.'">'.
            '<img src="'.$OUTPUT->pix_url('t/hide') . '" class="iconsmall" alt="'.$strhide.'" /></a>';
    } else {
        $visible = '<a href="plugins.php?show='.$blockid.'&amp;sesskey='.sesskey().'" title="'.$strshow.'">'.
            '<img src="'.$OUTPUT->pix_url('t/show') . '" class="iconsmall" alt="'.$strshow.'" /></a>';
        $class = 'dimmed_text';
    }

    if ($dbversion == $plugin->version) {
        $version = $dbversion;
    } else {
        $version = "$dbversion ($plugin->version)";
    }

    $row = array(
            $strblockname,
            $version,
            $visible,
            $settings,
            );
    $table->add_data($row, $class);
}

$table->print_html();

echo $OUTPUT->footer();


