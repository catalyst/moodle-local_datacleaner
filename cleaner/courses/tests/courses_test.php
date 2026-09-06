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

namespace cleaner_courses;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit test for the courses cleaner.
 *
 * @package cleaner_courses
 * @copyright  2026 Catalyst IT
 * @author     Jason den Dulk <jasondendulk@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(clean::class)]
final class courses_test extends \advanced_testcase {
    /**
     * Reset cleaner options before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        new clean(['dryrun' => false, 'verbose' => false]);
    }

    /**
     * The constructor should mark the cleaner as requiring cascade deletion
     * when configured courses match the configured criteria.
     */
    public function test_constructor_sets_cascade_delete_for_matching_courses(): void {
        $course = $this->getDataGenerator()->create_course(['shortname' => 'remove-me']);
        set_config('minimumage', 0, 'cleaner_courses');

        $cleaner = new clean();
        $this->assertTrue($cleaner->needs_cascade_delete());

        // Add a course to not delete.
        set_config('courses', $course->shortname, 'cleaner_courses');
        $cleaner = new clean();
        $this->assertFalse($cleaner->needs_cascade_delete());
    }

    /**
     * Function delete_courses() should remove the selected courses.
     */
    public function test_delete_courses_deletes_selected_courses(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $this->assertTrue($DB->record_exists('course', ['id' => $course->id]));

        clean::delete_courses([$course->id => $course->id]);

        $this->assertFalse($DB->record_exists('course', ['id' => $course->id]));
    }

    /**
     * Function delete_courses() should not modify records during a dry run.
     */
    public function test_delete_courses_does_not_delete_during_dry_run(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        new clean(['dryrun' => true]);

        ob_start();
        clean::delete_courses([$course->id => $course->id]);
        $output = ob_get_clean();

        $this->assertTrue($DB->record_exists('course', ['id' => $course->id]));
        $this->assertStringContainsString('Would delete 1 courses', $output);
    }

    /**
     * Function delete_dangling_course_contexts() should remove course contexts without
     * a matching course while preserving valid course contexts.
     */
    public function test_delete_dangling_course_contexts(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $validcontext = \context_course::instance($course->id);
        $danglingcontextid = $DB->insert_record('context', (object) [
            'contextlevel' => CONTEXT_COURSE,
            'instanceid' => 987654321,
            'path' => null,
            'depth' => 0,
            'locked' => 0,
        ]);

        $this->assertTrue($DB->record_exists('context', ['id' => $danglingcontextid]));

        clean::delete_dangling_course_contexts();

        $this->assertFalse($DB->record_exists('context', ['id' => $danglingcontextid]));
        $this->assertTrue($DB->record_exists('context', ['id' => $validcontext->id]));
    }

    /**
     * Function delete_dangling_course_contexts() should not remove any course contexts during a
     * dry run.
     */
    public function test_delete_dangling_course_contexts_in_dryrun(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $validcontext = \context_course::instance($course->id);
        $danglingcontextid = $DB->insert_record('context', (object) [
            'contextlevel' => CONTEXT_COURSE,
            'instanceid' => 987654321,
            'path' => null,
            'depth' => 0,
            'locked' => 0,
        ]);

        $this->assertTrue($DB->record_exists('context', ['id' => $danglingcontextid]));

        new clean(['dryrun' => true]);
        ob_start();
        clean::delete_dangling_course_contexts();
        $output = ob_get_clean();

        $this->assertTrue($DB->record_exists('context', ['id' => $danglingcontextid]));
        $this->assertTrue($DB->record_exists('context', ['id' => $validcontext->id]));
        $this->assertStringContainsString('Would delete 1 context records', $output);
    }

    /**
     * Function execute() should preserve selected courses and delete unselected courses.
     */
    public function test_execute_deletes_configured_courses_only(): void {
        global $DB;

        $selected = $this->getDataGenerator()->create_course(['shortname' => 'selected']);
        $unselected = $this->getDataGenerator()->create_course(['shortname' => 'unselected']);
        set_config('minimumage', 0, 'cleaner_courses');
        set_config('courses', $selected->shortname, 'cleaner_courses');

        new clean();
        ob_start();
        clean::execute();
        ob_get_clean();

        $this->assertTrue($DB->record_exists('course', ['id' => $selected->id]));
        $this->assertFalse($DB->record_exists('course', ['id' => $unselected->id]));
    }

    /**
     * Function execute() should report when no courses match the configured criteria.
     */
    public function test_execute_reports_when_no_courses_match(): void {
        set_config('minimumage', 0, 'cleaner_courses');
        set_config('courses', 'does-not-exist', 'cleaner_courses');

        new clean();
        ob_start();
        clean::execute();
        $output = ob_get_clean();

        $this->assertStringContainsString('No courses need deletion.', $output);
    }
}
