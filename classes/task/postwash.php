<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_datacleaner\task;

use local_datacleaner\clean;
use local_datacleaner\plugininfo\cleaner;

/**
 * Class for the postwash scheduled task.
 *
 * @package    local_datacleaner
 * @copyright  2026 Abhinav Gandham <abhinavgandham@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class postwash extends \core\task\scheduled_task {
    /**
     * Gets the name of the task.
     *
     * @return string The task name.
     */
    public function get_name() {
        return get_string('scheduledstaskpostwash', 'local_datacleaner');
    }

    /**
     * Executes the task.
     *
     * @return void
     */
    public function execute() {
        global $CFG;

        require_once($CFG->dirroot . '/local/datacleaner/cli/lib.php');

        // Run safety checks to ensure we are not running on production.
        try {
            safety_checks(false, true);
        } catch (\Exception $error) {
            mtrace($error->getMessage());
            throw $error;
        }

        clean::debug_info();
        mtrace(get_string('postwashrunning', 'local_datacleaner'));
        $plugins = cleaner::get_enabled_plugins_by_sortorder();
        clean::run_wash($plugins, ['run-post-wash' => true, 'verbose' => true]);
        mtrace(get_string('postwashcompleted', 'local_datacleaner'));

        // If the setting has since been turned off, this is the last scheduled run.
        if (!get_config('local_datacleaner', 'enable_postwash')) {
            $this->disable();
        }
    }
}
