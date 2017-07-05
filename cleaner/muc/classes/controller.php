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
class controller {
    const MY_URL = '/local/datacleaner/cleaner/muc/index.php';

    public static function get_muc_file_location() {
        global $CFG;
        return "{$CFG->dataroot}/muc/config.php";
    }

    /** @var upload_form */
    private $uploadform;

    public static function get_download_filename($wwwroot) {
        return rawurlencode($wwwroot) . '.muc';
    }

    private static function get_action_environment() {
        $environment = required_param('environment', PARAM_RAW);
        $environment = rawurldecode($environment);
        return $environment;
    }

    public function __construct() {
        admin_externalpage_setup('cleaner_muc');
        $this->uploadform = new upload_form();
    }

    public function index() {
        global $PAGE;

        $action = optional_param('action', '', PARAM_ALPHA);
        if ($action) {
            if ($this->perform_action($action)) {
                return;
            } else {
                throw new moodle_exception('Invalid action: ' . $action);
            }
        }

        if ($this->uploadform->process_submit()) {
            redirect(self::MY_URL);
        }

        $PAGE->set_url(self::MY_URL);

        $configurations = muc_config_db::get_all();
        index_renderer::output($this->uploadform, $configurations);
    }

    private function perform_action($action) {
        require_sesskey();

        switch ($action) {
            case 'current':
                return $this->action_current();
            case 'download':
                return $this->action_download(self::get_action_environment());
            case 'delete':
                return $this->action_delete(self::get_action_environment());
            default:
                return false;
        }
    }

    private function action_current() {
        global $CFG;

        if (!headers_sent()) {
            header('Content-Type: text/plain');
        }

        readfile(self::get_muc_file_location());

        return true;
    }

    private function action_download($environment) {
        $config = muc_config_db::get_by_wwwroot($environment);

        if (is_null($config)) {
            return false;
        }

        if (!headers_sent()) {
            header('Content-Type: text/plain');
        }

        echo $config->get_configuration();

        return true;
    }

    private function action_delete($environment) {
        muc_config_db::delete($environment);

        redirect(self::MY_URL);

        return true;
    }
}
