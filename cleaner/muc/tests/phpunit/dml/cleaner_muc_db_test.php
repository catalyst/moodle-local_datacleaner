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

use cleaner_muc\dml\muc_config_db;
use cleaner_muc\envbar_adapter;

defined('MOODLE_INTERNAL') || die();

class  local_cleanurls_cleaner_muc_db_test extends advanced_testcase {
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        // Trigger classloaders.
        class_exists(muc_config_db::class);
    }

    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_it_exists() {
        self::assertTrue(class_exists('\cleaner_muc\dml\muc_config_db'));
    }

    public function test_it_creates() {
        global $DB;

        $id = muc_config_db::save('http://wwwroot.moodle.test', '<?php // File Contents');

        $actual = $DB->get_record('cleaner_muc_configs', ['id' => $id]);
        self::assertSame(base64_encode('http://wwwroot.moodle.test'), $actual->wwwroot);
        self::assertSame('<?php // File Contents', $actual->configuration);
        self::assertGreaterThan(0, $actual->lastmodified);
    }

    public function test_it_reads_one() {
        muc_config_db::save('http://moodle.test', 'My Configuration');
        $config = muc_config_db::get('http://moodle.test');
        self::assertSame('My Configuration', $config);
    }

    public function test_it_reads_one_null_if_not_found() {
        $config = muc_config_db::get('http://moodle.test');
        self::assertNull($config);
    }

    public function test_it_reads_all() {
        $expected = [
            'http://moodle1.test'            => 'Moodle 1 Config',
            'http://moodle2'                 => 'Moodle 2 Configuration!',
            'https://moodle1.test/submoodle' => 'Moodle 1 SubMoodle Config',
        ];

        foreach ($expected as $wwwroot => $configuration) {
            muc_config_db::save($wwwroot, $configuration);
        }

        $actual = muc_config_db::get_all();
        self::assertSame($expected, $actual);
    }

    public function test_it_reads_all_ordered_by_wwwroot() {
        $expected = [
            'https://c' => 'Config',
            'http://c'  => 'Config',
            'https://a' => 'Config',
            'http://a'  => 'Config',
            'http://b'  => 'Config',
            'https://b' => 'Config',
        ];

        foreach ($expected as $wwwroot => $configuration) {
            muc_config_db::save($wwwroot, $configuration);
        }

        $expected = array_keys($expected);
        sort($expected);

        $actual = array_keys(muc_config_db::get_all());
        self::assertSame($expected, $actual);
    }

    public function test_it_updates() {
        muc_config_db::save('http://wwwroot.moodle.test', 'Wrong Config');
        muc_config_db::save('http://wwwroot.moodle.test', 'Correct Config');
        $config = muc_config_db::get('http://wwwroot.moodle.test');
        self::assertSame('Correct Config', $config);
    }

    public function test_it_deletes() {
        $wwwroot = 'http://moodle.test';
        $leaveme = 'http://moodle2.test';

        muc_config_db::save($wwwroot, 'My Configuration');
        muc_config_db::delete($wwwroot);

        $found = muc_config_db::get($wwwroot);
        self::assertNull($found, 'Should have been deleted.');

        $found = muc_config_db::get($leaveme);
        self::assertNull($found, 'Should have not been deleted.');
    }

    public function test_it_logs() {
        $this->markTestSkipped('Test not implemented.');
    }
}
