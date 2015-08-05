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
 * @package    cleaner_delete_users
 * @copyright  2015 Catalyst IT
 * @author     Nigel Cunningham <nigelc@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (!$ADMIN->fulltree) {
    return;
}

$settings->add(new admin_setting_configtext('cleaner_delete_users/minimumage',
            new lang_string('minimumage', 'cleaner_delete_users'),
            new lang_string('minimumagedesc', 'cleaner_delete_users'), 365, PARAM_INT));

$settings->add(new admin_setting_configcheckbox('cleaner_delete_users/keepsiteadmins',
            new lang_string('keepsiteadmins', 'cleaner_delete_users'),
            new lang_string('keepsiteadminsdesc', 'cleaner_delete_users'), 1));

$settings->add(new admin_setting_configtext('cleaner_delete_users/keepuids',
            new lang_string('keepuids', 'cleaner_delete_users'),
            new lang_string('keepuidsdesc', 'cleaner_delete_users'), '', PARAM_SEQUENCE));
