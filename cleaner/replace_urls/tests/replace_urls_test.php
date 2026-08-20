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

use cleaner_replace_urls\clean;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for replace_urls
 *
 * @package    cleaner_replace_urls
 * @copyright  2015 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(clean::class)]
final class replace_urls_test extends advanced_testcase {
    /** @var Course values */
    private $course;

    /**
     * Insert some config make sure they are gone
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);

        // Set config for original and new site.
        set_config('origsiteurl', 'local.origin', 'cleaner_replace_urls');
        set_config('enabled', 1, 'cleaner_replace_urls');
        set_config('cleantext', 1, 'cleaner_replace_urls');

        // Create a course to test.
        $coursearray = [
            'fullname' => get_config('cleaner_replace_urls', 'origsiteurl'),
        ];
        $this->course = $this->getDataGenerator()->create_course($coursearray);
    }

    /**
     * Teardown unit tests.
     */
    protected function tearDown(): void {
        $this->course = null;
        parent::tearDown();
    }

    /**
     * Regression test for old functions
     * @group test_replace_url
     */
    public function test_replace_url(): void {
        global $DB;

        $this->resetAfterTest(true);

        // Set the newsiteurl config.
        set_config('newsiteurl', 'new.origin', 'cleaner_replace_urls');

        $configcleaner = new clean();
        $configcleaner::execute();

        $namesafter = $DB->get_record_sql('SELECT fullname FROM {course} WHERE id=:name', ['name' => $this->course->id]);

        $this->assertEquals(get_config('cleaner_replace_urls', 'newsiteurl'), $namesafter->fullname);
    }

    /**
     * Test that wysiwyg fields (columns paired with a *format column) are replaced.
     *
     * Regression test for https://github.com/catalyst/moodle-local_datacleaner/issues/201
     * The bug was an inner foreach reusing the outer $column variable, causing the
     * wysiwyg scan to only run once (on the last outer iteration) and silently corrupt
     * earlier columns — so content-type fields were never cleaned.
     *
     * @group test_replace_wysiwyg
     */
    public function test_replace_wysiwyg(): void {
        global $DB;

        $this->resetAfterTest(true);

        // Use only wysiwyg mode (not cleantext) to isolate the wysiwyg code path.
        set_config('cleantext', 0, 'cleaner_replace_urls');
        set_config('cleanwysiwyg', 1, 'cleaner_replace_urls');
        set_config('newsiteurl', 'new.origin', 'cleaner_replace_urls');

        // Course_sections.summary / summaryformat is a classic wysiwyg pair.
        $section = $this->getDataGenerator()->create_course_section([
            'course'  => $this->course->id,
            'section' => 1,
        ]);
        $DB->set_field(
            'course_sections',
            'summary',
            'Visit http://local.origin/course/view.php?id=1',
            ['id' => $section->id]
        );

        $configcleaner = new clean();
        $configcleaner::execute();

        $after = $DB->get_field('course_sections', 'summary', ['id' => $section->id]);
        $this->assertStringContainsString('new.origin', $after);
        $this->assertStringNotContainsString('local.origin', $after);
    }

    /**
     * Test the replace without newsite
     * @group without
     */
    public function test_replace_url_with_wwwroot(): void {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        // Set the newsiteurl to null.
        set_config('newsiteurl', '', 'cleaner_replace_urls');

        $configcleaner = new clean();
        $configcleaner::execute();

        $namesafter = $DB->get_record_sql('SELECT fullname FROM {course} WHERE id=:name', ['name' => $this->course->id]);

        $this->assertEquals($CFG->wwwroot, $namesafter->fullname);
    }
}
