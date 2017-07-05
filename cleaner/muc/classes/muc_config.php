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
 * @subpackage  local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_muc;

use cleaner_muc\dml\muc_config_db;
use cleaner_muc\form\upload_form;
use cleaner_muc\output\index_renderer;
use moodle_exception;
use ReflectionClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/adminlib.php');

/**
 * @package     cleaner_muc
 * @subpackage  local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class muc_config {
    /** @var int */
    protected $id;

    public function get_id() {
        return $this->id;
    }

    public function set_id($id) {
        $this->id = is_null($id) ? null : (int)$id;
    }

    /** @var string */
    protected $wwwroot;

    public function get_wwwroot() {
        return $this->wwwroot;
    }

    public function set_wwwroot($wwwroot) {
        $this->wwwroot = (string)$wwwroot;
    }

    /** @var string */
    protected $configuration;

    public function get_configuration() {
        return $this->configuration;
    }

    public function set_configuration($configuration) {
        $this->configuration = is_null($configuration) ? '' : (string)$configuration;
    }

    /** @var int */
    protected $lastmodified;

    public function get_lastmodified() {
        return $this->lastmodified;
    }

    public function set_lastmodified($id) {
        $this->lastmodified = is_null($id) ? null : (int)$id;
    }

    /**
     * emoticons_feedback constructor.
     *
     * @param muc_config|array $data
     */
    public function __construct($data = []) {
        if (is_a($data, self::class)) {
            $data = $data->to_array();
        } else {
            $data = (array)$data;
        }

        $reflection = new ReflectionClass(self::class);

        foreach ($reflection->getProperties() as $property) {
            $name = $property->getName();

            if (!array_key_exists($name, $data)) {
                $data[$name] = null;
            }

            $setter = "set_{$name}";
            $this->$setter($data[$name]);
            unset($data[$name]);
        }

        if (!empty($data)) {
            debugging('Invalid fields: ' . array_keys($data));
        }
    }

    public function to_array() {
        $data = [];
        $reflection = new ReflectionClass(self::class);
        foreach ($reflection->getProperties() as $property) {
            $name = $property->getName();
            $getter = "get_{$name}";
            $data[$name] = $this->$getter();
        }
        return $data;
    }
}
