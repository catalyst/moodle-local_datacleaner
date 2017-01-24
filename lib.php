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
 * @package    cleaner
 * @copyright  2015 Catalyst IT
 * @author     Nigel Cunningham <nigelc@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This function should be called instead of core_course_external::get_categories().
 *
 * See Issue #2.
 *
 * @return array Categories.
 */
function local_datacleaner_get_categories() {
    global $DB;
    static $categories = null;

    if (is_null($categories)) {
        // Fetch from database.
        $categories = $DB->get_records('course_categories');

        // Sort by path.
        usort($categories, function ($a, $b) {
            return strcmp($a->path, $b->path);
        });

        // Convert all to array.
        for ($i = 0; $i < count($categories); $i++) {
            $categories[$i] = (array)$categories[$i];
        }
    }

    return $categories;
}
