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
 * @package     cleaner_muc
 * @subpackage  local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use cleaner_muc\envbar_adapter;
use local_envbar\local\envbarlib;

defined('MOODLE_INTERNAL') || die();

class  local_cleanurls_cleaner_muc_envbar_adapter_test extends advanced_testcase {
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        // Trigger classloaders.
        class_exists(envbarlib::class);
    }

    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->create_envbar_data();
    }

    public function test_it_lists_the_environments() {
        $environments = envbar_adapter::get_environments();
        self::assertCount(3, $environments);
        self::assertSame('R', $environments[0]['showtext']);
        self::assertSame('G', $environments[1]['showtext']);
        self::assertSame('B', $environments[2]['showtext']);
    }

    public function test_it_know_we_are_production_site() {
        self::mock_production_site();
        self::assertTrue(envbar_adapter::is_production());
    }

    public function test_it_know_we_are_not_production_site() {
        self::assertFalse(envbar_adapter::is_production());
    }

    private function create_envbar_data() {
        envbarlib::setprodwwwroot('http://production.example');

        $entries = [
            ['colourbg' => '#880000', 'colourtext' => '#ff0000', 'matchpattern' => 'red', 'showtext' => 'R', 'lastrefresh' => 0],
            ['colourbg' => '#008800', 'colourtext' => '#00ff00', 'matchpattern' => 'green', 'showtext' => 'G', 'lastrefresh' => 0],
            ['colourbg' => '#000088', 'colourtext' => '#0000ff', 'matchpattern' => 'blue', 'showtext' => 'B', 'lastrefresh' => 0],
        ];
        foreach ($entries as $entry) {
            envbarlib::update_envbar((object)$entry);
        }
    }

    private static function mock_production_site() {
        global $CFG;
        $CFG->wwwroot = 'http://production.example';
    }
}
