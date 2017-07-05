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

use cleaner_muc\form\upload_form;
use cleaner_muc\output\index_renderer;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * @package     cleaner_muc
 * @subpackage  local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controller {
    const MY_URL = '/local/datacleaner/cleaner/muc/index.php';

    /** @var upload_form */
    private $uploadform;

    public static function get_download_filename() {
        global $CFG;
        return rawurlencode($CFG->wwwroot) . '.muc';
    }

    public function __construct() {
        $this->uploadform = new upload_form();
    }

    public function index() {
        global $PAGE;

        $renderer = new index_renderer();

        if ($this->uploadform->process_submit()) {
            redirect(self::MY_URL);
        }

        $PAGE->set_url(self::MY_URL);
        echo $renderer->render_index_page($this->uploadform);
    }

    public function download() {
        global $CFG;

        if (!is_siteadmin()) {
            throw new moodle_exception('Only admins can download MUC configuration.');
        }

        require_sesskey();

        $mucfile = "{$CFG->dataroot}/muc/config.php";
        readfile($mucfile);
    }
}
