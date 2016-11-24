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
 * Unit tests for clean
 *
 * @package     local_datacleaner
 * @subpackage  cleaner_users
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use cleaner_users\clean;

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden');

/**
 * Tests
 */
class users_clean_test extends advanced_testcase {
    /** @test */
    public function test_it_should_scramble_users_without_sql_problems() {
        global $CFG, $DB;
        $this->resetAfterTest(true);

        // Fake users table parameters.
        $faketablename = 'fake_users_table';
        $faketablefields = ['firstname', 'firstnamephonetic', 'alternatename'];

        // Use users table as model.
        $dbmanager = $DB->get_manager();
        $xmldbfile = clean::load_xmldbfile($CFG->dirroot.'/lib/db/install.xml');
        $xmldbstructure = $xmldbfile->getStructure();
        $userstructure = $xmldbstructure->getTable('user');
        $userkeys = $userstructure->getKeys();

        // Create fake table.
        $temptablestruct = new xmldb_table($faketablename);
        $fieldlist = [$userstructure->getField('id')];
        foreach ($faketablefields as $field) {
            $fieldlist[] = $userstructure->getField($field);
        }
        $temptablestruct->setFields($fieldlist);
        $temptablestruct->setKeys([$userkeys[0]]);
        $dbmanager->create_temp_table($temptablestruct);

        // Add dummy data.
        $DB->insert_record($faketablename, (object)[
            'firstname'         => 'firstname',
            'firstnamephonetic' => 'firstnamephonetic',
            'alternatename'     => 'alternatename',
        ]);

        // Run scrambler.
        clean::randomise_fields($faketablename, $faketablefields, 1, 'IN(1)', [1]);

        // Fetch data.
        $output = $DB->get_records($faketablename);

        // Drop fake table.
        $dbmanager->drop_table($temptablestruct);

        // We are not checking the algorithm, do not check how it was scrambled.
        self::assertCount(1, $output);
    }
}
