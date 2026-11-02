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

namespace cleaner_core;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the core cleaner.
 *
 * @package   cleaner_core
 * @copyright 2026 ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(clean::class)]
final class core_clean_test extends \advanced_testcase {
    /**
     * Create one row in a backup table and one in a tool_monitor table.
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();
        $this->resetAfterTest();
        set_config('deletemucfile', 0, 'cleaner_core');

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $DB->insert_record('backup_courses', (object) ['courseid' => $course->id, 'laststarttime' => time()]);
        $DB->insert_record('tool_monitor_subscriptions', (object) [
            'courseid' => $course->id,
            'ruleid' => 1,
            'cmid' => 0,
            'userid' => $user->id,
            'timecreated' => time(),
        ]);
    }

    /**
     * Function execute() should empty the backup and tool_monitor tables.
     */
    public function test_execute_empties_backup_and_tool_monitor_tables(): void {
        global $DB;

        new clean(['dryrun' => false, 'verbose' => false]);
        ob_start();
        clean::execute();
        ob_end_clean();

        $this->assertSame(0, $DB->count_records('backup_courses'));
        $this->assertSame(0, $DB->count_records('tool_monitor_subscriptions'));
    }

    /**
     * Function execute() in dry run mode should list the tables but keep their rows.
     */
    public function test_execute_dryrun_lists_tables_and_keeps_rows(): void {
        global $DB;

        new clean(['dryrun' => true, 'verbose' => false]);
        ob_start();
        clean::execute();
        $output = ob_get_clean();

        $this->assertStringContainsString('- backup_courses', $output);
        $this->assertStringContainsString('- tool_monitor_subscriptions', $output);
        $this->assertSame(1, $DB->count_records('backup_courses'));
        $this->assertSame(1, $DB->count_records('tool_monitor_subscriptions'));
    }
}
