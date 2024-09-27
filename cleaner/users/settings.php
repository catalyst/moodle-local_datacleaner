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
 * @package    cleaner_courses
 * @copyright  2015 Catalyst IT
 * @author     Nigel Cunningham <nigelc@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (!$ADMIN->fulltree) {
    return;
}

$settings->add(new admin_setting_configcheckbox('cleaner_users/keepsiteadmins', new lang_string('keepsiteadmins', 'cleaner_users'),
            new lang_string('keepsiteadminsdesc', 'cleaner_users'), 1));

$settings->add(new admin_setting_configtextarea('cleaner_users/keepusernames',
            new lang_string('keepusernames', 'cleaner_users'),
            new lang_string('keepusernamesdesc', 'cleaner_users'), '', PARAM_RAW));

$settings->add(new admin_setting_configcheckbox('cleaner_users/renameusers', new lang_string('renameusers', 'cleaner_users'),
            new lang_string('renameusersdesc', 'cleaner_users'), 0));
