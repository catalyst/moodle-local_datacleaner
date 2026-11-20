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

namespace cleaner_users\tests;

use cleaner_users\clean;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for cleaner_users.
 *
 * @package    cleaner_users
 * @copyright  2015 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \cleaner_users\clean
 */
class users_test extends \advanced_testcase {

    /**
     * Create users with distinct profile field values so scrambling can redistribute them.
     *
     * @param int $count Number of users to create.
     * @return \stdClass[]
     */
    private function create_users_with_profile_data(int $count = 10): array {
        $users = [];
        for ($i = 0; $i < $count; $i++) {
            $users[] = $this->getDataGenerator()->create_user([
                'firstname' => 'First' . $i,
                'lastname' => 'Last' . $i,
                'city' => 'City' . $i,
                'institution' => 'Institution' . $i,
                'department' => 'Department' . $i,
                'middlename' => 'Middle' . $i,
                'alternatename' => 'Alt' . $i,
            ]);
        }
        return $users;
    }

    /**
     * Run the users cleaner (capture CLI output).
     *
     * @param bool $dryrun Whether to run in dry-run mode.
     */
    private function run_cleaner(bool $dryrun = false): void {
        new clean(['dryrun' => $dryrun, 'verbose' => false]);
        ob_start();
        clean::execute();
        ob_end_clean();
    }

    /**
     * When renameusers is disabled, first and last names are scrambled, not replaced.
     */
    public function test_scramble_first_and_last_names_when_rename_disabled(): void {
        global $DB;
        $this->resetAfterTest(true);

        set_config('renameusers', 0, 'cleaner_users');
        set_config('keepsiteadmins', 1, 'cleaner_users');
        $users = $this->create_users_with_profile_data();
        $original = [];
        foreach ($users as $user) {
            $original[$user->id] = [
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
            ];
        }

        // Scrambler builds its value pool from the whole user table, not only cleaned rows.
        $firstnamepool = $DB->get_fieldset_select('user', 'firstname', 'deleted = 0');
        $lastnamepool = $DB->get_fieldset_select('user', 'lastname', 'deleted = 0');

        $this->run_cleaner();

        $firstnameprefix = clean::FIRST_NAME_PREFIX;
        $lastnameprefix = clean::LAST_NAME_PREFIX;
        $newbyid = [];

        foreach ($users as $user) {
            $record = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);

            // Not the rename/replace path.
            $this->assertNotSame($firstnameprefix . $user->id, $record->firstname);
            $this->assertNotSame($lastnameprefix . $user->id, $record->lastname);

            // Values come from the existing pool (redistributed), not newly invented strings.
            $this->assertContains($record->firstname, $firstnamepool);
            $this->assertContains($record->lastname, $lastnamepool);

            $newbyid[$user->id] = [
                'firstname' => $record->firstname,
                'lastname' => $record->lastname,
            ];
        }

        // At least one reassignment happened (avoids requiring every row to move).
        $this->assertNotEquals(
            array_column($original, 'firstname'),
            array_column($newbyid, 'firstname'),
            'Expected firstnames to be reassigned across users.'
        );
    }

    /**
     * When renameusers is enabled, first and last names are replaced and not scrambled afterwards.
     */
    public function test_replace_first_and_last_names_when_rename_enabled(): void {
        global $DB;
        $this->resetAfterTest(true);

        set_config('renameusers', 1, 'cleaner_users');
        $users = $this->create_users_with_profile_data();

        $this->run_cleaner();

        $firstnameprefix = clean::FIRST_NAME_PREFIX;
        $lastnameprefix = clean::LAST_NAME_PREFIX;

        foreach ($users as $user) {
            $record = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
            $this->assertSame($firstnameprefix . $user->id, $record->firstname);
            $this->assertSame($lastnameprefix . $user->id, $record->lastname);
        }
    }

    /**
     * Other profile fields are still scrambled when renameusers is enabled.
     */
    public function test_other_fields_still_scrambled_when_rename_enabled(): void {
        global $DB;
        $this->resetAfterTest(true);

        set_config('renameusers', 1, 'cleaner_users');
        $users = $this->create_users_with_profile_data();
        $originalcities = array_column($users, 'city');

        // Scrambler builds its value pool from the whole user table, not only cleaned rows.
        $citypool = $DB->get_fieldset_select('user', 'city', 'deleted = 0');

        $this->run_cleaner();

        $newcities = [];
        foreach ($users as $user) {
            $record = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
            // Names must still be the replaced values (not overwritten by scramble).
            $this->assertSame(clean::FIRST_NAME_PREFIX . $user->id, $record->firstname);
            $this->assertSame(clean::LAST_NAME_PREFIX . $user->id, $record->lastname);
            $this->assertContains($record->city, $citypool);
            $newcities[] = $record->city;
        }

        $this->assertNotEquals(
            $originalcities,
            $newcities,
            'Expected cities to be reassigned across users.'
        );
    }

    /**
     * Dry run must not modify user records.
     */
    public function test_dryrun_does_not_modify_users(): void {
        global $DB;
        $this->resetAfterTest(true);

        set_config('renameusers', 1, 'cleaner_users');
        $users = $this->create_users_with_profile_data(2);

        $this->run_cleaner(true);

        foreach ($users as $user) {
            $record = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
            $this->assertSame($user->firstname, $record->firstname);
            $this->assertSame($user->lastname, $record->lastname);
            $this->assertSame($user->username, $record->username);
            $this->assertSame($user->email, $record->email);
        }
    }
}
