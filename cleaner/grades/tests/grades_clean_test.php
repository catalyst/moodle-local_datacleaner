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

namespace cleaner_grades;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the grades cleaner.
 *
 * @package   cleaner_grades
 * @copyright 2026 Catalyst IT
 * @author    Jason den Dulk <jasondendulk@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(clean::class)]
class grades_clean_test extends \advanced_testcase {
    /**
     * Reset the cleaner options and test data before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        new clean(['dryrun' => false, 'verbose' => false]);
    }

    /**
     * Function execute() should replace grades in both grade tables, including records
     * whose maximum grade is zero.
     */
    public function test_execute_scrambles_grades_and_history(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        foreach ([10, 0] as $rawgrademax) {
            $gradeitem = $this->getDataGenerator()->create_grade_item(['courseid' => $course->id]);
            $gradeid = $DB->insert_record('grade_grades', (object)[
                'itemid' => $gradeitem->id,
                'userid' => $user->id,
                'rawgrade' => 7,
                'rawgrademax' => $rawgrademax,
                'rawgrademin' => 0,
                'finalgrade' => 8,
            ]);

            $DB->insert_record('grade_grades_history', (object)[
                'action' => 1,
                'oldid' => $gradeid,
                'itemid' => $gradeitem->id,
                'userid' => $user->id,
                'rawgrade' => 7,
                'rawgrademax' => $rawgrademax,
                'rawgrademin' => 0,
                'finalgrade' => 8,
            ]);
        }

        set_config('deleteall', 0, 'cleaner_grades');
        clean::execute();

        $grades = $DB->get_records('grade_grades', null, 'id ASC');
        $history = $DB->get_records('grade_grades_history', null, 'id ASC');

        $this->assertCount(2, $grades);
        $this->assertCount(2, $history);

        $gradevalues = array_values($grades);
        $historyvalues = array_values($history);
        $this->assertEquals((int) $gradevalues[0]->id % 10, (int) $gradevalues[0]->rawgrade);
        $this->assertEquals((int) $gradevalues[0]->id % 10, (int) $gradevalues[0]->finalgrade);
        $this->assertSame(0.0, (float) $gradevalues[1]->rawgrade);
        $this->assertSame(0.0, (float) $gradevalues[1]->finalgrade);
        $this->assertEquals((int) $historyvalues[0]->id % 10, (int) $historyvalues[0]->rawgrade);
        $this->assertEquals((int) $historyvalues[0]->id % 10, (int) $historyvalues[0]->finalgrade);
        $this->assertSame(0.0, (float) $historyvalues[1]->rawgrade);
        $this->assertSame(0.0, (float) $historyvalues[1]->finalgrade);
    }

    /**
     * Function execute() should delete all records when configured to do so.
     */
    public function test_execute_deletes_all_grades_when_configured(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $gradeitem = $this->getDataGenerator()->create_grade_item(['courseid' => $course->id]);
        $gradeid = $DB->insert_record('grade_grades', (object) [
            'itemid' => $gradeitem->id,
            'userid' => $user->id,
            'rawgrademax' => 100,
            'rawgrademin' => 0,
        ]);
        $DB->insert_record('grade_grades_history', (object) [
            'action' => 1,
            'oldid' => $gradeid,
            'itemid' => $gradeitem->id,
            'userid' => $user->id,
            'rawgrademax' => 100,
            'rawgrademin' => 0,
        ]);

        set_config('deleteall', 1, 'cleaner_grades');

        clean::execute();

        $this->assertSame(0, $DB->count_records('grade_grades'));
        $this->assertSame(0, $DB->count_records('grade_grades_history'));
    }
}
