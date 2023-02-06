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
 * Testcase for cleaner_custom_sql_*
 *
 * @package     local_datacleaner
 * @author      Srdjan Janković <srdjan@catalyst.net.nz>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Testcase for cleaner_custom_sql_*
 *
 * @package     local_datacleaner
 * @author      Srdjan Janković <srdjan@catalyst.net.nz>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @SuppressWarnings(public) Allow as many methods as needed.
 */
class local_datacleaner_custom_sql_test extends advanced_testcase {

    /**
     * Initialise a cleaner object to reset static options.
     *
     * This prevents impact on other tests which assume default options.
     *
     * @return void
     * @throws coding_exception
     */
    public function tearDown(): void {
        new \cleaner_custom_sql_pre\clean(['verbose' => false, 'dryrun' => false]);
    }

    public function test_executes_sql() {
        global $DB;

        $this->resetAfterTest(true);

        $sql = "SELECT * FROM {table_that_is_not}";
        $prefix = $DB->get_prefix();
        $realsql = "SELECT * FROM {$prefix}table_that_is_not";

        foreach (['pre', 'post'] as $when) {
            $module = "cleaner_custom_sql_$when";
            $param = "run-$when-wash";
            $class = "$module\\clean";

            set_config('sql', $sql, $module);
            $cleaner = new $class([$param => true, 'dryrun' => false, 'verbose' => false]);
            try {
                $cleaner->execute();
                $this->fail("Should have thrown an exception");
            } catch (dml_write_exception $e) {
                $this->assertEquals($realsql, $e->sql);
            }

            set_config('sql', "SELECT 1;\nSELECT 2", $module);
            $cleaner = new $class([$param => true, 'dryrun' => true, 'verbose' => false]);
            $cleaner->execute();
        }
    }
}
