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

use cleaner_muc\envbar_adapter;
use moodle_exception;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Class downloader
 *
 * @package     cleaner_muc
 * @subpackage  local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class downloader {
    public static function output() {
        global $PAGE;
        $PAGE->set_url('/local/datacleaner/cleaner/muc/downloader.php');
        $downloader = new downloader();

        $download = ('muc' == optional_param('download', '', PARAM_ALPHA));
        if ($download) {
            $downloader->download();
        } else {
            echo $downloader->render_page();
        }

        return $downloader;
    }

    private function render_page() {
        global $PAGE;
        $renderer = $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL);

        return $renderer->header() .
               $renderer->heading(get_string('setting_downloader', 'cleaner_muc')) .
               $this->render_download_link() .
               $renderer->footer();
    }

    private function render_download_link() {
        if (envbar_adapter::is_production()) {
            return '<i>' . get_string('downloader_in_production', 'cleaner_muc') . '</i>';
        } else {
            $url = new moodle_url('/local/datacleaner/cleaner/muc/downloader.php', [
                'download' => 'muc',
                'sesskey'  => sesskey(),
            ]);
            $filename = self::get_filename();
            return '<a download="' . $filename . '" href="' . $url . '">' .
                   get_string('downloader_link', 'cleaner_muc') .
                   '</a>';
        }
    }

    private function download() {
        global $CFG;

        if (!is_siteadmin()) {
            throw new moodle_exception('Only admins can download MUC configuration.');
        }

        if (envbar_adapter::is_production()) {
            throw new moodle_exception('Cannot download MUC config in production environment.');
        }

        require_sesskey();

        $mucfile = "{$CFG->dataroot}/muc/config.php";
        readfile($mucfile);
    }

    public static function get_filename() {
        global $CFG;
        $url = new moodle_url($CFG->wwwroot);
        $host = $url->get_host();
        return "{$host}-muc-config.php";
    }
}
