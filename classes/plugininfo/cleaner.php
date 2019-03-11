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
 * @package    local_datacleaner
 * @copyright  2015 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_datacleaner\plugininfo;

use core\plugininfo\base;

defined('MOODLE_INTERNAL') || die();

class cleaner extends base {

    /**
     * Whether the subplugin is enabled.
     *
     * @return bool Whether enabled.
     */
    public function enabled() {
        return get_config('cleaner_' . $this->name, 'enabled');
    }

    /**
     * Get enabled plugins, sorted by sortorder
     *
     * @return array Enabled plugins, sorted by sortorder
     */
    static public function get_plugins_by_sortorder() {

        $fileinfo = \core_plugin_manager::instance()->get_present_plugins('cleaner');
        $plugins = \core_plugin_manager::instance()->get_plugins_of_type('cleaner');

        foreach ($plugins as $name => $plugin) {
            if (isset($fileinfo[$name])) {
                $plugin->sortorder = $fileinfo[$name]->sortorder;
            }
        }
        usort($plugins, function($a, $b) {
            return $a->sortorder - $b->sortorder;
        });

        return $plugins;
    }

    /**
     * Get enabled plugins, sorted by sort order
     *
     * @return array Enabled plugins, sorted by sort order
     */
    static public function get_enabled_plugins_by_sortorder() {

        $plugins = self::get_plugins_by_sortorder();

        // Filter only enabled ones.
        $plugins = array_filter($plugins, function($plugin) {
            return $plugin->enabled();
        });

        return $plugins;
    }

    /**
     * Yes you can uninstall these plugins if you want.
     * @return \moodle_url
     */
    public function is_uninstall_allowed() {
        return true;
    }

    /**
     * Return URL used for management of plugins of this type.
     * @return \moodle_url
     */
    public static function get_manage_url() {
        return new \moodle_url('/local/datacleaner/index.php');
    }

    /**
     * Include the settings.php file from sub plugins if they provide it.
     * This is a copy of very similar implementations from various other subplugin areas.
     *
     * @return \moodle_url
     */
    public function load_settings(\part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig) {
        global $CFG, $USER, $DB, $OUTPUT, $PAGE; // In case settings.php wants to refer to them.
        $ADMIN = $adminroot; // May be used in settings.php.
        $plugininfo = $this; // Also can be used inside settings.php.

        if (!$this->is_installed_and_upgraded()) {
            return;
        }

        if (!$hassiteconfig or !file_exists($this->full_path('settings.php'))) {
            return;
        }

        $section = $this->get_settings_section_name();
        $settings = new \admin_settingpage($section, $this->displayname, 'moodle/site:config', $this->is_enabled() === false);

        include($this->full_path('settings.php')); // This may also set $settings to null.

        if ($settings) {
            $settings->add(new \admin_setting_configcheckbox('cleaner_' . $this->name . '/enabled',
                new \lang_string('enabledisable', 'local_datacleaner'), null, 0));

            $ADMIN->add($parentnodename, $settings);
        }
    }

    /**
     * Get the settings section name.
     * It's used to get the setting links in the cleaner sub-plugins table.
     *
     * @return null|string the settings section name.
     */
    public function get_settings_section_name() {
        if (file_exists($this->full_path('settings.php'))) {
            return 'cleaner_' . $this->name;
        } else {
            return null;
        }
    }

    /**
     * Get the settings section url.
     *
     * @return null|\moodle_url the settings section name.
     */
    public function get_settings_section_url() {
        $sectionname = $this->get_settings_section_name();
        $classname = "\\" . $sectionname . "\\clean";

        if ($sectionname) {
            return $classname::get_settings_section_url($sectionname);
        }
        return null;
    }

}

