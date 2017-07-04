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
    public static function download() {
        global $CFG;

        if (!is_siteadmin()) {
            throw new moodle_exception('Only admins can download MUC configuration.');
        }

        require_sesskey();

        $mucfile = "{$CFG->dataroot}/muc/config.php";
        readfile($mucfile);
    }

    public function render_download_section() {
        global $PAGE;
        $renderer = $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL);

        return $renderer->heading(get_string('setting_downloader', 'cleaner_muc')) .
               $this->render_download_link();
    }

    private function render_download_link() {
        $url = new moodle_url('/local/datacleaner/cleaner/muc/download.php', ['sesskey' => sesskey()]);
        $filename = self::get_filename();
        return '<a download="' . $filename . '" href="' . $url . '">' .
               get_string('downloader_link', 'cleaner_muc') .
               '</a>';
    }

    public static function get_filename() {
        global $CFG;
        return rawurlencode($CFG->wwwroot) . '.muc';
    }
}