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

use cleaner_muc\controller;
use flexible_table;
use html_writer;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tablelib.php');

/**
 * @package     cleaner_muc
 * @subpackage  local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class configurations_table extends flexible_table {
    public function __construct() {
        global $PAGE;

        parent::__construct('local_cleanurls_cleaner_muc_configurations_table');

        $this->define_baseurl($PAGE->url);
        $this->set_attribute('class', 'generaltable admintable');

        $this->define_columns(['wwwroot', 'actions']);

        $this->define_headers([
                                  get_string('table_header_wwwroot', 'cleaner_muc'),
                                  get_string('actions'),
                              ]);

        $this->setup();
    }

    public function get_html(array $wwwroots) {
        global $CFG;

        ob_start();

        $current = get_string('table_current_configuration', 'cleaner_muc', $CFG->wwwroot);
        $this->add_data([$current, $this->create_data_buttons(null)]);

        foreach ($wwwroots as $wwwroot) {
            $this->add_data([$wwwroot, $this->create_data_buttons($wwwroot)]);
        }
        $this->finish_output();

        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    protected function create_data_buttons($wwwroot) {
        $buttons = $this->create_buttons_viewdownload($wwwroot) .
                   $this->create_button_delete($wwwroot);

        return html_writer::tag('nobr', $buttons);
    }

    protected function create_buttons_viewdownload($wwwroot) {
        global $CFG, $OUTPUT;

        $params = [
            'sesskey'     => sesskey(),
            'action'      => is_null($wwwroot) ? 'current' : 'download',
            'environment' => is_null($wwwroot) ? '' : rawurlencode($wwwroot),
        ];
        $filename = controller::get_download_filename(is_null($wwwroot) ? $CFG->wwwroot : $wwwroot);

        $buttons = '';
        foreach (['view', 'download'] as $action) {
            $icon = ($action == 'view') ? 'preview' : $action;

            $buttons .= html_writer::link(
                new moodle_url($this->baseurl, $params),
                $OUTPUT->pix_icon("t/{$icon}", get_string($action)),
                ($action == 'download') ? ['download' => $filename] : ['target' => '_blank']
            );
        }

        return $buttons;
    }

    protected function create_button_delete($wwwroot) {
        global $OUTPUT;

        if (is_null($wwwroot)) {
            return '';
        }

        return html_writer::link(
            new moodle_url($this->baseurl, ['action' => 'delete', 'environment' => $wwwroot]),
            $OUTPUT->pix_icon('t/delete', get_string('delete'))
        );
    }
}
