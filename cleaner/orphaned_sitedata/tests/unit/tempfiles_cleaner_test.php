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
 * cache_cleaner test class.
 *
 * @package     cache_cleaner_test
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_orphaned_sitedata\tests\unit;

use cleaner_orphaned_sitedata\orphan_cleaner;
use cleaner_orphaned_sitedata\tempfiles_cleaner;
use FilesystemIterator;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/adminlib.php');
require_once(__DIR__.'/orphaned_sitedata_testcase.php');

/**
 * cache_cleaner test class.
 *
 * @package     cache_cleaner_test
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @SuppressWarnings(public) Allow as many methods as needed.
 */
class tempfiles_cleaner_test extends orphaned_sitedata_testcase {
    public function test_it_exists() {
        self::assertNotNull(new tempfiles_cleaner(true));
    }

    public function test_it_removes_tempfiles() {
        global $CFG;
        $file = $CFG->tempdir.'/test_it_removes_tempfiles.test';
        touch($file);
        self::assertFileExists($file);
        $this->execute(new tempfiles_cleaner(false));
        self::assertFileNotExists($file);
    }

    public function test_it_removes_all_files_and_subdirs() {
        global $CFG;
        $tmpdir = $CFG->tempdir.'/test/it/removes/subdirs';
        $file = $tmpdir.'/test_it_removes_all_files_and_subdirs.test';
        mkdir($tmpdir, 0777, true);
        touch($file);
        self::assertFileExists($file);
        $this->execute(new tempfiles_cleaner(false));

        // Temporary directory should be empty.
        $hascontents = (new FilesystemIterator($CFG->tempdir))->valid();
        self::assertFalse($hascontents);
    }


    public function test_it_does_not_remove_tempfiles_in_dry_run() {
        global $CFG;
        $file = $CFG->tempdir.'/test_it_does_not_remove_tempfiles_in_dry_run.test';
        touch($file);
        self::assertFileExists($file);
        $this->execute(new tempfiles_cleaner(true));
        self::assertFileExists($file);
    }
}
