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

namespace cleaner_completion;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for the skip list of the completion cleaner settings page.
 *
 * @package    cleaner_completion
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class settings_skiplist_test extends \advanced_testcase {
    /**
     * Render the skip list setting of the completion cleaner.
     *
     * @return string HTML of the setting
     */
    private function render_courses_setting(): string {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        $page = admin_get_root(true, true)->locate('cleaner_completion');
        foreach ($page->settings as $setting) {
            if ($setting->name === 'courses') {
                return $setting->output_html($setting->get_setting());
            }
        }
        $this->fail('Courses setting not found.');
    }

    /**
     * A quote in a skip list line is matched literally and not executed as SQL.
     *
     * @return void
     */
    public function test_skiplist_line_is_not_executed_as_sql(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->getDataGenerator()->create_course(['shortname' => 'other', 'fullname' => 'Unrelated course']);
        set_config('courses', "nomatch' OR '1'='1", 'cleaner_completion');

        $html = $this->render_courses_setting();

        $this->assertStringNotContainsString('Unrelated course', $html);
    }

    /**
     * Courses named in the skip list are still listed.
     *
     * @return void
     */
    public function test_skiplist_lists_matching_course(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->getDataGenerator()->create_course(['shortname' => 'keep1', 'fullname' => 'Kept course']);
        $this->getDataGenerator()->create_course(['shortname' => 'other', 'fullname' => 'Unrelated course']);
        set_config('courses', "keep1\n", 'cleaner_completion');

        $html = $this->render_courses_setting();

        $this->assertStringContainsString('Kept course', $html);
        $this->assertStringNotContainsString('Unrelated course', $html);
    }
}
