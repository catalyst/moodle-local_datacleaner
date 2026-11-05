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

namespace local_datacleaner;

/**
 * Library functions for data cleaner.
 *
 * @package local_datacleaner
 * @author Jason den Dulk <jasondendulk@catalyst-au.net>
 * @copyright 2026 Catalyst IT
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib {
    /**
     * Has the production site URL (original_wwwroot) been saved?
     *
     * @return bool
     */
    public static function is_original_wwwroot_set(): bool {
        global $CFG;
        return !empty($CFG->original_wwwroot);
    }

    /**
     * Get the saved production site URL (original_wwwroot).
     *
     * @return string|null The decoded URL, or null if it has not been set.
     */
    public static function get_original_wwwroot(): ?string {
        global $CFG;
        return self::is_original_wwwroot_set() ? base64_decode($CFG->original_wwwroot) : null;
    }

    /**
     * Is the current site URL the saved production site URL (original_wwwroot)?
     *
     * @return bool True if original_wwwroot is set and matches $CFG->wwwroot.
     */
    public static function is_original_wwwroot(): bool {
        global $CFG;
        return self::is_original_wwwroot_set() && $CFG->wwwroot === self::get_original_wwwroot();
    }
}
