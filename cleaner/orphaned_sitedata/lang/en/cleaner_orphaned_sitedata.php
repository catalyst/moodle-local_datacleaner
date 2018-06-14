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
 * @package    cleaner_orphaned_sitedata
 * @author     Ghada El-Zoghbi <ghada@catalyst-au.net>
 * @copyright  2015 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Cleanup orphaned sitedata';
$string['privacy:metadata'] = 'The cleaner orphaned sitedata plugin does not store any personal data.';

// Settings page.
$string['deletebackups'] = 'Delete backups';
$string['deletebackupsdesc'] = 'Delete all backups (orphaned or not) from site data and the files table.';

$string['deleteorphanedfiles'] = 'Delete orphaned files';
$string['deleteorphanedfilesdesc'] = 'Delete all orphaned files in site data that are no longer referenced in the database.';

$string['deletetmpfiles'] = 'Delete temporary files';
$string['deletetmpfilesdesc'] = 'Delete all temporary files.';

$string['deletecachedfiles'] = 'Delete cached files';
$string['deletecachedfilesdesc'] = 'Delete all cached files.';

$string['woulddeletebackups'] = 'Would delete {$a} backup file(s).';
$string['willdeletebackups'] = 'Will delete {$a} backup file(s).';

$string['wouldpurgecache'] = 'Would purge the cache.';
$string['willpurgecache'] = 'Will purge the cache.';

$string['woulddeletetemp'] = 'Would delete temporary files from: {$a}';
$string['willdeletetemp'] = 'Will delete temporary files: {$a}';

$string['woulddeleteorphanedfiles'] = 'Would delete orphaned file(s).';
$string['willdeleteorphanedfiles'] = 'Will delete orphaned file(s).';

$string['errordeletingfile'] = "ERROR\n\n*** Cound not delete: {\$a}\n\n";
