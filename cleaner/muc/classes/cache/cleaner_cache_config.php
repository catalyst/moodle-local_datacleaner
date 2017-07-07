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
 * @package     cleaner_muc
 * @subpackage  local_datacleaner
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_muc\cache;

use cache_config_writer;
use cache_exception;
use cache_factory;
use core_component;

defined('MOODLE_INTERNAL') || die();

/**
 * @package     cleaner_muc
 * @subpackage  local_datacleaner
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleaner_cache_config extends cache_config_writer {
    /**
     * Expose this method.
     *
     * @return string
     */
    public static function get_config_file_path() {
        return parent::get_config_file_path();
    }

    /**
     * Based on parent::config_save().
     */
    public static function config_save_from_string($configuration) {
        global $CFG;

        $cachefile = static::get_config_file_path();
        $directory = dirname($cachefile);
        if ($directory !== $CFG->dataroot && !file_exists($directory)) {
            $result = make_writable_directory($directory, false);
            if (!$result) {
                throw new cache_exception('ex_configcannotsave', 'cache', '', null, 'Cannot create config directory. Check the permissions on your moodledata directory.');
            }
        }
        if (!file_exists($directory) || !is_writable($directory)) {
            throw new cache_exception('ex_configcannotsave', 'cache', '', null, 'Config directory is not writable. Check the permissions on the moodledata/muc directory.');
        }

        // Prepare the file content.
        $content = $configuration;

        // We need to create a temporary cache lock instance for use here. Remember we are generating the config file
        // it doesn't exist and thus we can't use the normal API for this (it'll just try to use config).
        $lockconf = [
            'name'    => 'cachelock_file_default',
            'type'    => 'cachelock_file',
            'dir'     => 'filelocks',
            'default' => true,
        ];

        $factory = cache_factory::instance();
        $locking = $factory->create_lock_instance($lockconf);
        if ($locking->lock('configwrite', 'config', true)) {
            // Its safe to use w mode here because we have already acquired the lock.
            $handle = fopen($cachefile, 'w');
            fwrite($handle, $content);
            fflush($handle);
            fclose($handle);
            $locking->unlock('configwrite', 'config');
            @chmod($cachefile, $CFG->filepermissions);
            // Tell PHP to recompile the script.
            core_component::invalidate_opcode_php_cache($cachefile);
        } else {
            throw new cache_exception('ex_configcannotsave', 'cache', '', null, 'Unable to open the cache config file.');
        }
    }
}
