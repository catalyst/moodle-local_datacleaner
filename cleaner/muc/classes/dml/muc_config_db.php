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

namespace cleaner_muc\dml;

use cleaner_muc\event\muc_config_deleted;
use cleaner_muc\event\muc_config_saved;
use cleaner_muc\muc_config;

defined('MOODLE_INTERNAL') || die();

/**
 * Class muc_config_db
 *
 * @package     cleaner_muc
 * @subpackage  local_datacleaner
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class muc_config_db {
    const TABLE_NAME = 'cleaner_muc_configs';

    public static function save(muc_config $config) {
        global $DB;

        static::delete($config->get_wwwroot());

        // The wwwroot is base64 encoded to prevent being washed during cleanup.
        $wwwroot64 = base64_encode($config->get_wwwroot());

        $config->set_lastmodified(time());

        $data = (object)[
            'wwwroot'       => $wwwroot64,
            'configuration' => $config->get_configuration(),
            'lastmodified'  => $config->get_lastmodified(),
        ];

        $id = $DB->insert_record(self::TABLE_NAME, $data);
        $config->set_id($id);

        muc_config_saved::fire($id, $config->get_wwwroot());
    }

    /**
     * @param $wwwroot string
     * @return muc_config
     */
    public static function get_by_wwwroot($wwwroot) {
        global $DB;

        $wwwroot64 = base64_encode($wwwroot);
        $data = $DB->get_record(self::TABLE_NAME, ['wwwroot' => $wwwroot64]);

        return self::create_from_db($data);
    }

    /**
     * @return muc_config[]
     */
    public static function get_all() {
        global $DB;

        $rows = $DB->get_records(self::TABLE_NAME);

        $all = [];
        foreach ($rows as $row) {
            $config = self::create_from_db($row);
            $all[$config->get_wwwroot()] = $config;
        }

        ksort($all);

        return $all;
    }

    public static function get_environments() {
        global $DB;

        $rows = $DB->get_records(self::TABLE_NAME, null, '', 'id,wwwroot');

        $envs = [];
        foreach ($rows as $row) {
            $envs[] = base64_decode($row->wwwroot);
        }

        sort($envs);

        return $envs;
    }

    public static function delete($wwwroot) {
        global $DB;

        $wwwroot64 = base64_encode($wwwroot);

        $id = $DB->get_field(self::TABLE_NAME, 'id', ['wwwroot' => $wwwroot64]);

        if ($id === false) {
            return;
        }

        $DB->delete_records(self::TABLE_NAME, ['id' => $id]);

        muc_config_deleted::fire($id, $wwwroot);
    }

    private static function create_from_db($data) {
        if ($data === false) {
            return null;
        }

        $data = (array)$data;
        $data['wwwroot'] = base64_decode($data['wwwroot']);
        return new muc_config($data);
    }
}
