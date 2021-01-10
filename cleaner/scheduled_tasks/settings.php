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
 * @package    cleaner_scheduled_tasks
 * @subpackage  local_datacleaner
 * @copyright  2019 Catalyst IT
 * @var $ADMIN  admin_root
 */

defined('MOODLE_INTERNAL') || die;

if (!$hassiteconfig) {
    return;
}

// Add the new settings page.
$ADMIN->add('datacleaner', new admin_externalpage(
    'cleaner_scheduled_tasks_settings',
    get_string('pluginname', 'cleaner_scheduled_tasks'),
    new moodle_url('/local/datacleaner/cleaner/scheduled_tasks/index.php')
));
