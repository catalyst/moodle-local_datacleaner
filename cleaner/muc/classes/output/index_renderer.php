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

use cleaner_muc\form\upload_form;
use cleaner_muc\muc_config;
use renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * @package     cleaner_muc
 * @subpackage  local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class index_renderer extends renderer_base {
    /**
     * Outputs (not returns) the HTML for the basic index page.
     *
     * @param upload_form  $uploadform
     * @param muc_config[] $configurations
     */
    public static function output(upload_form $uploadform, array $configurations) {
        global $OUTPUT, $PAGE;

        $renderer = $PAGE->get_renderer('cleaner_muc', 'index', RENDERER_TARGET_GENERAL);

        echo $OUTPUT->header() .
             $renderer->manage_muc_configurations($uploadform, $configurations) .
             $OUTPUT->footer();
    }

    public function manage_muc_configurations(upload_form $uploadform, array $configurations) {
        global $OUTPUT;

        $table = new configurations_table();

        return $OUTPUT->heading(get_string('heading_configurations', 'cleaner_muc')) .
               $table->get_html($configurations) .
               '<br /><br />' .
               $uploadform->render();
    }
}
