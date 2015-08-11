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

$ADMIN->add('datacleaner',
        new admin_externalpage('local_datacleaner',
            get_string('manage', 'local_datacleaner'),
            new moodle_url('/local/datacleaner/index.php')));

$plugins = \local_datacleaner\plugininfo\cleaner::get_enabled_plugins_by_sortorder();
foreach ($plugins as $plugin) {

    $pagename = 'cleaner_' . $plugin->name . '_settings';
    $plugin->load_settings($ADMIN, 'datacleaner', $hassiteconfig);
}

