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

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Testcase for cleaner_tokens
 *
 * @package    cleaner_tokens
 * @copyright  2026 Catalyst IT
 * @author     Jason den Dulk <jasondendulk@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\cleaner_tokens\clean::class)]
final class clean_test extends \advanced_testcase {
    /**
     * Setup for each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Create sample test data for user_password_history table.
     */
    protected function create_password_history_records(): int {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $count = 3;

        for ($i = 0; $i < $count; $i++) {
            $DB->insert_record('user_password_history', [
                'userid' => $user->id,
                'hash' => password_hash('password' . $i, PASSWORD_DEFAULT),
                'timecreated' => time() - (3600 * $i),
            ]);
        }

        return $count;
    }

    /**
     * Create sample test data for user_password_resets table.
     */
    protected function create_password_resets_records(): int {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $count = 2;

        for ($i = 0; $i < $count; $i++) {
            $DB->insert_record('user_password_resets', [
                'userid' => $user->id,
                'token' => substr(hash('sha1', 'reset_token_' . $i), 0, 30),
                'timecreated' => time() - (3600 * $i),
                'timerequested' => time() - (3600 * $i),
            ]);
        }

        return $count;
    }

    /**
     * Create sample test data for external_tokens table.
     */
    protected function create_external_tokens_records(): int {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $count = 2;

        for ($i = 0; $i < $count; $i++) {
            $DB->insert_record('external_tokens', [
                'token' => md5(uniqid('token_', true)),
                'privatetoken' => md5(uniqid('private_', true)),
                'tokentype' => 0,
                'userid' => $user->id,
                'externalserviceid' => 1,
                'contextid' => 1,
                'creatorid' => $user->id,
                'timecreated' => time(),
            ]);
        }

        return $count;
    }

    /**
     * Create sample test data for registration_hubs table.
     */
    protected function create_registration_hubs_records(): int {
        global $DB;
        $count = 2;

        for ($i = 0; $i < $count; $i++) {
            $DB->insert_record('registration_hubs', [
                'token' => md5(uniqid('hub_token_', true)),
                'hubname' => 'Test Hub ' . $i,
                'huburl' => 'https://hub.example.com/' . $i,
                'confirmed' => 1,
                'secret' => md5(uniqid('hub_secret_', true)),
                'timemodified' => time(),
            ]);
        }

        return $count;
    }

    /**
     * Create sample test data for user_private_key table.
     */
    protected function create_user_private_key_records(): int {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $count = 2;

        for ($i = 0; $i < $count; $i++) {
            $DB->insert_record('user_private_key', [
                'userid' => $user->id,
                'privatekey' => md5(uniqid('private_key_', true)),
                'type' => 'webservices',
                'instance' => $i,
                'ipwhitelist' => '',
                'validuntil' => time() + 3600,
                'timecreated' => time(),
            ]);
        }

        return $count;
    }

    /**
     * Create sample test data for oauth2_issuer table.
     */
    protected function create_oauth2_issuer_records(): int {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $count = 1;

        for ($i = 0; $i < $count; $i++) {
            $DB->insert_record('oauth2_issuer', [
                'timecreated' => time(),
                'timemodified' => time(),
                'usermodified' => $user->id,
                'name' => 'Test OAuth2 Issuer ' . $i,
                'image' => '',
                'baseurl' => 'https://oauth.example.com/' . $i,
                'clientid' => 'client_id_' . $i,
                'clientsecret' => 'client_secret_' . $i,
                'loginscopes' => 'openid profile email',
                'loginscopesoffline' => 'openid profile email offline_access',
                'loginparams' => '',
                'loginparamsoffline' => '',
                'alloweddomains' => '',
                'showonloginpage' => 1,
                'discoveryurl' => '',
                'sortorder' => 1,
            ]);
        }

        return $count;
    }

    /**
     * Create sample test data for oauth2_system_account table.
     */
    protected function create_oauth2_system_account_records(): int {
        global $DB;
        $issuer = $DB->get_record('oauth2_issuer', []);
        if (!$issuer) {
            $this->create_oauth2_issuer_records();
            $issuer = $DB->get_record('oauth2_issuer', []);
        }

        $user = $this->getDataGenerator()->create_user();
        $count = 1;

        $DB->insert_record('oauth2_system_account', [
            'timecreated' => time(),
            'timemodified' => time(),
            'usermodified' => $user->id,
            'issuerid' => $issuer->id,
            'refreshtoken' => 'refresh_token_system_' . uniqid(),
            'grantedscopes' => 'openid profile email offline_access',
            'email' => 'system@example.com',
            'username' => 'system_user',
        ]);

        return $count;
    }

    /**
     * Create sample test data for oauth2_access_token table.
     */
    protected function create_oauth2_access_token_records(): int {
        global $DB;
        $issuer = $DB->get_record('oauth2_issuer', []);
        if (!$issuer) {
            $this->create_oauth2_issuer_records();
            $issuer = $DB->get_record('oauth2_issuer', []);
        }

        $user = $this->getDataGenerator()->create_user();
        $count = 1;

        $DB->insert_record('oauth2_access_token', [
            'timecreated' => time(),
            'timemodified' => time(),
            'usermodified' => $user->id,
            'issuerid' => $issuer->id,
            'token' => 'access_token_' . uniqid(),
            'expires' => time() + 3600,
            'scope' => 'openid profile email',
        ]);

        return $count;
    }

    /**
     * Create sample test data for oauth2_refresh_token table.
     */
    protected function create_oauth2_refresh_token_records(): int {
        global $DB;
        $issuer = $DB->get_record('oauth2_issuer', []);
        if (!$issuer) {
            $this->create_oauth2_issuer_records();
            $issuer = $DB->get_record('oauth2_issuer', []);
        }

        $user = $this->getDataGenerator()->create_user();
        $count = 1;

        $DB->insert_record('oauth2_refresh_token', [
            'timecreated' => time(),
            'timemodified' => time(),
            'userid' => $user->id,
            'issuerid' => $issuer->id,
            'token' => 'refresh_token_' . uniqid(),
            'scopehash' => sha1('openid profile email'),
        ]);

        return $count;
    }

    /**
     * Test cleaning user password history with dryrun disabled.
     */
    public function test_clean_user_password_history_execute(): void {
        global $DB;

        $this->create_password_history_records();
        $this->assertTrue($DB->record_exists('user_password_history', []));

        $this->set_clean_options(['dryrun' => false]);
        ob_start();
        \cleaner_tokens\clean::clean_user_password_history();
        ob_end_clean();

        $this->assertFalse($DB->record_exists('user_password_history', []));
    }

    /**
     * Test cleaning user password history with dryrun enabled.
     */
    public function test_clean_user_password_history_dryrun(): void {
        global $DB;

        $this->create_password_history_records();
        $this->assertTrue($DB->record_exists('user_password_history', []));

        $this->set_clean_options(['dryrun' => true]);
        ob_start();
        \cleaner_tokens\clean::clean_user_password_history();
        ob_end_clean();

        $this->assertTrue($DB->record_exists('user_password_history', []));
    }

    /**
     * Test cleaning user password resets with dryrun disabled.
     */
    public function test_clean_user_password_resets_execute(): void {
        global $DB;

        $this->create_password_resets_records();
        $this->assertTrue($DB->record_exists('user_password_resets', []));

        $this->set_clean_options(['dryrun' => false]);
        ob_start();
        \cleaner_tokens\clean::clean_user_password_resets();
        ob_end_clean();

        $this->assertFalse($DB->record_exists('user_password_resets', []));
    }

    /**
     * Test cleaning user password resets with dryrun enabled.
     */
    public function test_clean_user_password_resets_dryrun(): void {
        global $DB;

        $this->create_password_resets_records();
        $this->assertTrue($DB->record_exists('user_password_resets', []));

        $this->set_clean_options(['dryrun' => true]);
        ob_start();
        \cleaner_tokens\clean::clean_user_password_resets();
        ob_end_clean();

        $this->assertTrue($DB->record_exists('user_password_resets', []));
    }

    /**
     * Test cleaning external tokens with dryrun disabled.
     */
    public function test_clean_external_tokens_execute(): void {
        global $DB;

        $this->create_external_tokens_records();
        $this->assertTrue($DB->record_exists('external_tokens', []));

        $this->set_clean_options(['dryrun' => false]);
        ob_start();
        \cleaner_tokens\clean::clean_external_tokens();
        ob_end_clean();

        $this->assertFalse($DB->record_exists('external_tokens', []));
    }

    /**
     * Test cleaning external tokens with dryrun enabled.
     */
    public function test_clean_external_tokens_dryrun(): void {
        global $DB;

        $this->create_external_tokens_records();
        $this->assertTrue($DB->record_exists('external_tokens', []));

        $this->set_clean_options(['dryrun' => true]);
        ob_start();
        \cleaner_tokens\clean::clean_external_tokens();
        ob_end_clean();

        $this->assertTrue($DB->record_exists('external_tokens', []));
    }

    /**
     * Test cleaning registration hubs with dryrun disabled.
     */
    public function test_clean_registration_hubs_execute(): void {
        global $DB;

        $this->create_registration_hubs_records();
        $this->assertTrue($DB->record_exists('registration_hubs', []));

        $this->set_clean_options(['dryrun' => false]);
        ob_start();
        \cleaner_tokens\clean::clean_registration_hubs();
        ob_end_clean();

        $this->assertFalse($DB->record_exists('registration_hubs', []));
    }

    /**
     * Test cleaning registration hubs with dryrun enabled.
     */
    public function test_clean_registration_hubs_dryrun(): void {
        global $DB;

        $this->create_registration_hubs_records();
        $this->assertTrue($DB->record_exists('registration_hubs', []));

        $this->set_clean_options(['dryrun' => true]);
        ob_start();
        \cleaner_tokens\clean::clean_registration_hubs();
        ob_end_clean();

        $this->assertTrue($DB->record_exists('registration_hubs', []));
    }

    /**
     * Test cleaning user private keys with dryrun disabled.
     */
    public function test_clean_user_private_key_execute(): void {
        global $DB;

        $this->create_user_private_key_records();
        $this->assertTrue($DB->record_exists('user_private_key', []));

        $this->set_clean_options(['dryrun' => false]);
        ob_start();
        \cleaner_tokens\clean::clean_user_private_key();
        ob_end_clean();

        $this->assertFalse($DB->record_exists('user_private_key', []));
    }

    /**
     * Test cleaning user private keys with dryrun enabled.
     */
    public function test_clean_user_private_key_dryrun(): void {
        global $DB;

        $this->create_user_private_key_records();
        $this->assertTrue($DB->record_exists('user_private_key', []));

        $this->set_clean_options(['dryrun' => true]);
        ob_start();
        \cleaner_tokens\clean::clean_user_private_key();
        ob_end_clean();

        $this->assertTrue($DB->record_exists('user_private_key', []));
    }

    /**
     * Test cleaning OAuth2 data with dryrun disabled.
     */
    public function test_clean_oauth2_execute(): void {
        global $DB;

        $this->create_oauth2_issuer_records();
        $this->create_oauth2_system_account_records();
        $this->create_oauth2_access_token_records();
        $this->create_oauth2_refresh_token_records();

        $this->assertTrue($DB->record_exists('oauth2_issuer', []));

        $this->set_clean_options(['dryrun' => false]);
        ob_start();
        \cleaner_tokens\clean::clean_oauth2();
        ob_end_clean();

        $this->assertFalse($DB->record_exists('oauth2_issuer', []));
        $this->assertFalse($DB->record_exists('oauth2_system_account', []));
        $this->assertFalse($DB->record_exists('oauth2_access_token', []));
        $this->assertFalse($DB->record_exists('oauth2_refresh_token', []));
    }

    /**
     * Test cleaning OAuth2 data with dryrun enabled.
     */
    public function test_clean_oauth2_dryrun(): void {
        global $DB;

        $this->create_oauth2_issuer_records();
        $this->create_oauth2_system_account_records();
        $this->create_oauth2_access_token_records();
        $this->create_oauth2_refresh_token_records();

        $this->assertTrue($DB->record_exists('oauth2_issuer', []));

        $this->set_clean_options(['dryrun' => true]);
        ob_start();
        \cleaner_tokens\clean::clean_oauth2();
        ob_end_clean();

        $this->assertTrue($DB->record_exists('oauth2_issuer', []));
        $this->assertTrue($DB->record_exists('oauth2_system_account', []));
        $this->assertTrue($DB->record_exists('oauth2_access_token', []));
        $this->assertTrue($DB->record_exists('oauth2_refresh_token', []));
    }

    /**
     * Test cleaning extra tables with valid tables and dryrun disabled.
     */
    public function test_clean_extra_tables_valid_execute(): void {
        global $DB;

        $this->create_external_tokens_records();
        set_config('extratables', 'external_tokens', 'cleaner_tokens');

        $this->assertTrue($DB->record_exists('external_tokens', []));

        $this->set_clean_options(['dryrun' => false]);
        ob_start();
        \cleaner_tokens\clean::clean_extra_tables();
        ob_end_clean();

        $this->assertFalse($DB->record_exists('external_tokens', []));
    }

    /**
     * Test cleaning extra tables with valid tables and dryrun enabled.
     */
    public function test_clean_extra_tables_valid_dryrun(): void {
        global $DB;

        $this->create_external_tokens_records();
        set_config('extratables', 'external_tokens', 'cleaner_tokens');

        $this->assertTrue($DB->record_exists('external_tokens', []));

        $this->set_clean_options(['dryrun' => true]);
        ob_start();
        \cleaner_tokens\clean::clean_extra_tables();
        ob_end_clean();

        $this->assertTrue($DB->record_exists('external_tokens', []));
    }

    /**
     * Test cleaning extra tables with non-existent table.
     */
    public function test_clean_extra_tables_invalid_table(): void {
        global $DB;

        set_config('extratables', "nonexistent_table_xyz\nregistration_hubs", 'cleaner_tokens');
        $this->create_registration_hubs_records();

        $this->assertTrue($DB->record_exists('registration_hubs', []));

        $this->set_clean_options(['dryrun' => false]);
        ob_start();
        \cleaner_tokens\clean::clean_extra_tables();
        ob_end_clean();

        $this->assertFalse($DB->record_exists('registration_hubs', []));
    }

    /**
     * Test cleaning extra tables with empty config.
     */
    public function test_clean_extra_tables_empty_config(): void {
        global $DB;

        set_config('extratables', '', 'cleaner_tokens');

        $this->set_clean_options(['dryrun' => false]);
        ob_start();
        \cleaner_tokens\clean::clean_extra_tables();
        ob_end_clean();

        $this->assertTrue(true);
    }

    /**
     * Test cleaning extra tables with multiple tables.
     */
    public function test_clean_extra_tables_multiple(): void {
        global $DB;

        $this->create_external_tokens_records();
        $this->create_registration_hubs_records();
        set_config('extratables', "external_tokens\nregistration_hubs", 'cleaner_tokens');

        $this->assertTrue($DB->record_exists('external_tokens', []));
        $this->assertTrue($DB->record_exists('registration_hubs', []));

        $this->set_clean_options(['dryrun' => false]);
        ob_start();
        \cleaner_tokens\clean::clean_extra_tables();
        ob_end_clean();

        $this->assertFalse($DB->record_exists('external_tokens', []));
        $this->assertFalse($DB->record_exists('registration_hubs', []));
    }

    /**
     * Test execute method calls all cleaning methods.
     */
    public function test_execute(): void {
        global $DB;

        $this->create_password_history_records();
        $this->create_password_resets_records();
        $this->create_external_tokens_records();
        $this->create_registration_hubs_records();
        $this->create_user_private_key_records();
        $this->create_oauth2_issuer_records();
        $this->create_oauth2_system_account_records();
        $this->create_oauth2_access_token_records();
        $this->create_oauth2_refresh_token_records();

        $this->set_clean_options(['dryrun' => false]);
        ob_start();
        \cleaner_tokens\clean::execute();
        ob_end_clean();

        $this->assertFalse($DB->record_exists('user_password_history', []));
        $this->assertFalse($DB->record_exists('user_password_resets', []));
        $this->assertFalse($DB->record_exists('external_tokens', []));
        $this->assertFalse($DB->record_exists('registration_hubs', []));
        $this->assertFalse($DB->record_exists('user_private_key', []));
        $this->assertFalse($DB->record_exists('oauth2_issuer', []));
        $this->assertFalse($DB->record_exists('oauth2_system_account', []));
        $this->assertFalse($DB->record_exists('oauth2_access_token', []));
        $this->assertFalse($DB->record_exists('oauth2_refresh_token', []));
    }

    /**
     * Test execute method with dryrun enabled.
     */
    public function test_execute_dryrun(): void {
        global $DB;

        $this->create_password_history_records();
        $this->create_password_resets_records();
        $this->create_external_tokens_records();
        $this->create_registration_hubs_records();
        $this->create_user_private_key_records();
        $this->create_oauth2_issuer_records();

        $this->set_clean_options(['dryrun' => true]);
        ob_start();
        \cleaner_tokens\clean::execute();
        ob_end_clean();

        $this->assertTrue($DB->record_exists('user_password_history', []));
        $this->assertTrue($DB->record_exists('user_password_resets', []));
        $this->assertTrue($DB->record_exists('external_tokens', []));
        $this->assertTrue($DB->record_exists('registration_hubs', []));
        $this->assertTrue($DB->record_exists('user_private_key', []));
        $this->assertTrue($DB->record_exists('oauth2_issuer', []));
    }

    /**
     * Helper method to set clean class options via reflection.
     *
     * @param array $options The options to set.
     */
    private function set_clean_options(array $options): void {
        $reflection = new \ReflectionClass(\cleaner_tokens\clean::class);
        $property = $reflection->getProperty('options');
        $property->setAccessible(true);
        $currentoptions = $property->getValue(null);
        $property->setValue(null, array_merge($currentoptions, $options));
    }
}
