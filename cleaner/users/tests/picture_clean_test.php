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
 * Unit tests for the profile picture handling of the users cleaner.
 *
 * @package    cleaner_users
 * @copyright  2026 ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \cleaner_users\clean
 */
final class picture_clean_test extends \advanced_testcase {
    /**
     * Create a user with a stored profile picture.
     *
     * @param string $username The username of the new user.
     * @return int The user context id.
     */
    private function create_user_with_picture(string $username): int {
        global $DB;

        $user = $this->getDataGenerator()->create_user(['username' => $username]);
        $contextid = \context_user::instance($user->id)->id;
        $file = get_file_storage()->create_file_from_string([
            'contextid' => $contextid,
            'component' => 'user',
            'filearea' => 'icon',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'f1.png',
        ], 'Profile photo');
        $DB->set_field('user', 'picture', $file->get_id(), ['id' => $user->id]);

        return $contextid;
    }

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
     * Function execute() must delete the stored profile pictures of cleaned users.
     */
    public function test_execute_deletes_pictures_of_cleaned_users(): void {
        $this->resetAfterTest();
        $contextid = $this->create_user_with_picture('production.user');

        $this->run_cleaner();

        $this->assertTrue(get_file_storage()->is_area_empty($contextid, 'user', 'icon', false, false));
    }

    /**
     * Function execute() must keep the profile pictures of retained users.
     */
    public function test_execute_keeps_pictures_of_retained_users(): void {
        $this->resetAfterTest();
        $contextid = $this->create_user_with_picture('retained.user');
        $this->getDataGenerator()->create_user(['username' => 'production.user']);
        set_config('keepusernames', 'retained.user', 'cleaner_users');

        $this->run_cleaner();

        $this->assertFalse(get_file_storage()->is_area_empty($contextid, 'user', 'icon', false, false));
    }
}
