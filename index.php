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

admin_externalpage_setup('local_datacleaner');

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('pluginname', 'local_datacleaner'));

echo get_string('info', 'local_datacleaner');

// core_plugin_manager->get_plugins_of_type('cleaner');
foreach (core_plugin_manager::instance()->get_plugins_of_type('cleaner') as $plugin) {
    /** @var \editor_atto\plugininfo\atto $plugin */

echo '<h2>' . $plugin->displayname . '</h2>';

echo '<pre>';
echo var_dump( $plugin);
echo '</pre>';
    // $plugin->load_settings($ADMIN, 'editoratto', $hassiteconfig);
}


echo $OUTPUT->footer();

