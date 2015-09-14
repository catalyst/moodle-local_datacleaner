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
 * @package    cleaner_orphaned_sitedata
 * @copyright  2015 Catalyst IT
 * @author     Ghada El-Zoghbi <ghada@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (!$ADMIN->fulltree) {
    return;
}

$settings->add(new admin_setting_configcheckbox('cleaner_orphaned_sitedata/deletebackups',
        new lang_string('deletebackups', 'cleaner_orphaned_sitedata'),
        new lang_string('deletebackupsdesc', 'cleaner_orphaned_sitedata'), 0));

$settings->add(new admin_setting_configcheckbox('cleaner_orphaned_sitedata/deletecachedfiles',
        new lang_string('deletecachedfiles', 'cleaner_orphaned_sitedata'),
        new lang_string('deletecachedfilesdesc', 'cleaner_orphaned_sitedata'), 1));

$settings->add(new admin_setting_configcheckbox('cleaner_orphaned_sitedata/deletetmpfiles',
        new lang_string('deletetmpfiles', 'cleaner_orphaned_sitedata'),
        new lang_string('deletetmpfilesdesc', 'cleaner_orphaned_sitedata'), 1));

$settings->add(new admin_setting_configcheckbox('cleaner_orphaned_sitedata/deleteorphanedfiles',
        new lang_string('deleteorphanedfiles', 'cleaner_orphaned_sitedata'),
        new lang_string('deleteorphanedfilesdesc', 'cleaner_orphaned_sitedata'), 0));

