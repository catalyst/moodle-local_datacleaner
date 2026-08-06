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

use local_datacleaner\task\postwash;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the postwash scheduled task.
 *
 * @package    local_datacleaner
 * @copyright  2026 Abhinav Gandham <abhinavgandham@catalyst-au.net>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(postwash::class)]
final class postwash_task_test extends advanced_testcase {
    /**
     * Tests that the get_name() task method returns the expected string containing the task name.
     */
    public function test_get_name(): void {
        $task = new postwash();
        $this->assertEquals(get_string('scheduledstaskpostwash', 'local_datacleaner'), $task->get_name());
    }

    /**
     * Tests that the postwash scheduled task is disabled after task execution if the enable postwash config setting is turned off.
     */
    public function test_enable_postwash_off(): void {
        global $CFG;

        $this->resetAfterTest(true);
        $task = new postwash();

        // Simulate running on a clone of production, so safety_checks pass.
        set_config('original_wwwroot', base64_encode('https://prod.example.com'));
        $CFG->config_php_settings['local_datacleaner_allowexecution'] = true;

        // Disable the postwash task and execute the task.
        set_config('enable_postwash', 0, 'local_datacleaner');

        ob_start();
        $task->execute();
        ob_end_clean();

        // Assert that debug output was generated.
        $this->assertDebuggingCalled();

        // Check that the postwash scheduled task is still disabled.
        $freshtask = \core\task\manager::get_scheduled_task('local_datacleaner\task\postwash');
        $this->assertEquals(1, $freshtask->get_disabled());
    }

    /**
     * Tests that the postwash scheduled task is enabled after task execution if the enable postwash config setting is turned on.
     */
    public function test_enable_postwash_on(): void {
        global $CFG;

        $this->resetAfterTest(true);
        $task = new postwash();

        // Simulate running on a clone of production, so safety_checks pass.
        set_config('original_wwwroot', base64_encode('https://prod.example.com'));
        $CFG->config_php_settings['local_datacleaner_allowexecution'] = true;

        // Enable the postwash task and execute the task.
        set_config('enable_postwash', 1, 'local_datacleaner');

        ob_start();
        $task->execute();
        ob_end_clean();

        // Assert that debug output was generated.
        $this->assertDebuggingCalled();

        // Check that the postwash scheduled task is still enabled.
        $freshtask = \core\task\manager::get_scheduled_task('local_datacleaner\task\postwash');
        $this->assertEquals(0, $freshtask->get_disabled());
    }
}
