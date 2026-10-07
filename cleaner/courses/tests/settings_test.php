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

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * Tests for the courses cleaner settings page.
 *
 * @package    cleaner_courses
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversNothing]
final class settings_test extends \advanced_testcase {
    /**
     * Render the categories setting of the courses cleaner.
     *
     * @return string HTML of the setting
     */
    private function render_categories_setting(): string {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        $page = admin_get_root(true, true)->locate('cleaner_courses');
        foreach ($page->settings as $setting) {
            if ($setting->name === 'categories') {
                return $setting->output_html($setting->get_setting());
            }
        }
        $this->fail('Categories setting not found.');
    }

    #[Group('baseline')]
    #[RunInSeparateProcess]
    /**
     * Markup in a category name is not rendered as HTML.
     *
     * @return void
     */
    public function test_category_label_is_escaped(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->getDataGenerator()->create_category(['name' => 'Cat <u id="mbsxss">x</u>']);

        $html = $this->render_categories_setting();

        $this->assertStringNotContainsString('<u id="mbsxss">', $html);
        $this->assertStringContainsString('Cat x', $html);
    }

    #[Group('baseline')]
    #[RunInSeparateProcess]
    /**
     * Plain category names are still listed.
     *
     * @return void
     */
    public function test_plain_category_label_is_listed(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $category = $this->getDataGenerator()->create_category(['name' => 'Grundschule Nord']);

        $html = $this->render_categories_setting();

        $this->assertStringContainsString('id_s_cleaner_courses_categories_' . $category->id, $html);
        $this->assertStringContainsString('Grundschule Nord', $html);
    }
}
