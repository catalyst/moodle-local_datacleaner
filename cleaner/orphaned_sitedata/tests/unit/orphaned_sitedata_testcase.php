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
 * orphaned_sitedata testcase.
 *
 * @package     cache_cleaner_test
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_orphaned_sitedata\tests\unit;

use advanced_testcase;
use context_course;
use ReflectionMethod;
use stored_file;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/adminlib.php');

/**
 * orphaned_sitedata testcase.
 *
 * @package     cache_cleaner_test
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @SuppressWarnings(public) Allow as many methods as needed.
 */
class orphaned_sitedata_testcase extends advanced_testcase {
    protected function execute($cleaner) {
        ob_start();
        $cleaner->execute();
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    protected function create_file($component, $filepath, $filename) {
        $syscontext = context_course::instance(1);
        $filerecord = [
            'contextid' => $syscontext->id,
            'component' => $component,
            'filearea'  => 'unittest',
            'itemid'    => 0,
            'filepath'  => $filepath,
            'filename'  => $filename,
        ];
        $fs = get_file_storage();
        return $fs->create_file_from_string($filerecord, 'backup data');
    }

    protected function file_is_readable(stored_file $file) {
        if (class_exists('\core_files\filestorage\file_system')) {
            $filesystem = \core_files\filestorage\file_system::instance();
            $filesystem->cleanup_trash(); // So is_readable wont retrieve it.
            $isreadable = $filesystem->is_readable($file);
        } else {
            // Let's be a little naughty and hack access the protected method in stored_file.
            $reflection = new ReflectionMethod(stored_file::class, 'get_pathname_by_contenthash');
            $reflection->setAccessible(true);
            $path = $reflection->invoke($file);
            $isreadable = is_readable($path);
        }
        return $isreadable;
    }
}
