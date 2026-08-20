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
 * Language strings
 *
 * @package    cleaner_replace_urls
 * @copyright  2015 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['cleanconfig'] = 'Replace in config tables';
$string['cleanconfigdesc'] = 'This will enable replacing URLs in the config and config_plugins tables.';
$string['cleantext'] = 'Replace in database fields text / varchar';
$string['cleantextdesc'] = 'This will enable replacing URLs in all database columns of type text and varchar.';
$string['cleanwysiwyg'] = 'Replace in wysiwyg elements';
$string['cleanwysiwygdesc'] = 'This will enable replacing URLs in all rich text editor fields.';
$string['cleantext'] = 'Relace in database fields text / varchar';
$string['cleantextdesc'] = 'This will enable replacing URLs in all database columns of type text and varchar.';
$string['newsiteurl'] = 'New site URL';
$string['newsiteurldesc'] = 'The URL of the datacleansed site. Leave blank to use the current wwwroot.';
$string['origsiteurl'] = 'Original site URL';
$string['origsiteurldesc'] = 'The URL of the production site. Leave blank to use the local_envbar production URL if available.';
$string['pluginname'] = 'Replace URLs';
$string['privacy:metadata'] = 'The cleaner replace urls plugin does not store any personal data.';
$string['skiptables'] = 'Tables to skip';
$string['skiptablesdesc'] = 'Names of tables to skip, separated by a comma. E.g. user, log, config.';
$string['urlsfromenvbar'] = 'local_envbar is installed. The production URL (<strong>{$a}</strong>) and current wwwroot will be used automatically if the fields below are left blank.';
