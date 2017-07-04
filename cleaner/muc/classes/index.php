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
class index {
    public static function output() {
        global $PAGE;
        $PAGE->set_url('/local/datacleaner/cleaner/muc/index.php');
        $index = new index();

        echo $index->render_index_page();
    }

    public function render_index_page() {
        global $PAGE;
        $renderer = $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL);

        $downloader = new downloader();
        $uploader = new uploader();

        return $renderer->header() .
               $uploader->render_upload_section() .
               '<br /><br />' .
               $downloader->render_download_section() .
               $renderer->footer();
    }
}
