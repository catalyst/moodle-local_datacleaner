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
class index_controller {
    /** @var index_controller */
    private $uploader;

    /** @var uploader */
    private $downloader;

    public function __construct() {
        $this->downloader = new index_controller();
        $this->uploader = new uploader();
    }

    public static function execute() {
        global $PAGE;

        $myurl = '/local/datacleaner/cleaner/muc/index.php';
        $index = new index_renderer();

        // TODO process submit should be in controller.
        if ((new uploader())->process_submit()) {
            // End script here (redirect) -- cannot be unit-tested.
            redirect($myurl);
        }

        $PAGE->set_url($myurl);
        echo $index->render_index_page();
    }

    public static function download() {
        global $CFG;

        if (!is_siteadmin()) {
            throw new moodle_exception('Only admins can download MUC configuration.');
        }

        require_sesskey();

        $mucfile = "{$CFG->dataroot}/muc/config.php";
        readfile($mucfile);
    }

    public static function get_download_filename() {
        global $CFG;
        return rawurlencode($CFG->wwwroot) . '.muc';
    }
}
