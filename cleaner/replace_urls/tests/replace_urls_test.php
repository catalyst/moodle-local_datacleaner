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
 * Unit tests for replace_urls
 *
 * @package     local_datacleaner
 * @subpackage  cleaner_replace_urls
 * @author      Marcus Boon<marcus@catalyst-au.net>
 */

use cleaner_replace_urls\clean;

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden');

/**
 * Tests
 */
class cleaner_replace_urls_test extends advanced_testcase {

    /** @var Course values */
    private $course;

    /**
     * Insert some config make sure they are gone
     */
    protected function setUp(): void {
        parent::setup();
        $this->resetAfterTest(true);

        // Set config for original and new site
        set_config('origsiteurl', 'local.origin', 'cleaner_replace_urls');
        set_config('enabled', 1, 'cleaner_replace_urls');
        set_config('cleantext', 1, 'cleaner_replace_urls');

        // create a course to test
        $coursearray = array(
            'fullname' => get_config('cleaner_replace_urls', 'origsiteurl'),
        );
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
    public function test_replace_url() {
        global $DB;

        $this->resetAfterTest(true);

        // Set the newsiteurl config
        set_config('newsiteurl', 'new.origin', 'cleaner_replace_urls');

        $configcleaner = new clean();
        $configcleaner::execute();

        $namesafter = $DB->get_record_sql('SELECT fullname FROM {course} WHERE id=:name', ['name' => $this->course->id]);

        $this->assertEquals(get_config('cleaner_replace_urls', 'newsiteurl'), $namesafter->fullname);

    }

    /**
     * Test the replace without newsite
     * @group without
     */
    public function test_replace_url_with_wwwroot() {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        // Set the newsiteurl to null
        set_config('newsiteurl', '', 'cleaner_replace_urls');

        $configcleaner = new clean();
        $configcleaner::execute();

        $namesafter = $DB->get_record_sql('SELECT fullname FROM {course} WHERE id=:name', ['name' => $this->course->id]);

        $this->assertEquals($CFG->wwwroot, $namesafter->fullname);

    }
}