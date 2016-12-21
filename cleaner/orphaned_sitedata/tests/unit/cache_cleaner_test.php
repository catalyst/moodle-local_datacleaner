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

use cleaner_orphaned_sitedata\cache_cleaner;

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
class cache_cleaner_test extends orphaned_sitedata_testcase {
    public function test_it_exists() {
        self::assertNotNull(new cache_cleaner(true));
    }

    public function test_it_runs() {
        self::resetAfterTest(true);
        // It is only testing if the call is not exploding.
        // It is a fairly complex to test caches and we are only making Moodle API calls.
        $this->execute(new cache_cleaner(false));
    }

    public function test_it_runs_in_dry_mode() {
        // By not calling self::resetAfterTest() we ensure this test cannot modify DB or $CFG.
        // If caches are cleaned, it will fail because it changes DB and $CFG.
        $this->execute(new cache_cleaner(true));
    }
}
