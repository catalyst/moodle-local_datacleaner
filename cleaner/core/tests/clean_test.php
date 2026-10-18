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

/**
 * Tests for the table selection of the core cleaner.
 *
 * @package    cleaner_core
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \cleaner_core\clean
 */
final class clean_test extends \advanced_testcase {
    /**
     * Run the cleaner in dry run mode and return the listed tables.
     *
     * @return string cleaner output
     */
    private function dry_run(): string {
        new clean(['dryrun' => true, 'verbose' => false]);
        ob_start();
        clean::execute();
        return ob_get_clean();
    }

    /**
     * The sessions table is wiped.
     *
     * @return void
     */
    public function test_sessions_table_is_truncated(): void {
        $this->resetAfterTest();

        $output = $this->dry_run();

        $this->assertMatchesRegularExpression('/- sessions\R/', $output);
    }

    /**
     * The other core tables are still wiped.
     *
     * @return void
     */
    public function test_other_tables_are_still_truncated(): void {
        $this->resetAfterTest();

        $output = $this->dry_run();

        $this->assertMatchesRegularExpression('/- task_adhoc\R/', $output);
        $this->assertMatchesRegularExpression('/- stats_daily\R/', $output);
    }
}
