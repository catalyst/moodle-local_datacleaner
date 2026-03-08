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

namespace cleaner_custom_sql_post;

/**
 * Data cleaner class for custom sql post.
 *
 * @package    cleaner_custom_sql_post
 * @copyright  2019 Srdjan Janković <srdjan@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clean extends \local_datacleaner\clean {
    /**
     * Task.
     */
    const TASK = 'Custom SQL query in post phase';

    /**
     * Generate the config key for an environment's SQL setting.
     *
     * @param string $showtext The environment's display name.
     * @return string
     */
    public static function get_env_config_key(string $showtext): string {
        return 'sql_env_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($showtext));
    }

    /**
     * Execute the cleaning process.
     */
    public static function execute() {
        global $CFG, $DB;

        $dryrun = (bool)self::$options['dryrun'];
        $verbose = (bool)self::$options['verbose'];

        $config = get_config('cleaner_custom_sql_post');

        if (!empty($config->sql)) {
            if ($verbose || $dryrun) {
                mtrace(($dryrun ? '[DRYRUN] ' : '') . 'Executing global post-wash SQL.');
            }
            self::execute_sql($config->sql);
        }

        if (!class_exists('\local_envbar\local\envbarlib')) {
            return;
        }

        $environments = \local_envbar\local\envbarlib::get_records();
        foreach ($environments as $env) {
            if ($env->matchpattern !== $CFG->wwwroot) {
                continue;
            }

            $key = self::get_env_config_key($env->showtext);
            if (empty($config->$key)) {
                break;
            }

            if ($verbose || $dryrun) {
                mtrace(($dryrun ? '[DRYRUN] ' : '') . "Executing environment SQL for: {$env->showtext} ({$env->matchpattern})");
            }
            self::execute_sql($config->$key);
            break;
        }
    }
}
