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
use core_renderer;

defined('MOODLE_INTERNAL') || die();

/**
 * @package     cleaner_muc
 * @subpackage  local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class index_renderer {
    /** @var core_renderer */
    private $renderer;

    public function __construct() {
        global $PAGE;
        $this->renderer = $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL);
    }

    public function get_index(upload_form $uploadform, array $configurations) {
        $table = new configurations_table();

        return $this->renderer->header() .
               $this->renderer->heading(get_string('heading_configurations', 'cleaner_muc')) .
               $table->get_html($configurations) .
               '<br /><br />' .
               $uploadform->render() .
               $this->renderer->footer();
    }
}
