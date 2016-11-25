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
 * Used to scramble fields from different rows in a table.
 * For more information please visit {@link https://github.com/catalyst/moodle-local_datacleaner/issues/17}
 *
 * @package     local_datacleaner
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_datacleaner;

use invalid_parameter_exception;
use xmldb_table;

/**
 * Class table_scrambler
 *
 * @package     local_datacleaner
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class table_scrambler {
    const TEMPORARY_TABLE_NAME = 'local_datacleaner_temp';

    /**
     * Gets the next prime after the given number.
     *
     * @param int $number
     * @return int
     * @throws invalid_parameter_exception
     */
    public static function get_prime_after($number) {
        $primes = self::get_primes();
        for ($i = 0; $i < count($primes); $i++) {
            if ($primes[$i] > $number) {
                return $primes[$i];
            }
        }
        throw new invalid_parameter_exception('Cannot find the next prime after ['.$number.'].');
    }

    /**
     * Gets the prime factors that will generate a number equals to or higher than the given product.
     *
     * It will not generate the smallest possible set of prime numbers but we aim the get some low numbers.
     *
     * @param int $count   Number of factors to return
     * @param int $product Product goal
     * @return int[] Factors.
     */
    public static function get_prime_factors($count, $product) {
        $primes = self::get_primes();

        // Find the Nth root for the product.
        $firstfactor = (int)pow($product, 1 / $count);

        // If this number is a prime, use it as the first prime of the factors. Otherwise find the next prime.
        if (!in_array($firstfactor, $primes)) {
            $firstfactor = self::get_prime_after($firstfactor);
        }

        // Return $count primes starting from $firstfactor.
        $index = array_search($firstfactor, $primes);
        return array_slice($primes, $index, $count);
    }

    /**
     * Table taken from http://www.factmonster.com/math/numbers/prime.html
     *
     * @return int[]
     */
    private static function get_primes() {
        return [
            2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41, 43, 47, 53, 59, 61, 67, 71, 73, 79, 83, 89, 97, 101,
            103, 107, 109, 113, 127, 131, 137, 139, 149, 151, 157, 163, 167, 173, 179, 181, 191, 193, 197, 199,
            211, 223, 227, 229, 233, 239, 241, 251, 257, 263, 269, 271, 277, 281, 283, 293, 307, 311, 313, 317,
            331, 337, 347, 349, 353, 359, 367, 373, 379, 383, 389, 397, 401, 409, 419, 421, 431, 433, 439, 443,
            449, 457, 461, 463, 467, 479, 487, 491, 499, 503, 509, 521, 523, 541, 547, 557, 563, 569, 571, 577,
            587, 593, 599, 601, 607, 613, 617, 619, 631, 641, 643, 647, 653, 659, 661, 673, 677, 683, 691, 701,
            709, 719, 727, 733, 739, 743, 751, 757, 761, 769, 773, 787, 797, 809, 811, 821, 823, 827, 829, 839,
            853, 857, 859, 863, 877, 881, 883, 887, 907, 911, 919, 929, 937, 941, 947, 953, 967, 971, 977, 983,
            991, 997,
        ];
    }

    /** @var string[] */
    private $fieldstoscramble = [];

    /** @var string */
    private $table;

    /**
     * table_scrambler constructor.
     *
     * @param string   $table            Table to scramble.
     * @param string[] $fieldstoscramble Fields to scramble.
     */
    public function __construct($table, array $fieldstoscramble) {
        $this->table = $table;
        $this->fieldstoscramble = $fieldstoscramble;
    }

    /**
     * Runs the scrambler.
     */
    public function execute() {
        global $DB;
        $n_records = $DB->count_records($this->table);
        $f_fields = count($this->fieldstoscramble);
        $factors = self::get_prime_factors($f_fields, $n_records);
        $maxprime = end($factors);

        $table = $this->create_temp_table();
        $this->populate_temp_table($maxprime);

        $data = $DB->get_records(self::TEMPORARY_TABLE_NAME);
    }

    /**
     * @return string[]
     */
    public function get_fields_to_scramble() {
        return $this->fieldstoscramble;
    }

    /**
     * @return string
     */
    public function get_table() {
        return $this->table;
    }

    /**
     * @return xmldb_table
     */
    private function create_temp_table() {
        global $DB;
        $dbmanager = $DB->get_manager();

        $table = new xmldb_table(self::TEMPORARY_TABLE_NAME);
        $table->add_field('id', XMLDB_TYPE_INTEGER, 10, true, true, true);
        foreach ($this->fieldstoscramble as $field) {
            // We do not know what type was the field in the original table.
            $table->add_field($field, XMLDB_TYPE_TEXT);
        }
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbmanager->create_temp_table($table);

        return $table;
    }

    /**
     * @param $rows Number of rows to populate.
     */
    private function populate_temp_table($rows) {
        global $DB;

        $intotable = '{'.self::TEMPORARY_TABLE_NAME.'}';
        $fromtable = '{'.$this->table.'}';
        $fields = implode(',', $this->fieldstoscramble);

        $sql = <<<SQL
INSERT INTO {$intotable} ({$fields}) (
    SELECT DISTINCT {$fields}
    FROM {$fromtable}
    ORDER BY {$fields}
    LIMIT {$rows}
)
SQL;

        $DB->execute($sql);
    }
}
