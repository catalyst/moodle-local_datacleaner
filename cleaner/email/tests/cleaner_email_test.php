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
 * Testcase for cleaner_email
 *
 * @package    cleaner_email
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  2017 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use cleaner_email\clean;

defined('MOODLE_INTERNAL') || die();

/**
 * Testcase for cleaner_email
 *
 * @package    cleaner_email
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  2017 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleaner_email_test extends advanced_testcase {

    /** @var stdClass $config */
    private $config;

    /** @var array $users */
    private $users;

    /** Number of users created */
    const MAX_USERS = 10;

    /**
     * Create some test users.
     */
    protected function setUp() {
        parent::setup();
        $this->resetAfterTest(true);

        for ($i = 0; $i < self::MAX_USERS; $i++) {
            $this->users[] = $this->getDataGenerator()->create_user();
        }

        // Set some defaults.
        $this->config = new stdClass();
        $this->config->emailsuffix = '.test';
        $this->config->emailsuffixignore = '';
    }

    /**
     * Test appending the suffix
     */
    public function test_cleaner_email_suffix_append() {
        global $DB;

        // Obtain the list of generated users.
        foreach ($this->users as $user) {
            $this->assertNotContains('.test', $user->email);
        }

        // Lets clean!
        clean::execute_appendsuffix($this->config, false, false);

        // Check that suffix exists.
        foreach ($this->users as $user) {
            $record = $DB->get_record('user', ['id' => $user->id]);
            $this->assertContains('.test', $record->email);
        }
    }

    /**
     * Test appending the suffix and ignoring a basic pattern.
     */
    public function test_cleaner_email_suffix_ignore() {
        global $DB;

        // Prevent emails of this pattern to have the suffix.
        $this->config->emailsuffixignore = 'example.com';

        // Obtain the list of generated users.
        foreach ($this->users as $user) {
            $this->assertNotContains('.test', $user->email);
        }

        // Lets clean!
        clean::execute_appendsuffix($this->config, false, false);

        // Check that suffix does not exist for users.
        foreach ($this->users as $user) {
            $record = $DB->get_record('user', ['id' => $user->id]);
            $this->assertNotContains('.test', $record->email);
        }
    }

    /**
     * Test appending the suffix and ignoring a regular expression.
     *
     * @param string $input
     * @param string $expected
     * @param string $suffix
     * @param string $ignorepattern
     *
     * @dataProvider provider_for_cleaner_email_suffix_ignore_pattern
     */
    public function test_cleaner_email_suffix_ignore_pattern($input, $expected, $suffix, $ignorepattern) {
        global $DB;

        $this->config->emailsuffix = $suffix;
        $this->config->emailsuffixignore = $ignorepattern;

        $user = $this->getDataGenerator()->create_user(['email' => $input]);

        // Lets clean!
        clean::execute_appendsuffix($this->config, false, false);

        $record = $DB->get_record('user', ['id' => $user->id]);
        $this->assertEquals($expected, $record->email);
    }

    /**
     * Provider for test_cleaner_email_suffix_ignore_pattern.
     *
     * The array values are,
     *
     * 1. Input.
     * 2. Expected output.
     * 3. Suffix.
     * 4. Ignore pattern.
     *
     * @return array
     */
    public function provider_for_cleaner_email_suffix_ignore_pattern() {
        return [
            ['user@example.com',     'user@example.com.suffix',     '.suffix',   'moodle.com'],
            ['user@email.com',       'user@email.com.suffix',       '.suffix',   'emailsuffix.com'],
            ['user@email+alias.com', 'user@email+alias.com.suffix', '.suffix',   'email+alias'],
            ['user@email+alias.com', 'user@email+alias.com',       ' .nosuffix', 'email[\+]alias'],
            ['user@email+alias.com', 'user@email+alias.com',        '.nosuffix', 'email.alias'],
            ['user@example.com',     'user@example.com',            '.nosuffix', 'example.com'],
        ];
    }
}
