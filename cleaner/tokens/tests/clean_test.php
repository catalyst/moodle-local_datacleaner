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

namespace cleaner_tokens\tests;

use cleaner_tokens\clean;
use PHPUnit\Framework\Attributes\CoversClass;
use xmldb_table;

/**
 * Testcase for cleaner_tokens
 *
 * @package    cleaner_tokens
 * @copyright  2026 Catalyst IT
 * @author     Jason den Dulk <jasondendulk@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(clean::class)]
final class clean_test extends \advanced_testcase {
    /** @var string[] Tables created during the test run. */
    private array $tables = [];

    /**
     * Setup for each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->tables = [];
    }

    /**
     * Tear down and clean up any temporary tables.
     */
    protected function tearDown(): void {
        global $DB;

        $dbman = $DB->get_manager();
        foreach ($this->tables as $tablename) {
            if ($dbman->table_exists($tablename)) {
                $dbman->drop_table(new xmldb_table($tablename));
            }
        }

        parent::tearDown();
    }

    /**
     * Create a temporary table with a token column.
     *
     * @param string $tablename The unprefixed table name.
     * @param int $length Token column length.
     */
    private function create_token_table(string $tablename, int $length = 64): void {
        global $DB;

        $dbman = $DB->get_manager();
        if ($dbman->table_exists($tablename)) {
            $dbman->drop_table(new xmldb_table($tablename));
        }

        $table = new xmldb_table($tablename);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('token', XMLDB_TYPE_CHAR, (string) $length, null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($table);

        $this->tables[] = $tablename;
    }

    /**
     * Insert deterministic test records into a table.
     *
     * @param string $tablename The unprefixed table name.
     * @param array $tokens Token values to insert.
     */
    private function insert_tokens(string $tablename, array $tokens): void {
        global $DB;

        foreach ($tokens as $token) {
            $DB->insert_record($tablename, (object) ['token' => $token]);
        }
    }

    /**
     * Test execute() reads the plugin config and applies all configured actions.
     */
    public function test_execute_truncates_hashes_and_regenerates_fields(): void {
        global $DB;

        $truncate = 'cleaner_tokens_test_truncate';
        $hash = 'cleaner_tokens_test_hash';
        $regen = 'cleaner_tokens_test_regen';

        $this->create_token_table($truncate);
        $this->create_token_table($hash);
        $this->create_token_table($regen);

        $this->insert_tokens($truncate, ['alpha', 'beta']);
        $this->insert_tokens($hash, ['first-token', 'second-token']);
        $this->insert_tokens($regen, ['regen-one', 'regen-two']);

        set_config('tablestotruncate', implode("\r\n", [$truncate]), 'cleaner_tokens');
        set_config('fieldstorehash', $hash . ':token:8', 'cleaner_tokens');
        set_config('rehashseed', '64', 'cleaner_tokens');
        set_config('fieldstoregenerate', $regen . ':token:12', 'cleaner_tokens');

        ob_start();
        clean::execute();
        ob_end_clean();

        $this->assertEquals(0, $DB->count_records($truncate));

        $hashed = $DB->get_records($hash, null, 'id ASC');
        foreach ($hashed as $record) {
            $this->assertLessThanOrEqual(8, strlen($record->token));
            $this->assertNotEquals('first-token', $record->token);
            $this->assertNotEquals('second-token', $record->token);
        }

        $regenerated = $DB->get_records($regen, null, 'id ASC');
        foreach ($regenerated as $record) {
            $this->assertLessThanOrEqual(12, strlen($record->token));
            $this->assertNotEquals('regen-one', $record->token);
            $this->assertNotEquals('regen-two', $record->token);
        }
    }

    /**
     * Test truncate_tables() removes all rows from configured tables.
     */
    public function test_truncate_tables_removes_rows_for_existing_tables(): void {
        global $DB;

        $tablename1 = 'cleaner_tokens_test_truncate_1';
        $tablename2 = 'cleaner_tokens_test_truncate_2';

        $this->create_token_table($tablename1);
        $this->create_token_table($tablename2);
        $this->insert_tokens($tablename1, ['keep-me', 'remove-me']);
        $this->insert_tokens($tablename2, ['clobber-me']);

        ob_start();
        clean::truncate_tables([$tablename1, $tablename2]);
        ob_end_clean();

        $this->assertEquals(0, $DB->count_records($tablename1));
        $this->assertEquals(0, $DB->count_records($tablename2));
    }

    /**
     * Test rehash_fields() hashes values and enforces the optional length limit.
     */
    public function test_rehash_fields_hashes_values_and_truncates_when_requested(): void {
        global $DB;

        $tablename1 = 'cleaner_tokens_test_rehash1';
        $tablename2 = 'cleaner_tokens_test_rehash2';
        $this->create_token_table($tablename1);
        $this->create_token_table($tablename2);
        $this->insert_tokens($tablename1, ['alpha-value', 'beta-value']);
        $this->insert_tokens($tablename2, ['gamma-value']);

        ob_start();
        clean::rehash_fields([$tablename1 . ':token:8', $tablename2 . ':token']);
        ob_end_clean();

        $values = array_map(static fn($record) => $record->token, $DB->get_records($tablename1, null, 'id ASC'));
        foreach ($values as $value) {
            $this->assertLessThanOrEqual(8, strlen($value));
            $this->assertNotSame('alpha-value', $value);
            $this->assertNotSame('beta-value', $value);
        }

        $values = array_map(static fn($record) => $record->token, $DB->get_records($tablename2, null, 'id ASC'));
        foreach ($values as $value) {
            $this->assertNotSame('gamma-value', $value);
        }
    }

    /**
     * Test rehash_fields() rejects malformed field definitions.
     */
    public function test_rehash_fields_rejects_invalid_field_definition(): void {
        $this->expectException(\moodle_exception::class);

        clean::rehash_fields(['cleaner_tokens_test_rehash:']);
    }

    /**
     * Test regenerate_fields() replaces values with random strings and applies the length limit.
     */
    public function test_regenerate_fields_replaces_values_and_applies_length_limit(): void {
        global $DB;

        $tablename1 = 'cleaner_tokens_test_regenerate1';
        $tablename2 = 'cleaner_tokens_test_regenerate2';
        $this->create_token_table($tablename1);
        $this->create_token_table($tablename2);
        $this->insert_tokens($tablename1, ['seed-one', 'seed-two']);
        $this->insert_tokens($tablename2, ['seed-three']);

        ob_start();
        clean::regenerate_fields([$tablename1 . ':token:12', $tablename2 . ':token']);
        ob_end_clean();

        $values = array_map(static fn($record) => $record->token, $DB->get_records($tablename1, null, 'id ASC'));
        foreach ($values as $value) {
            $this->assertLessThanOrEqual(12, strlen($value));
            $this->assertNotSame('seed-one', $value);
            $this->assertNotSame('seed-two', $value);
        }

        $values = array_map(static fn($record) => $record->token, $DB->get_records($tablename2, null, 'id ASC'));
        foreach ($values as $value) {
            $this->assertNotSame('seed-three', $value);
        }
    }

    /**
     * Test regenerate_fields() rejects malformed field definitions.
     */
    public function test_regenerate_fields_rejects_invalid_field_definition(): void {
        $this->expectException(\moodle_exception::class);

        clean::regenerate_fields(['cleaner_tokens_test_regenerate:']);
    }
}
