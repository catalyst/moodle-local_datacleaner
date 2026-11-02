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

namespace cleaner_users;

/**
 * Unit tests for the username handling of the users cleaner.
 *
 * @package    cleaner_users
 * @copyright  2026 ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \cleaner_users\clean
 */
final class username_clean_test extends \advanced_testcase {
    /**
     * Run the cleaner for real and discard its console output.
     */
    private function run_cleaner(): void {
        new clean(['dryrun' => false, 'verbose' => false]);
        ob_start();
        try {
            clean::execute();
        } finally {
            ob_end_clean();
        }
    }

    /**
     * Function execute() must not abort when an account already holds a target username.
     */
    public function test_execute_renames_account_holding_target_username(): void {
        global $DB;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user(['username' => 'production.user']);
        $squatter = $this->getDataGenerator()->create_user(['username' => clean::USERNAME_PREFIX . $user->id]);

        $this->run_cleaner();

        $this->assertSame(clean::USERNAME_PREFIX . $user->id, $DB->get_field('user', 'username', ['id' => $user->id]));
        $this->assertSame(clean::USERNAME_PREFIX . $squatter->id, $DB->get_field('user', 'username', ['id' => $squatter->id]));
    }

    /**
     * Function execute() must rename cleaned users and keep the usernames of retained users.
     */
    public function test_execute_keeps_username_of_retained_users(): void {
        global $DB;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user(['username' => 'production.user']);
        $retained = $this->getDataGenerator()->create_user(['username' => 'retained.user']);
        set_config('keepusernames', 'retained.user', 'cleaner_users');

        $this->run_cleaner();

        $this->assertSame(clean::USERNAME_PREFIX . $user->id, $DB->get_field('user', 'username', ['id' => $user->id]));
        $this->assertSame('retained.user', $DB->get_field('user', 'username', ['id' => $retained->id]));
    }
}
