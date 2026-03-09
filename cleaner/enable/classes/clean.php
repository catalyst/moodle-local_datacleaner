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

namespace cleaner_enable;

/**
 * Data cleaner class for enabling/disabling cleaners.
 *
 * Reads the configured enable/disable matrix and applies it by writing the
 * "enabled" config key for each cleaner subplugin. This is useful in a
 * post-wash context where a database restore may have overwritten the desired
 * enabled states; running this cleaner early in the post phase restores them
 * so they are correct for subsequent runs.
 *
 * @package    cleaner_enable
 * @copyright  2026 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clean extends \local_datacleaner\clean {

    const TASK = 'Applying cleaner enable/disable settings';

    /**
     * Execute the cleaning process.
     *
     * Iterates over every cleaner subplugin (except this one) and applies the
     * enabled/disabled state stored in the matrix configuration.
     */
    public static function execute() {
        $dryrun  = (bool) self::$options['dryrun'];
        $verbose = (bool) self::$options['verbose'];

        $cleaners = \local_datacleaner\plugininfo\cleaner::get_plugins_by_sortorder();

        $applicable = array_filter($cleaners, function ($c) {
            return $c->name !== 'enable';
        });

        self::new_task(count($applicable));

        foreach ($applicable as $cleaner) {
            $stored = get_config('cleaner_enable', 'enabled_' . $cleaner->name);

            // If no override has been configured, leave the cleaner's own setting alone.
            if ($stored === false) {
                if ($verbose) {
                    mtrace("  No override configured for cleaner_{$cleaner->name}, skipping.");
                }
                self::next_step();
                continue;
            }

            $value = (int)(bool)$stored;
            $label = $value ? 'enabled' : 'disabled';

            if ($verbose) {
                mtrace("  cleaner_{$cleaner->name}: {$label}");
            }

            if (!$dryrun) {
                set_config('enabled', $value, 'cleaner_' . $cleaner->name);
            } else {
                mtrace("  Would set cleaner_{$cleaner->name}/enabled = {$value}");
            }

            self::next_step();
        }
    }
}
