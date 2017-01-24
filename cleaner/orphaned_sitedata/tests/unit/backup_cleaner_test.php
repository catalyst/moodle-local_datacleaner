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
 * backup_cleaner test class.
 *
 * @package     backup_cleaner_test
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_orphaned_sitedata\tests\unit;

use cleaner_orphaned_sitedata\backup_cleaner;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/adminlib.php');
require_once(__DIR__.'/orphaned_sitedata_testcase.php');

/**
 * backup_cleaner test class.
 *
 * @package     backup_cleaner_test
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @SuppressWarnings(public) Allow as many methods as needed.
 */
class backup_cleaner_test extends orphaned_sitedata_testcase {
    private $initialfiles;

    public function setUp() {
        parent::setUp();
        $this->initialfiles = $this->get_files();
    }

    public function test_it_exists() {
        $cleaner = new backup_cleaner(true);
        self::assertNotNull($cleaner);
    }

    public function test_it_does_nothing_if_no_backup_files_exist() {
        $this->execute(new backup_cleaner(true));
        self::assertSame($this->initialfiles, $this->get_files());
    }

    public function test_it_removes_backup_files() {
        $this->resetAfterTest(true);
        $file = $this->create_backup_file('test_it_removes_backup_files.backup');
        self::assertFileExists($file);
        $this->execute(new backup_cleaner(false));
        self::assertFileNotExists($file);
    }

    public function test_it_does_not_remove_backup_files_in_dry_run() {
        $this->resetAfterTest(true);
        $file = $this->create_backup_file('test_it_does_not_remove_backup_files_in_dry_run.backup');
        self::assertFileExists($file);
        $this->execute(new backup_cleaner(true));
        self::assertFileExists($file);
    }

    public function create_backup_file($filename) {
        $file = $this->create_file('backup', '/somebackups/', $filename);
        return $this->get_pathname($file);
    }

    private function get_files() {
        global $DB;
        $found = $DB->get_records_select('files', "filename <> '.'", null, 'id ASC');
        array_walk($found, function(&$value) {
            $value = $value->filename;
        });
        return $found;
    }
}
