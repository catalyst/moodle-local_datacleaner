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

namespace cleaner_muc\output;

use cleaner_muc\index_controller;
use cleaner_muc\uploader;
use core_renderer;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * @package     cleaner_muc
 * @subpackage  local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class index_renderer {
    public static function output() {
        global $PAGE;

        $myurl = '/local/datacleaner/cleaner/muc/index.php';
        $index = new index_renderer();

        if ($index->uploader->process_submit()) {
            // End script here (redirect) -- cannot be unit-tested.
            redirect($myurl);
        }

        $PAGE->set_url($myurl);
        echo $index->render_index_page();
    }

    /** @var index_controller */
    private $uploader;

    /** @var uploader */
    private $downloader;

    /** @var core_renderer */
    private $renderer;

    public function __construct() {
        global $PAGE;
        $this->renderer = $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL);

        $this->downloader = new index_controller();
        $this->uploader = new uploader();
    }

    public function render_index_page() {
        return $this->renderer->header() .
               $this->render_upload_section() .
               '<br /><br />' .
               $this->render_download_section() .
               $this->renderer->footer();
    }

    private function render_upload_section() {
        global $PAGE;
        $renderer = $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL);

        return $renderer->heading(get_string('setting_uploader', 'cleaner_muc')) .
               $this->uploader->render();
    }

    private function render_download_section() {
        global $PAGE;
        $renderer = $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL);

        return $renderer->heading(get_string('setting_downloader', 'cleaner_muc')) .
               $this->render_download_link();
    }

    private function render_download_link() {
        $url = new moodle_url('/local/datacleaner/cleaner/muc/download.php', ['sesskey' => sesskey()]);
        $filename = index_controller::get_download_filename();
        return '<a download="' . $filename . '" href="' . $url . '">' .
               get_string('downloader_link', 'cleaner_muc') .
               '</a>';
    }
}
