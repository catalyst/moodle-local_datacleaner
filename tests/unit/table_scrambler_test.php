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
 * Testcase for table_scrambler
 *
 * @package     local_datacleaner
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_datacleaner\table_scrambler;

defined('MOODLE_INTERNAL') || die();

/**
 * Testcase for table_scrambler
 *
 * @package     local_datacleaner
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @SuppressWarnings(public) Allow as many methods as needed.
 */
class local_datacleaner_table_scrambler_test extends advanced_testcase {
    public function provider_for_it_creates_sorted_temporary_tables() {
        return [
            'unrepeated' => [
                $this->create_test_data_array(),
                [
                    ['id' => '1', 'value' => 'Bill'],
                    ['id' => '2', 'value' => 'David'],
                    ['id' => '3', 'value' => 'Nicholas'],
                ],
                [
                    ['id' => '1', 'value' => 'Hoobin'],
                    ['id' => '2', 'value' => 'Jones'],
                    ['id' => '3', 'value' => 'Smith'],
                ],
            ],
            'repeated'   => [
                [
                    ['first' => 'John', 'last' => 'Smith'],
                    ['first' => 'John', 'last' => 'Doe'],
                    ['first' => 'Daniel', 'last' => 'Silva'],
                    ['first' => 'Daniel', 'last' => 'Roperto'],
                    ['first' => 'Brendan', 'last' => 'Heywood'],
                    ['first' => 'Nicholas', 'last' => 'Hoobin'],
                ],
                [
                    ['id' => '1', 'value' => 'Brendan'],
                    ['id' => '2', 'value' => 'Daniel'],
                    ['id' => '3', 'value' => 'John'],
                ],
                [
                    ['id' => '1', 'value' => 'Doe'],
                    ['id' => '2', 'value' => 'Silva'],
                    ['id' => '3', 'value' => 'Smith'],
                ],
            ],
        ];
    }

    public function provider_for_it_scrambles_names() {
        return [
            ['', [
                ['id' => '1', 'first' => 'David', 'last' => 'Jones'],
                ['id' => '2', 'first' => 'Bill', 'last' => 'Smith'],
                ['id' => '3', 'first' => 'David', 'last' => 'Hoobin'],
                ['id' => '4', 'first' => 'Bill', 'last' => 'Jones'],
                ['id' => '5', 'first' => 'David', 'last' => 'Smith'],
                ['id' => '6', 'first' => 'Bill', 'last' => 'Hoobin'],
            ]],
            ['3,4,5,6', [
                ['id' => '1', 'first' => 'David', 'last' => 'Smith'],
                ['id' => '2', 'first' => 'Nicholas', 'last' => 'Hoobin'],
                ['id' => '3', 'first' => 'David', 'last' => 'Hoobin'],
                ['id' => '4', 'first' => 'Bill', 'last' => 'Jones'],
                ['id' => '5', 'first' => 'David', 'last' => 'Smith'],
                ['id' => '6', 'first' => 'Bill', 'last' => 'Hoobin'],
            ]],
        ];
    }

    public function provider_for_the_next_prime_after() {
        return [
            [0, 2],
            [1, 2],
            [2, 3],
            [919, 929],
        ];
    }

    public function provider_for_the_prime_factors() {
        return [
            [2, 1, [2, 3]],
            [2, 3, [2, 3]],
            [2, 6, [2, 3]],
            [2, 24, [5, 7]],
        ];
    }

    /**
     * @dataProvider provider_for_it_creates_sorted_temporary_tables
     */
    public function test_it_creates_sorted_temporary_tables($inputdata, $expectedfirst, $expectedlast) {
        global $DB;
        $this->resetAfterTest(true);

        $table = $this->create_test_data($inputdata);

        $scrambler = new table_scrambler('test_names', ['first', 'last']);
        $scrambler->create_temporary_tables();

        // Check first name table.
        $data = $DB->get_records('tmp_first', null, 'id ASC');
        self::assertCount(count($expectedfirst), $data);
        for ($i = 0; $i < count($expectedfirst); $i++) {
            self::assertSame($expectedfirst[$i], (array)($data[$i + 1]));
        }

        // Check last name table.
        $data = $DB->get_records('tmp_last', null, 'id ASC');
        self::assertCount(count($expectedlast), $data);
        for ($i = 0; $i < count($expectedlast); $i++) {
            self::assertSame($expectedlast[$i], (array)($data[$i + 1]));
        }

        // Drop test tables.
        $scrambler->drop_temporary_tables();
        $DB->get_manager()->drop_table($table);
    }

    public function test_it_requires_a_table_name_and_fields() {
        $scrambler = new table_scrambler('table', ['name', 'address']);
        self::assertSame('table', $scrambler->get_table());
        self::assertSame(['name', 'address'], $scrambler->get_fields_to_scramble());
    }

    /**
     * @dataProvider provider_for_it_scrambles_names
     */
    public function test_it_scrambles_names($except, $expected) {
        global $DB;
        $this->resetAfterTest(true);

        $table = $this->create_test_data();

        $scrambler = new table_scrambler('test_names', ['first', 'last']);
        if (!empty($except)) {
            $scrambler->set_change_only_ids($except);
        }
        $scrambler->execute();

        // Check if it was properly screambled.
        $scrambled = $DB->get_records('test_names', null, 'id ASC');
        self::assertCount(count($expected), $scrambled);
        for ($i = 0; $i < count($expected); $i++) {
            self::assertSame($expected[$i], (array)($scrambled[$i + 1]));
        }

        // Drop test table.
        $DB->get_manager()->drop_table($table);
    }

    public function test_it_throws_an_exception_if_cannot_find_the_next_prime() {
        $this->setExpectedException(invalid_parameter_exception::class);
        table_scrambler::get_prime_after(999999);
    }

    public function test_the_2_prime_factors_for_14351_are_113_127() {
        $factors = table_scrambler::get_prime_factors(2, 14351);
        $product = array_product($factors);
        self::assertGreaterThanOrEqual(113 * 127, $product);
        // We could possibly get something smaller, ensure another algorithm is not worst than our current one.
        self::assertLessThanOrEqual(127 * 131, $product);
    }

    /**
     * @dataProvider provider_for_the_next_prime_after
     */
    public function test_the_next_prime_after($number, $expected) {
        $next = table_scrambler::get_prime_after($number);
        self::assertSame($expected, $next);
    }

    /**
     * @dataProvider provider_for_the_prime_factors
     */
    public function test_the_prime_factors($count, $number, $expected) {
        $factors = table_scrambler::get_prime_factors($count, $number);
        self::assertSame($expected, $factors);
    }

    private function create_test_data($data = null) {
        global $DB;

        if (is_null($data)) {
            $data = $this->create_test_data_array();
        }

        // Create test table.
        $dbmanager = $DB->get_manager();
        $table = new xmldb_table('test_names');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 5, true, true, true);
        $table->add_field('first', XMLDB_TYPE_CHAR, 20);
        $table->add_field('last', XMLDB_TYPE_CHAR, 20);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbmanager->create_temp_table($table);

        // Fill some data.
        // Not using $DB->insert_records() because it did not exist in Moodle 26.
        foreach ($data as $row) {
            $DB->insert_record('test_names', $row);
        }

        return $table;
    }

    private function create_test_data_array() {
        return [
            ['first' => 'David', 'last' => 'Smith'],
            ['first' => 'Nicholas', 'last' => 'Hoobin'],
            ['first' => 'Bill', 'last' => 'Jones'],
            ['first' => 'Daniel', 'last' => 'Roperto'],
            ['first' => 'Brendan', 'last' => 'Heywood'],
            ['first' => 'Sarah', 'last' => 'Bryce'],
        ];
    }
}
