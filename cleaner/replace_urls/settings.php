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
 * @package    cleaner_replace_urls
 * @copyright  2015 Catalyst IT
 * @author     Nigel Cunningham
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (!$ADMIN->fulltree) {
    return;
}

$settings->add(new admin_setting_configtext('cleaner_replace_urls/origsiteurl',
            new lang_string('origsiteurl', 'cleaner_replace_urls'),
            new lang_string('origsiteurldesc', 'cleaner_replace_urls'), 'http://', PARAM_URL));

$settings->add(new admin_setting_configtext('cleaner_replace_urls/newsiteurl',
            new lang_string('newsiteurl', 'cleaner_replace_urls'),
            new lang_string('newsiteurldesc', 'cleaner_replace_urls'), 'http://localhost', PARAM_URL));

$defaultskiptables = "config, config_plugins, config_log, upgrade_log, log, filter_config, sessions, events_queue, " .
        "repository_instance_config, block_instances";
$settings->add(new admin_setting_configtextarea(
    'cleaner_replace_urls/skiptables',
    new lang_string('skiptables', 'cleaner_replace_urls'),
    new lang_string('skiptablesdesc', 'cleaner_replace_urls'), $defaultskiptables, PARAM_TEXT, 60, 5));