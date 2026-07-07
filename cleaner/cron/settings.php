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
 * Settings for the cron cleaner.
 *
 * @package    cleaner_cron
 * @copyright  2026 Catalyst IT
 * @author     Jason den Dulk <jasondendulk@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (!$ADMIN->fulltree) {
    return;
}

$settings->add(
    new admin_setting_configcheckbox(
        'cleaner_cron/deletemetadata',
        new lang_string('deletemetadata', 'cleaner_cron'),
        new lang_string('deletemetadatadesc', 'cleaner_cron'),
        1
    )
);

$settings->add(
    new admin_setting_configcheckbox(
        'cleaner_cron/rescheduletasks',
        new lang_string('rescheduletasks', 'cleaner_cron'),
        new lang_string('rescheduletasksdesc', 'cleaner_cron'),
        1
    )
);

// Build the option list from the labels of all scheduled tasks, keyed by class name.
$taskoptions = [];
foreach (\core\task\manager::get_all_scheduled_tasks() as $task) {
    $taskoptions[\core\task\manager::get_canonical_class_name($task)] = $task->get_name();
}
asort($taskoptions);

$settings->add(
    new admin_setting_configmultiselect(
        'cleaner_cron/scheduledtasks',
        new lang_string('scheduledtasks', 'cleaner_cron'),
        new lang_string('scheduledtasksdesc', 'cleaner_cron'),
        [],
        $taskoptions
    )
);

$settings->add(
    new admin_setting_configselect(
        'cleaner_cron/taskaction',
        new lang_string('taskaction', 'cleaner_cron'),
        new lang_string('taskactiondesc', 'cleaner_cron'),
        \cleaner_cron\clean::TASKACTION_NONE,
        [
            \cleaner_cron\clean::TASKACTION_NONE => new lang_string('taskaction_none', 'cleaner_cron'),
            \cleaner_cron\clean::TASKACTION_IMMEDIATE => new lang_string('taskaction_immediate', 'cleaner_cron'),
            \cleaner_cron\clean::TASKACTION_NEXTCRON => new lang_string('taskaction_nextcron', 'cleaner_cron'),
        ]
    )
);
