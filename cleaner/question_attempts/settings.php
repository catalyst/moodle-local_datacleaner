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
 * Settings for the question attempts cleaner.
 *
 * @package    cleaner_question_attempts
 * @copyright  2026 Catalyst IT Canada
 * @author     Artem Garanin <artemgaranin@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (!$ADMIN->fulltree) {
    return;
}

$settings->add(
    new admin_setting_configtext(
        'cleaner_question_attempts/minimumage',
        new lang_string('minimumage', 'cleaner_question_attempts'),
        new lang_string('minimumagedesc', 'cleaner_question_attempts'),
        30,
        PARAM_INT
    )
);
