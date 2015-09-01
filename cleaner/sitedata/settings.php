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

$settings->add(new admin_setting_configcheckbox('cleaner_sitedata/replaceall',
        new lang_string('replaceall', 'cleaner_sitedata'),
        new lang_string('replacealldesc', 'cleaner_sitedata'), 0));

$settings->add(new admin_setting_configmultiselect('cleaner_sitedata/filetypes',
        new lang_string('filetypes', 'cleaner_sitedata'),
        new lang_string('filetypesdesc', 'cleaner_sitedata'), array(0),
        array('7z', 'avi', 'bz', 'bz2', 'css', 'csv', 'doc', 'docx', 'flv', 'gif', 'gtar',
                'gz', 'htm', 'html', 'jpeg', 'jpg', 'js', 'mov', 'mp3', 'mp4', 'odb', 'odc',
                'odf', 'odg', 'odi', 'odm', 'odp', 'ods', 'odt', 'pdf', 'png', 'ppt', 'pptx',
                'rar', 'rtf', 'swf', 'tar', 'txt', 'wmv', 'xls', 'xlsx', 'xml', 'zip')));
        // These will need to be converted to the associated mimetype.

