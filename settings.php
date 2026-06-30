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
 * Add page to admin menu.
 *
 * @package    local_datacleaner
 * @copyright  2015 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (!$hassiteconfig) { // Needs this condition or there is error on login page.
    return;
}

$ADMIN->add('localplugins', new admin_category('datacleaner', get_string('pluginname', 'local_datacleaner')));

$ADMIN->add(
    'datacleaner',
    new admin_externalpage(
        'local_datacleaner',
        get_string('manage', 'local_datacleaner'),
        new moodle_url('/local/datacleaner/index.php')
    )
);


$general = new admin_settingpage('local_datacleaner_generalsettings', new lang_string('generalsettings', 'local_datacleaner'));

$general->add(
    new \local_datacleaner\admin_setting_configencodedtext(
        'local_datacleaner/original_wwwroot',
        new lang_string('original_wwwroot', 'local_datacleaner'),
        new lang_string('original_wwwrootdesc', 'local_datacleaner'),
        $CFG->wwwroot,
        PARAM_URL
    )
);

$general->add(
    new admin_setting_configtext(
        'local_datacleaner/mismatch_threshold',
        new lang_string('mismatch_threshold', 'local_datacleaner'),
        new lang_string('mismatch_thresholddesc', 'local_datacleaner'),
        '5',
        PARAM_INT
    )
);
$ADMIN->add('datacleaner', $general);

require_once(__DIR__ . '/classes/admin_setting_sql_textarea.php');

$plugins = \local_datacleaner\plugininfo\cleaner::get_plugins_by_sortorder();
foreach ($plugins as $plugin) {
    $pagename = 'cleaner_' . $plugin->name . '_settings';
    $plugin->load_settings($ADMIN, 'datacleaner', $hassiteconfig);
}
