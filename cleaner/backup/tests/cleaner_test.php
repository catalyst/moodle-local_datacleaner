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
 * Tests for the cleaner.
 *
 * @package    cleaner_backup
 * @copyright  2020 Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace cleaner_backup\tests;

defined('MOODLE_INTERNAL') || die();

class cleaner_backup_test extends \advanced_testcase {

    public function delete_provider() {
        // Array of filename, deleted.
        return [
            ['myfile.txt', false],
            ['myfile.mbz', true],
            ['myfile.mbzz', false],
            ['mbz.txt', false],
            ['mbz.mbz', true],
            ['myfile.mbz.txt', false],
            ['myfile.mbz.mbz', true],
        ];
    }

    /**
     * @dataProvider delete_provider
     */
    public function test_delete_backups($filename, $deleted) {
        global $DB;
        $this->resetAfterTest();

        // Create a course, and create a file in the course area.
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $storage = get_file_storage();

        $file = $storage->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'cleaner_backup',
            'filearea' => 'backup',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename
        ], 'content');

        \cleaner_backup\clean::delete_backups();

        $this->assertEquals($deleted, !$storage->file_exists(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        ));

        // Purge the files table to be sure its fresh.
        $DB->delete_records('files', []);

        // Now recreate this file, and process a fast delete.
        $file = $storage->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'cleaner_backup',
            'filearea' => 'backup',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename
        ], 'content');

        set_config('fastdelete', 1, 'cleaner_backup');
        \cleaner_backup\clean::delete_backups();

        $this->assertEquals($deleted, !$storage->file_exists(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        ));
    }
}
