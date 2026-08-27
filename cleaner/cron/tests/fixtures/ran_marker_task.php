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

namespace cleaner_cron\task;

/**
 * Scheduled task fixture that records how many times it has been executed.
 *
 * Used by cleaner_cron tests to observe whether a task was actually run.
 *
 * @package    cleaner_cron
 * @copyright  2026 Catalyst IT
 * @author     Jason den Dulk <jasondendulk@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ran_marker_task extends \core\task\scheduled_task {
    /** @var int Number of times execute() has been called this process. */
    public static $timesrun = 0;

    /**
     * Get the task name.
     *
     * @return string
     */
    public function get_name() {
        return 'Ran marker task';
    }

    /**
     * Record that the task ran.
     */
    public function execute() {
        self::$timesrun++;
    }
}
