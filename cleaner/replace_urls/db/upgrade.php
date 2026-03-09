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
 * Upgrade steps for cleaner_replace_urls.
 *
 * @package    cleaner_replace_urls
 * @copyright  2026 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the cleaner_replace_urls plugin.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_cleaner_replace_urls_upgrade($oldversion) {
    if ($oldversion < 2026030800) {
        // Clear the default 'http://localhost' newsiteurl so it falls back to wwwroot.
        if (get_config('cleaner_replace_urls', 'newsiteurl') === 'http://localhost') {
            set_config('newsiteurl', '', 'cleaner_replace_urls');
        }

        // Clear the placeholder 'http://' origsiteurl so envbar auto-detection can take over.
        if (get_config('cleaner_replace_urls', 'origsiteurl') === 'http://') {
            set_config('origsiteurl', '', 'cleaner_replace_urls');
        }

        upgrade_plugin_savepoint(true, 2026030800, 'cleaner', 'replace_urls');
    }

    return true;
}
