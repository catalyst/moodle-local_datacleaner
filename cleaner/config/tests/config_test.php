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
 * Unit tests for cleaner_config
 *
 * @package     local_datacleaner
 * @subpackage  cleaner_config
 * @author      Marcus Boon<marcus@catalyst-au.net>
 */

use cleaner_config\clean;

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden');

/**
 * Tests
 */
class cleaner_config_test extends advanced_testcase {

    /** @var Column names */
    private $names;

    /** @var Column values */
    private $values;

    /**
     * Insert some config make sure they are gone
     */
    protected function setUp() : void {
        parent::setup();
        $this->resetAfterTest(true);

        $this->names = array('unittestname1', 'unittestname2', 'unittestname3');
        $confignames = implode("\n", $this->names);

        $this->values = array('unittestvalsA', 'unittestvalsB');
        $configvalues = implode("\n", $this->values);

        // Set config for the config cleaner.
        set_config('names', $confignames, 'cleaner_config');
        set_config('vals', $configvalues, 'cleaner_config');

        // Set some dummy config so that we can clean it up later.
        foreach ($this->names as $name) {
            set_config($name, $name.'value');
        }

        foreach ($this->values as $value) {
            set_config($value.'name', $value);
        }
    }

    /**
     * Teardown unit tests.
     */
    protected function tearDown() : void {
        $this->names = null;
        $this->values = null;
        parent::tearDown();
    }

    /**
     * Test the wheresql function
     */
    public function test_cleaner_config_getwhere() {

        $this->resetAfterTest(true);

        $configcleaner = new clean();
        list($where, $params) = $configcleaner->get_where();
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression("/name LIKE ?/", $where);
            $this->assertMatchesRegularExpression("/value LIKE ?/", $where);
        } else {
            $this->assertRegExp("/name LIKE ?/", $where);
            $this->assertRegExp("/value LIKE ?/", $where);
        }
        $this->assertEquals('unittestname1', $params[0]);
        $this->assertEquals('unittestname2', $params[1]);
        $this->assertEquals('unittestname3', $params[2]);
        $this->assertEquals('unittestvalsA', $params[3]);
        $this->assertEquals('unittestvalsB', $params[4]);
    }

    /**
     * Test the execute function
     */
    public function test_cleaner_config_execute() {
        global $DB;

        $this->resetAfterTest(true);

        $namesbefore = $DB->count_records_select('config', "name LIKE '%unittestname%'");
        $valsbefore = $DB->count_records_select('config', "value LIKE '%unittestvals%'");
        $this->assertEquals(3, $namesbefore);
        $this->assertEquals(2, $valsbefore);

        $configcleaner = new clean();
        $configcleaner::execute();

        $namesafter = $DB->count_records_select('config', "name LIKE '%unittestname%'");
        $valsafter = $DB->count_records_select('config', "value LIKE '%unittestvals%'");
        $this->assertEquals(0, $namesafter);
        $this->assertEquals(0, $valsafter);
    }

}
