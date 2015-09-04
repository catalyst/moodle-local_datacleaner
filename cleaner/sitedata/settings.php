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
 * @package    cleaner_sitedata
 * @copyright  2015 Catalyst IT
 * @author     Tim Price <timprice@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (!$ADMIN->fulltree) {
    return;
}

require_once($CFG->dirroot . '/local/datacleaner/cleaner/sitedata/classes/supported_file_types.php');

$settings->add(new admin_setting_configcheckbox('cleaner_sitedata/allfiletypes',
        new lang_string('allfiletypes', 'cleaner_sitedata'),
        new lang_string('allfiletypesdesc', 'cleaner_sitedata'), 0));

$file_types = new cleaner_sitedata\cleaner_sitedata_supported_file_types();
$settings->add(new admin_setting_configmultiselect('cleaner_sitedata/filetypes',
        new lang_string('filetypes', 'cleaner_sitedata'),
        new lang_string('filetypesdesc', 'cleaner_sitedata'),
        array(),
        $file_types->get_supported_file_types()));

$settings->add(new admin_setting_configcheckbox('cleaner_sitedata/allcontextlevels',
        new lang_string('allcontextlevels', 'cleaner_sitedata'),
        new lang_string('allcontextlevelsdesc', 'cleaner_sitedata'), 0));

$settings->add(new admin_setting_configmultiselect('cleaner_sitedata/contextlevels',
        new lang_string('contextlevels', 'cleaner_sitedata'),
        new lang_string('contextlevelsdesc', 'cleaner_sitedata'), array(CONTEXT_USER),
        array(CONTEXT_SYSTEM    => 'System',
              CONTEXT_USER      => 'User',
              CONTEXT_COURSECAT => 'Course category',
              CONTEXT_COURSE    => 'Course',
              CONTEXT_MODULE    => 'Module',
              CONTEXT_BLOCK     => 'Block')));
