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
 * Settings for the custom sql post cleaner.
 *
 * @package    cleaner_custom_sql_post
 * @copyright   2019 Srdjan Janković <srdjan@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/classes/admin_setting_sql_textarea.php');

if (!$ADMIN->fulltree) {
    return;
}

$settings->add(
    new \cleaner_custom_sql_post\admin_setting_sql_textarea(
        'cleaner_custom_sql_post/sql',
        new lang_string('sql', 'cleaner_custom_sql_post'),
        new lang_string('sqldesc', 'cleaner_custom_sql_post'),
        '',
        PARAM_RAW
    )
);

$settings->add(
    new admin_setting_heading(
        'cleaner_custom_sql_post/env_heading',
        new lang_string('environmentsql', 'cleaner_custom_sql_post'),
        ''
    )
);

if (class_exists('\local_envbar\local\envbarlib')) {
    $environments = \local_envbar\local\envbarlib::get_records();
    foreach ($environments as $env) {
        $key = \cleaner_custom_sql_post\clean::get_env_config_key($env->showtext);
        $settings->add(
            new \cleaner_custom_sql_post\admin_setting_sql_textarea(
                'cleaner_custom_sql_post/' . $key,
                "SQL for '{$env->showtext}'",
                new lang_string('sqlenvironmentdesc', 'cleaner_custom_sql_post', $env->matchpattern),
                '',
                PARAM_RAW
            )
        );
    }
} else {
    $settings->add(
        new admin_setting_heading(
            'cleaner_custom_sql_post/env_note',
            '',
            new lang_string('environmentsqlnote', 'cleaner_custom_sql_post')
        )
    );
}
