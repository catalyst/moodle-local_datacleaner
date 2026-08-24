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
 * @package    cleaner_cron
 * @copyright  2026 Catalyst IT
 * @author     Jason den Dulk <jasondendulk@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['deletemetadata'] = 'Clean metadata';
$string['deletemetadatadesc'] = 'Clean cron related metadata, such as locks, fail delay and timestarts.';
$string['pluginname'] = 'Cron cleaner';
$string['privacy:metadata'] = 'The cron cleaner plugin does not store any personal data.';

$string['rescheduletasks'] = 'Reschedule tasks';
$string['rescheduletasksdesc'] = 'Reset the next run time of each task according to their configuration.';

$string['scheduledtasks'] = 'Scheduled tasks';
$string['scheduledtasksdesc'] = 'Select the scheduled tasks to run when performing a clean.';

$string['taskaction'] = 'Task action';
$string['taskaction_immediate'] = 'Run selected tasks immediately';
$string['taskaction_nextcron'] = 'Run selected tasks on next cron';
$string['taskaction_none'] = 'Do nothing';
$string['taskactiondesc'] = 'What to do with the selected scheduled tasks after cleaning.<br/><b>Note:</b> Running tasks imeediately is considered experimental. It may not work, in which case you should select to run at the next cron instead.';
