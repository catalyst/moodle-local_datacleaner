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
 * @package    cleaner_tokens
 * @copyright  2026 Catalyst IT
 * @author     Jason den Dulk <jasondendulk@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['fieldstoregenerate'] = 'Fields to regenerate';
$string['fieldstoregeneratedesc'] = 'Put any fields (one per line) that you want to be regenerated. Format: tablename:fieldname[:length]. These fields will be replaced by a randomly generated string. If set, the value will be limited to the given length.';
$string['fieldstorehash'] = 'Fields to hash';
$string['fieldstorehashdesc'] = 'Put any fields (one per line) that you want to be hashed. Format: tablename:fieldname[:length]. These fields will be deterministicly rehashed using the seed below. If set, the value will be limited to the given length.';
$string['invalid_field_format'] = 'Invalid field format. Must be in the format tablename:fieldname[:length]';
$string['pluginname'] = 'Token cleaner';
$string['privacy:metadata'] = 'The token cleaner plugin does not store any personal data.';
$string['rehashseed'] = 'Rehash seed';
$string['rehashseeddesc'] = 'The seed to use when rehashing fields. If zero, then the rehash will be unseeded.';
$string['tablestotruncate'] = 'Tables to truncate';
$string['tablestotruncatedesc'] = 'Put any tables (one per line) that you want to be truncated.';
