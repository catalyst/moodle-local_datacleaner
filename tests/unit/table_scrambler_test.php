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
 */
class local_datacleaner_table_scrambler_test extends advanced_testcase {
    public function test_it_creates_sorted_temporary_tables() {
        global $DB;
        $this->resetAfterTest(true);

        $table = $this->create_test_data();

        $scrambler = new table_scrambler('test_names', ['first', 'last']);
        $scrambler->create_temporary_tables();

        // Check first name table.
        $data = $DB->get_records('tmp_first', null, 'id ASC');
        self::assertCount(3, $data);
        self::assertSame(['id' => '1', 'value' => 'Bill'], (array)($data[1]));
        self::assertSame(['id' => '2', 'value' => 'David'], (array)($data[2]));
        self::assertSame(['id' => '3', 'value' => 'Nicholas'], (array)($data[3]));

        // Check first name table.
        $data = $DB->get_records('tmp_last', null, 'id ASC');
        self::assertCount(3, $data);
        self::assertSame(['id' => '1', 'value' => 'Hoobin'], (array)($data[1]));
        self::assertSame(['id' => '2', 'value' => 'Jones'], (array)($data[2]));
        self::assertSame(['id' => '3', 'value' => 'Smith'], (array)($data[3]));

        // Drop test tables.
        $scrambler->drop_temporary_tables();
        $DB->get_manager()->drop_table($table);
    }

    public function test_it_creates_sorted_temporary_tables_with_repeated_names() {
        global $DB;
        $this->resetAfterTest(true);

        $table = $this->create_repeated_test_data();

        $scrambler = new table_scrambler('test_names', ['first', 'last']);
        $scrambler->create_temporary_tables();

        // Check first name table.
        $data = $DB->get_records('tmp_first', null, 'id ASC');
        self::assertCount(3, $data);
        self::assertSame(['id' => '1', 'value' => 'Brendan'], (array)($data[1]));
        self::assertSame(['id' => '2', 'value' => 'Daniel'], (array)($data[2]));
        self::assertSame(['id' => '3', 'value' => 'John'], (array)($data[3]));

        // Check first name table.
        $data = $DB->get_records('tmp_last', null, 'id ASC');
        self::assertCount(3, $data);
        self::assertSame(['id' => '1', 'value' => 'Doe'], (array)($data[1]));
        self::assertSame(['id' => '2', 'value' => 'Silva'], (array)($data[2]));
        self::assertSame(['id' => '3', 'value' => 'Smith'], (array)($data[3]));

        // Drop test tables.
        $scrambler->drop_temporary_tables();
        $DB->get_manager()->drop_table($table);
    }

    public function test_it_requires_a_table_name_and_fields() {
        $scrambler = new table_scrambler('table', ['name', 'address']);
        self::assertSame('table', $scrambler->get_table());
        self::assertSame(['name', 'address'], $scrambler->get_fields_to_scramble());
    }

    public function test_it_scrambles_names() {
        global $DB;
        $this->resetAfterTest(true);

        $table = $this->create_test_data();

        $scrambler = new table_scrambler('test_names', ['first', 'last']);
        $scrambler->execute();

        // Check if it was properly screambled.
        $scrambled = $DB->get_records('test_names', null, 'id ASC');
        self::assertCount(6, $scrambled);
        self::assertSame(['id' => '1', 'first' => 'David', 'last' => 'Jones'], (array)($scrambled[1]));
        self::assertSame(['id' => '2', 'first' => 'Bill', 'last' => 'Smith'], (array)($scrambled[2]));
        self::assertSame(['id' => '3', 'first' => 'David', 'last' => 'Hoobin'], (array)($scrambled[3]));
        self::assertSame(['id' => '4', 'first' => 'Bill', 'last' => 'Jones'], (array)($scrambled[4]));
        self::assertSame(['id' => '5', 'first' => 'David', 'last' => 'Smith'], (array)($scrambled[5]));
        self::assertSame(['id' => '6', 'first' => 'Bill', 'last' => 'Hoobin'], (array)($scrambled[6]));

        // Drop test table.
        $DB->get_manager()->drop_table($table);
    }

    public function test_it_scrambles_names_except_the_ids_1_and_2() {
        global $DB;
        $this->resetAfterTest(true);

        $table = $this->create_test_data();

        $scrambler = new table_scrambler('test_names', ['first', 'last']);
        $scrambler->set_change_only_ids('3,4,5,6');
        $scrambler->execute();

        // Check if it was properly screambled.
        $scrambled = $DB->get_records('test_names', null, 'id ASC');
        self::assertCount(6, $scrambled);

        // This should be the same.
        self::assertSame(['id' => '1', 'first' => 'David', 'last' => 'Smith'], (array)($scrambled[1]));
        self::assertSame(['id' => '2', 'first' => 'Nicholas', 'last' => 'Hoobin'], (array)($scrambled[2]));

        // This should have been scrambled.
        self::assertSame(['id' => '3', 'first' => 'David', 'last' => 'Hoobin'], (array)($scrambled[3]));
        self::assertSame(['id' => '4', 'first' => 'Bill', 'last' => 'Jones'], (array)($scrambled[4]));
        self::assertSame(['id' => '5', 'first' => 'David', 'last' => 'Smith'], (array)($scrambled[5]));
        self::assertSame(['id' => '6', 'first' => 'Bill', 'last' => 'Hoobin'], (array)($scrambled[6]));

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

    public function test_the_2_prime_factors_for_1_are_1_2() {
        $factors = table_scrambler::get_prime_factors(2, 3);
        self::assertSame([2, 3], $factors);
    }

    public function test_the_2_prime_factors_for_24_are_5_7() {
        $factors = table_scrambler::get_prime_factors(2, 24);
        self::assertSame([5, 7], $factors);
    }

    public function test_the_2_prime_factors_for_25_are_5_7() {
        $factors = table_scrambler::get_prime_factors(2, 25);
        self::assertSame([5, 7], $factors);
    }

    public function test_the_2_prime_factors_for_6_are_2_3() {
        $factors = table_scrambler::get_prime_factors(2, 6);
        self::assertSame([2, 3], $factors);
    }

    public function test_the_next_prime_after_0_is_2() {
        self::assertSame(2, table_scrambler::get_prime_after(0));
    }

    public function test_the_next_prime_after_1_is_2() {
        self::assertSame(2, table_scrambler::get_prime_after(1));
    }

    public function test_the_next_prime_after_2_is_3() {
        self::assertSame(3, table_scrambler::get_prime_after(2));
    }

    public function test_the_next_prime_after_919_is_929() {
        self::assertSame(929, table_scrambler::get_prime_after(919));
    }

    private function create_repeated_test_data() {
        global $DB;

        // Create test table.
        $dbmanager = $DB->get_manager();
        $table = new xmldb_table('test_names');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 5, true, true, true);
        $table->add_field('first', XMLDB_TYPE_CHAR, 20);
        $table->add_field('last', XMLDB_TYPE_CHAR, 20);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbmanager->create_temp_table($table);

        // Fill some data.
        $DB->insert_records('test_names', [
            ['first' => 'John', 'last' => 'Smith'],
            ['first' => 'John', 'last' => 'Doe'],
            ['first' => 'Daniel', 'last' => 'Silva'],
            ['first' => 'Daniel', 'last' => 'Roperto'],
            ['first' => 'Brendan', 'last' => 'Heywood'],
            ['first' => 'Nicholas', 'last' => 'Hoobin'],
        ]);

        return $table;
    }

    private function create_test_data() {
        global $DB;

        // Create test table.
        $dbmanager = $DB->get_manager();
        $table = new xmldb_table('test_names');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 5, true, true, true);
        $table->add_field('first', XMLDB_TYPE_CHAR, 20);
        $table->add_field('last', XMLDB_TYPE_CHAR, 20);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbmanager->create_temp_table($table);

        // Fill some data.
        $DB->insert_records('test_names', [
            ['first' => 'David', 'last' => 'Smith'],
            ['first' => 'Nicholas', 'last' => 'Hoobin'],
            ['first' => 'Bill', 'last' => 'Jones'],
            ['first' => 'Daniel', 'last' => 'Roperto'],
            ['first' => 'Brendan', 'last' => 'Heywood'],
            ['first' => 'Sarah', 'last' => 'Bryce'],
        ]);

        return $table;
    }
}
