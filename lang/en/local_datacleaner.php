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
 * @package    local_datacleaner
 * @copyright  2015 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Data cleaner';
$string['cachedef_courses'] = 'Course cache';
$string['cachedef_schema'] = 'Schema cache';
$string['cleaning'] = 'Cleaning';
$string['enabledisable'] = 'Enabled';
$string['disable'] = 'Disable';
$string['enable'] = 'Enable';
$string['error:explicitconfigphp'] = 'Please set the local_datacleaner_allow_execution in config.php';
$string['info'] = '<p>You can configure what and how data will be cleaned after it is cloned from production. </p><p>After the database and sitedata are cloned into another environment a CLI is run which will perform the cleaning. Several checks are performed to ensure that this cannot be run on the production environment.</p>';
$string['manage'] = 'Manage cleaning tasks';
$string['notes'] = 'Notes';
$string['sortorder'] = 'Sort order';
$string['noplugins'] = 'No data cleansing plugins found.';
$string['progress'] = 'Progress';
$string['errordeletingdir'] = '-- ERROR -- An error was encountered while deleting the directory: {$a}';
$string['cascadedeletesettings'] = 'Cascade delete settings';
$string['mismatch_threshold'] = 'Mismatch threshold';
$string['mismatch_thresholddesc'] = 'The data cleaner uses a heuristic to create cascade delete rules in the database that
aren\'t normally there. Prior to creating a rule, it checks how many records would violate the potential relationship. This setting
controls the threshold above which the relationship will not be created (which also means records in the target table will not be
deleted). The threshold is expressed as a percentage of the total number of records involved. If the total number of records in a
 table is less than 100, this value is ignored and any conflicts cause the rule not to be created.';
