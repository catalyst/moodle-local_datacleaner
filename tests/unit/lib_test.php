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

namespace local_datacleaner;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the data cleaner lib class.
 *
 * @package local_datacleaner
 * @author Jason den Dulk <jasondendulk@catalyst-au.net>
 * @copyright 2026 Catalyst IT
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(lib::class)]
final class lib_test extends advanced_testcase {
    /**
     * Tests when original_wwwroot has not been set.
     */
    public function test_original_wwwroot_not_set(): void {
        $this->resetAfterTest(true);
        unset_config('original_wwwroot');

        $this->assertFalse(lib::is_original_wwwroot_set());
        $this->assertNull(lib::get_original_wwwroot());
        $this->assertFalse(lib::is_original_wwwroot());
    }

    /**
     * Tests when original_wwwroot is set to the current site URL, i.e. this is production.
     */
    public function test_original_wwwroot_is_current_site(): void {
        global $CFG;
        $this->resetAfterTest(true);
        set_config('original_wwwroot', base64_encode($CFG->wwwroot));

        $this->assertTrue(lib::is_original_wwwroot_set());
        $this->assertEquals($CFG->wwwroot, lib::get_original_wwwroot());
        $this->assertTrue(lib::is_original_wwwroot());
    }

    /**
     * Tests when original_wwwroot is set to another URL, i.e. this is not production.
     */
    public function test_original_wwwroot_is_other_site(): void {
        $othersite = 'https://prod.example.com';
        $this->resetAfterTest(true);
        set_config('original_wwwroot', base64_encode($othersite));

        $this->assertTrue(lib::is_original_wwwroot_set());
        $this->assertEquals($othersite, lib::get_original_wwwroot());
        $this->assertFalse(lib::is_original_wwwroot());
    }
}
