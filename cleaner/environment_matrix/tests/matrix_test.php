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

namespace cleaner_environment_matrix;

use advanced_testcase;
use cleaner_environment_matrix\local\matrix;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the environment matrix redaction of production values.
 *
 * @package cleaner_environment_matrix
 * @author Jason den Dulk <jasondendulk@catalyst-au.net>
 * @copyright 2026 Catalyst IT
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(matrix::class)]
final class matrix_test extends advanced_testcase {
    /** @var string A secret value that must not be displayed on production. */
    const SECRET = 'zzunittestsecretzz';

    /** @var string A core secret setting (password setting, and in the sensible settings list). */
    const CORE_SECRET = 'smtppass';

    /** @var string A plugin with a secret setting. */
    const PLUGIN = 'tiny_premium';

    /** @var string A plugin secret setting (password setting, and in the sensible settings list). */
    const PLUGIN_SECRET = 'apikey';

    /** @var string A secret-like setting name to test independently of the sensible settings list. */
    const SECRET_LIKE = 'proxypassword';

    /** @var string A plugin used for settings that are not in the admin tree. */
    const NON_TREE_PLUGIN = 'unittest_matrix_plugin';

    /** @var string A core setting that is not a secret. */
    const CORE_NON_SECRET = 'fullnamedisplay';

    /** @var string A core setting forced in config.php. */
    const FORCED = 'unittestforced';

    /** @var string The production site URL, when this is not the production site. */
    const PROD_URL = 'https://prod.example.com';

    /** @var string A URL that does not match the production site URL, with HTML to check escaping. */
    const OTHER_URL = 'https://other.example.com/<b>';

    /** @var int The id of a non-production environment. */
    const ENV_ID = 5;

    /** @var string A saved non-production value. */
    const SAVED_VALUE = 'fakepassword';

    /**
     * Set up the secret values.
     */
    protected function setUp(): void {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();

        set_config(self::CORE_SECRET, self::SECRET);
        set_config(self::PLUGIN_SECRET, self::SECRET, self::PLUGIN);
        $CFG->config_php_settings[self::FORCED] = 'forced';
    }

    /**
     * Make this site the production site, or not.
     *
     * @param bool $isprod
     */
    private function set_production(bool $isprod): void {
        global $CFG;
        $prod = $isprod ? $CFG->wwwroot : self::PROD_URL;
        set_config('original_wwwroot', base64_encode($prod));
    }

    /**
     * Save a non-production value for the core secret setting.
     */
    private function insert_saved_value(): void {
        global $DB;
        $DB->insert_record('cleaner_environment_matrixd', [
            'envid' => self::ENV_ID,
            'plugin' => 'core',
            'config' => self::CORE_SECRET,
            'value' => self::SAVED_VALUE,
        ]);
    }

    /**
     * Secrets and forced values are redacted on production.
     */
    public function test_should_redact_production(): void {
        $this->set_production(true);

        $this->assertTrue(matrix::should_redact(self::CORE_SECRET, 'core'));
        $this->assertTrue(matrix::should_redact(self::PLUGIN_SECRET, self::PLUGIN));
        $this->assertTrue(matrix::should_redact(self::FORCED, 'core'));
        $this->assertFalse(matrix::should_redact(self::CORE_NON_SECRET, 'core'));

        $this->assertEquals(matrix::REDACTED, matrix::redact_value(self::SECRET, self::CORE_SECRET, 'core'));
        $this->assertEquals('', matrix::redact_value('', self::CORE_SECRET, 'core'));
        $this->assertEquals(self::SAVED_VALUE, matrix::redact_value(self::SAVED_VALUE, self::CORE_NON_SECRET, 'core'));
    }

    /**
     * Nothing is redacted on non-production sites.
     */
    public function test_should_redact_non_production(): void {
        $this->set_production(false);

        $this->assertFalse(matrix::should_redact(self::CORE_SECRET, 'core'));
        $this->assertFalse(matrix::should_redact(self::PLUGIN_SECRET, self::PLUGIN));
        $this->assertFalse(matrix::should_redact(self::FORCED, 'core'));
        $this->assertEquals(self::SECRET, matrix::redact_value(self::SECRET, self::CORE_SECRET, 'core'));
    }

    /**
     * Search results redact secrets on production.
     */
    public function test_search_redacts_production(): void {
        $this->set_production(true);

        $result = matrix::search(self::CORE_SECRET);
        $this->assertEquals(matrix::REDACTED, $result['core'][self::CORE_SECRET]->value);

        $result = matrix::search(self::PLUGIN_SECRET);
        $this->assertEquals(matrix::REDACTED, $result[self::PLUGIN][self::PLUGIN_SECRET]->value);

        set_config(self::SECRET_LIKE, self::SECRET);
        $result = matrix::search(self::SECRET_LIKE);
        $this->assertEquals(matrix::REDACTED, $result['core'][self::SECRET_LIKE]->value);
    }

    /**
     * Secret-like configs outside the admin setting tree are found and redacted on production.
     */
    public function test_search_redacts_non_tree_secret_like_config(): void {
        $this->set_production(true);
        $configname = 'unittestmatrixproxypassword';
        set_config($configname, self::SECRET, self::NON_TREE_PLUGIN);

        $result = matrix::search($configname);
        $this->assertEquals(matrix::REDACTED, $result[self::NON_TREE_PLUGIN][$configname]->value);

        $result = matrix::search(self::SECRET);
        $this->assertArrayNotHasKey($configname, $result[self::NON_TREE_PLUGIN] ?? []);
    }

    /**
     * A search string with multiple words matches config names containing those words together.
     */
    public function test_search_multiple_words(): void {
        $this->set_production(false);
        set_config(self::SECRET_LIKE, 'proxy value');

        $result = matrix::search('proxy pass');
        $this->assertArrayHasKey(self::SECRET_LIKE, $result['core'] ?? []);
    }

    /**
     * Searching for a term that matches configs in and outside the admin tree returns both.
     */
    public function test_search_multiple_configs(): void {
        $this->set_production(false);
        $nontreeconfig = 'unittest_smtppass';
        set_config($nontreeconfig, 'non-tree value', self::NON_TREE_PLUGIN);

        $result = matrix::search(self::CORE_SECRET);
        $this->assertArrayHasKey(self::CORE_SECRET, $result['core']);
        $this->assertEquals('non-tree value', $result[self::NON_TREE_PLUGIN][$nontreeconfig]->value);
    }

    /**
     * Searching for a secret value on production does not reveal which setting it belongs to.
     */
    public function test_search_by_secret_value_production(): void {
        $this->set_production(true);

        $result = matrix::search(self::SECRET);
        $this->assertArrayNotHasKey(self::CORE_SECRET, $result['core'] ?? []);
        $this->assertArrayNotHasKey(self::PLUGIN_SECRET, $result[self::PLUGIN] ?? []);
    }

    /**
     * Search results are not redacted on non-production sites.
     */
    public function test_search_non_production(): void {
        $this->set_production(false);

        $result = matrix::search(self::CORE_SECRET);
        $this->assertEquals(self::SECRET, $result['core'][self::CORE_SECRET]->value);

        $result = matrix::search(self::SECRET);
        $this->assertEquals(self::SECRET, $result[self::PLUGIN][self::PLUGIN_SECRET]->value);
    }

    /**
     * On production, the production column is redacted, but saved non-production values are not.
     */
    public function test_get_matrix_data_production(): void {
        $this->set_production(true);
        $this->insert_saved_value();

        $data = matrix::get_matrix_data();
        $this->assertEquals(matrix::REDACTED, $data['core'][self::CORE_SECRET]['-1']->value);
        $this->assertEquals(self::SAVED_VALUE, $data['core'][self::CORE_SECRET][self::ENV_ID]->value);
    }

    /**
     * There is no production column on non-production sites, and saved values are not redacted.
     */
    public function test_get_matrix_data_non_production(): void {
        $this->set_production(false);
        $this->insert_saved_value();

        $data = matrix::get_matrix_data();
        $this->assertArrayNotHasKey('-1', $data['core'][self::CORE_SECRET]);
        $this->assertEquals(self::SAVED_VALUE, $data['core'][self::CORE_SECRET][self::ENV_ID]->value);
    }

    /**
     * The data cleaner and Environment bar production URLs must be set and equal.
     */
    public function test_get_production_config_warning(): void {
        if (!matrix::environmentbar_exists()) {
            $this->markTestSkipped('local_envbar is not installed.');
        }

        \local_envbar\local\envbarlib::setprodwwwroot(self::PROD_URL);

        // Consistent.
        set_config('original_wwwroot', base64_encode(self::PROD_URL));
        $this->assertNull(matrix::get_production_config_warning());

        // Mismatched.
        set_config('original_wwwroot', base64_encode(self::OTHER_URL));
        $warning = matrix::get_production_config_warning();
        $this->assertStringContainsString(self::OTHER_URL, $warning);
        $this->assertStringContainsString(self::PROD_URL, $warning);
        $this->assertStringContainsString(s(self::OTHER_URL), matrix::get_production_config_warning(true));

        // Data cleaner URL not set.
        unset_config('original_wwwroot');
        $this->assertEquals(
            get_string('prodconfig_nooriginalwwwroot', 'cleaner_environment_matrix'),
            matrix::get_production_config_warning()
        );

        // Environment bar URL not set.
        set_config('original_wwwroot', base64_encode(self::PROD_URL));
        unset_config('prodwwwroot', 'local_envbar');
        $this->assertEquals(
            get_string('prodconfig_noenvbarprodwwwroot', 'cleaner_environment_matrix'),
            matrix::get_production_config_warning()
        );
    }
}
