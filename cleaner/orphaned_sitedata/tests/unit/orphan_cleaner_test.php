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
 * orphan_cleaner test class.
 *
 * @package     orphan_cleaner_test
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_orphaned_sitedata\tests\unit;

use cleaner_orphaned_sitedata\orphan_cleaner;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/adminlib.php');
require_once(__DIR__.'/orphaned_sitedata_testcase.php');

/**
 * orphan_cleaner test class.
 *
 * @package     orphan_cleaner_test
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @SuppressWarnings(public) Allow as many methods as needed.
 */
class orphan_cleaner_test extends orphaned_sitedata_testcase {
    public function test_it_exists() {
        self::assertNotNull(new orphan_cleaner(true));
    }

    public function test_it_removes_orphaned_files() {
        global $DB;
        $this->resetAfterTest(true);

        $file = $this->create_test_file('test_it_removes_orphaned_files.test');
        self::assertTrue($this->file_is_readable($file));

        $DB->delete_records('files', ['id' => $file->get_id()]); // Make it orphaned.

        $this->execute(new orphan_cleaner(false));

        self::assertFalse($this->file_is_readable($file));
    }

    public function test_it_does_not_remove_orphaned_files_in_dry_run() {
        global $DB;
        $this->resetAfterTest(true);

        $file = $this->create_test_file('test_it_does_not_remove_orphaned_files_in_dry_run.test');
        self::assertTrue($this->file_is_readable($file));

        $DB->delete_records('files', ['id' => $file->get_id()]); // Make it orphaned.

        $this->execute(new orphan_cleaner(true));

        self::assertTrue($this->file_is_readable($file));
    }

    public function test_it_does_not_remove_non_orphaned_files() {
        $this->resetAfterTest(true);

        $file = $this->create_test_file('test_it_does_not_remove_non_orphaned_files.test');
        self::assertTrue($this->file_is_readable($file));

        $this->execute(new orphan_cleaner(false));

        self::assertTrue($this->file_is_readable($file));
    }

    private function create_test_file($filename) {
        return $this->create_file('course', '/orphanfiles/', $filename);
    }
}
