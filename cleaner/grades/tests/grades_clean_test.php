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

/**
 * Unit tests for the grades cleaner.
 *
 * @package   cleaner_grades
 * @copyright 2026 Catalyst IT
 * @author    Jason den Dulk <jasondendulk@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \cleaner_grades\clean
 */
final class grades_clean_test extends \advanced_testcase {
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

    /**
     * Create a grade and a grade history record with feedback and information text.
     *
     * @return int The id of the user the grade belongs to.
     */
    private function create_grade_with_feedback(): int {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $gradeitem = $this->getDataGenerator()->create_grade_item(['courseid' => $course->id]);
        $grade = (object) [
            'itemid' => $gradeitem->id,
            'userid' => $user->id,
            'rawgrade' => 7,
            'rawgrademax' => 10,
            'rawgrademin' => 0,
            'finalgrade' => 8,
            'feedback' => 'Personal feedback',
            'information' => 'Personal information',
        ];
        $grade->id = $DB->insert_record('grade_grades', $grade);
        $grade->action = 1;
        $grade->oldid = $grade->id;
        unset($grade->id);
        $DB->insert_record('grade_grades_history', $grade);

        return $user->id;
    }

    /**
     * Function execute() should clear the feedback and information text in both grade tables.
     */
    public function test_execute_clears_feedback_and_information(): void {
        global $DB;

        $userid = $this->create_grade_with_feedback();
        set_config('deleteall', 0, 'cleaner_grades');

        clean::execute();

        foreach (['grade_grades', 'grade_grades_history'] as $table) {
            $record = $DB->get_record($table, ['userid' => $userid], '*', MUST_EXIST);
            $this->assertNull($record->feedback);
            $this->assertNull($record->information);
        }
    }

    /**
     * Function execute() in dry run mode should announce clearing the text but keep it.
     */
    public function test_execute_dryrun_keeps_feedback_and_information(): void {
        global $DB;

        $userid = $this->create_grade_with_feedback();
        set_config('deleteall', 0, 'cleaner_grades');
        new clean(['dryrun' => true, 'verbose' => false]);

        ob_start();
        clean::execute();
        $output = ob_get_clean();

        $this->assertStringContainsString('Would clear the feedback and information fields', $output);
        foreach (['grade_grades', 'grade_grades_history'] as $table) {
            $record = $DB->get_record($table, ['userid' => $userid], '*', MUST_EXIST);
            $this->assertSame('Personal feedback', $record->feedback);
            $this->assertSame('Personal information', $record->information);
        }
    }

    /**
     * Create a grade and a grade history record with a negative maximum grade.
     */
    private function create_negative_range_grade(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $gradeitem = $this->getDataGenerator()->create_grade_item(['courseid' => $course->id]);
        $grade = (object) [
            'itemid' => $gradeitem->id,
            'userid' => $user->id,
            'rawgrade' => -10,
            'rawgrademax' => -5,
            'rawgrademin' => -20,
            'finalgrade' => -12,
        ];
        $grade->id = $DB->insert_record('grade_grades', $grade);
        $grade->action = 1;
        $grade->oldid = $grade->id;
        unset($grade->id);
        $DB->insert_record('grade_grades_history', $grade);
    }

    /**
     * Function execute() should also replace grades whose maximum grade is negative.
     */
    public function test_execute_scrambles_negative_rawgrademax(): void {
        global $DB;

        $this->create_negative_range_grade();
        set_config('deleteall', 0, 'cleaner_grades');

        clean::execute();

        foreach (['grade_grades', 'grade_grades_history'] as $table) {
            $record = $DB->get_record($table, [], '*', MUST_EXIST);
            $this->assertEquals((int) $record->id % 5, (int) $record->rawgrade);
            $this->assertEquals((int) $record->id % 5, (int) $record->finalgrade);
        }
    }

    /**
     * Function execute() in dry run mode should count grades with a negative maximum but keep them.
     */
    public function test_execute_dryrun_counts_negative_rawgrademax(): void {
        global $DB;

        $this->create_negative_range_grade();
        set_config('deleteall', 0, 'cleaner_grades');
        new clean(['dryrun' => true, 'verbose' => false]);

        ob_start();
        clean::execute();
        $output = ob_get_clean();

        $this->assertStringContainsString('Would update 2 fields on 2 records', $output);
        foreach (['grade_grades', 'grade_grades_history'] as $table) {
            $record = $DB->get_record($table, [], '*', MUST_EXIST);
            $this->assertEquals(-10, (int) $record->rawgrade);
            $this->assertEquals(-12, (int) $record->finalgrade);
        }
    }
}
