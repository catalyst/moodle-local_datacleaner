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
 * Settings for the Email cleaner.
 *
 * @package    cleaner_email
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  2017 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

if (!$hassiteconfig) { // Needs this condition or there is error on login page.
    return;
}

$settings->add(new admin_setting_configcheckbox('cleaner_email/noemailever',
    new lang_string('noemailever', 'cleaner_email'),
    new lang_string('noemaileverdesc', 'cleaner_email'), 0));

$settings->add(new admin_setting_configtext('cleaner_email/divertallemailsto',
    new lang_string('divertallemailsto', 'cleaner_email'),
    new lang_string('divertallemailstodesc', 'cleaner_email'), ''));

$settings->add(new admin_setting_configtext('cleaner_email/divertallemailsexcept',
    new lang_string('divertallemailsexcept', 'cleaner_email'),
    new lang_string('divertallemailsexceptdesc', 'cleaner_email'), ''));

$settings->add(new admin_setting_configtext('cleaner_email/emailsuffix',
    new lang_string('emailsuffix', 'cleaner_email'),
    new lang_string('emailsuffixdesc', 'cleaner_email'), '.invalid'));

$settings->add(new admin_setting_configtext('cleaner_email/emailsuffixignore',
    new lang_string('emailsuffixignore', 'cleaner_email'),
    new lang_string('emailsuffixignoredesc', 'cleaner_email'), ''));