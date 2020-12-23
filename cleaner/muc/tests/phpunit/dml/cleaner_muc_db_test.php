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
use cleaner_muc\muc_config;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../cleaner_muc_testcase.php');

/**
 * Tests.
 *
 * @package     cleaner_muc
 * @subpackage  local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @SuppressWarnings(public) Allow as many methods as needed.
 */
class local_cleanurls_cleaner_muc_db_test extends local_datacleaner_cleaner_muc_testcase {
    protected function setUp() : void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_it_exists() {
        self::assertTrue(class_exists('\cleaner_muc\dml\muc_config_db'));
    }

    public function test_it_creates() {
        global $DB;

        $config = new muc_config([
                                     'wwwroot'       => 'http://wwwroot.moodle.test',
                                     'configuration' => '<?php // File Contents',
                                 ]);
        muc_config_db::save($config);

        $actual = $DB->get_record('cleaner_muc_configs', ['id' => $config->get_id()]);
        self::assertSame(base64_encode('http://wwwroot.moodle.test'), $actual->wwwroot);
        self::assertSame('<?php // File Contents', $actual->configuration);
        self::assertGreaterThan(0, $actual->lastmodified);
    }

    public function test_it_reads_one() {
        $expected = self::create_muc_config();
        $actual = muc_config_db::get_by_wwwroot($expected->get_wwwroot());

        self::assertSame($expected->to_array(), $actual->to_array());
    }

    public function test_it_reads_one_null_if_not_found() {
        $config = muc_config_db::get_by_wwwroot('http://moodle.test');
        self::assertNull($config);
    }

    public function test_it_reads_all() {
        $expected = [
            'http://moodle1.test'            => 'Moodle 1 Config',
            'http://moodle2'                 => 'Moodle 2 Configuration!',
            'https://moodle1.test/submoodle' => 'Moodle 1 SubMoodle Config',
        ];

        foreach ($expected as $wwwroot => $configuration) {
            $config = self::create_muc_config($wwwroot, $configuration);
            $expected[$wwwroot] = $config->to_array();
        }

        $actual = muc_config_db::get_all();
        foreach ($actual as $wwwroot => $config) {
            $actual[$wwwroot] = $config->to_array();
        }

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
            $expected[$wwwroot] = self::create_muc_config($wwwroot, $configuration);
        }

        $expected = array_keys($expected);
        sort($expected);

        $actual = array_keys(muc_config_db::get_all());
        self::assertSame($expected, $actual);
    }

    public function test_it_reads_environments() {
        $expected = [
            'http://moodle1.test'            => 'Moodle 1 Config',
            'http://moodle2'                 => 'Moodle 2 Configuration!',
            'https://moodle1.test/submoodle' => 'Moodle 1 SubMoodle Config',
        ];

        foreach ($expected as $wwwroot => $configuration) {
            $expected[$wwwroot] = self::create_muc_config($wwwroot, $configuration);
        }

        $actual = muc_config_db::get_environments();
        self::assertSame(array_keys($expected), $actual);
    }

    public function test_it_reads_environments_ordered_by_wwwroot() {
        $expected = [
            'https://c' => 'Config',
            'http://c'  => 'Config',
            'https://a' => 'Config',
            'http://a'  => 'Config',
            'http://b'  => 'Config',
            'https://b' => 'Config',
        ];

        foreach ($expected as $wwwroot => $configuration) {
            $expected[$wwwroot] = self::create_muc_config($wwwroot, $configuration);
        }

        $expected = array_keys($expected);
        sort($expected);

        $actual = muc_config_db::get_environments();
        self::assertSame($expected, $actual);
    }

    public function test_it_updates() {
        muc_config_db::save(new muc_config(['wwwroot' => 'http://wwwroot.moodle.test', 'configuration' => 'Wrong Config']));
        muc_config_db::save(new muc_config(['wwwroot' => 'http://wwwroot.moodle.test', 'configuration' => 'Correct Config']));

        $config = muc_config_db::get_by_wwwroot('http://wwwroot.moodle.test');
        self::assertSame('Correct Config', $config->get_configuration());
    }

    public function test_it_deletes() {
        self::create_muc_config('http://moodle.test');
        self::create_muc_config('http://moodle2.test');

        muc_config_db::delete('http://moodle.test');

        $found = muc_config_db::get_by_wwwroot('http://moodle.test');
        self::assertNull($found, 'Should have been deleted.');

        $found = muc_config_db::get_by_wwwroot('http://moodle2.test');
        self::assertNotNull($found, 'Should have not been deleted.');
    }

    public function test_it_updates_lastmodified_when_saving() {
        $config = new muc_config(['wwwroot' => 'http://moodle.test']);
        $config->set_lastmodified(123);

        muc_config_db::save($config);
        self::assertGreaterThan(123, $config->get_lastmodified());
    }
}
