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

use cleaner_muc\muc_config;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/cleaner_muc_testcase.php');

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
class local_cleanurls_cleaner_muc_config_test extends local_datacleaner_cleaner_muc_testcase {
    public function test_it_has_the_required_fields() {
        $expected = ['id', 'wwwroot', 'configuration', 'lastmodified'];
        $reflection = new ReflectionClass(muc_config::class);
        $fields = $reflection->getProperties();
        $actual = [];
        foreach ($fields as $field) {
            $actual[] = $field->getName();
        }
        self::assertSame($expected, $actual);
    }

    public function test_it_can_be_created_from_array_and_object() {
        $inputdata = [
            'id'            => 1,
            'wwwroot'       => 'http://moodle.test',
            'configuration' => '<?php // Config',
            'lastmodified'  => 2,
        ];

        foreach (['array', 'object'] as $type) {
            $data = ($type == 'object') ? (object)$inputdata : $inputdata;
            $config = new muc_config($data);

            $type = "For type {$type}";
            self::assertSame(1, $config->get_id(), $type);
            self::assertSame('http://moodle.test', $config->get_wwwroot(), $type);
            self::assertSame('<?php // Config', $config->get_configuration(), $type);
            self::assertSame(2, $config->get_lastmodified(), $type);
        }
    }

    public function test_it_can_be_created_from_another_config() {
        $data = [
            'id'            => 1,
            'wwwroot'       => 'http://moodle.test',
            'configuration' => '<?php // Config',
            'lastmodified'  => 2,
        ];

        $config = new muc_config($data);
        $newconfig = new muc_config($config);
        self::assertSame(1, $newconfig->get_id());
        self::assertSame('http://moodle.test', $newconfig->get_wwwroot());
        self::assertSame('<?php // Config', $newconfig->get_configuration());
        self::assertSame(2, $newconfig->get_lastmodified());
    }

    public function test_it_can_be_converted_back_from_array() {
        $expected = [
            'id'            => 1,
            'wwwroot'       => 'http://moodle.test',
            'configuration' => '<?php // Config',
            'lastmodified'  => 5,
        ];
        $config = new muc_config($expected);
        $actual = $config->to_array();
        self::assertSame($expected, $actual);
    }

    public function test_it_can_have_some_fields_null() {
        $expected = [
            'id'            => null,
            'wwwroot'       => '',
            'configuration' => '',
            'lastmodified'  => null,
        ];
        $config = new muc_config([]);
        $actual = $config->to_array();
        self::assertSame($expected, $actual);
    }
}
