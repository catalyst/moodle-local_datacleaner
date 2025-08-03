<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_datacleaner;


/**
 * The text setting where input is encoded before save.
 *
 * @package   local_datacleaner
 * @author    Dustin Huynh <dustinhuynh@catalyst-au.net>
 * @copyright 2025, Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_configencodedtext extends \admin_setting_configtext {

    /**
     * Return the setting
     *
     * @return mixed returns config if successful else null
     */
    public function get_setting() {
        global $CFG;
        return base64_decode($CFG->original_wwwroot) ?? null;
    }

    /**
     * Write the setting
     */
    public function write_setting($data) {
        global $CFG;
        if ($this->paramtype === PARAM_INT && $data === '') {
            $data = 0;
        }
        $validated = $this->validate($data);
        if ($validated !== true) {
            return $validated;
        }
        $originalwwwroot = base64_encode($data);
        return set_config('original_wwwroot', $originalwwwroot) ? '' : get_string('errorsetting', 'admin');
    }

    /**
     * Return an XHTML string for the setting
     * @return string Returns an XHTML string
     */
    public function output_html($data, $query = '') {
        global $CFG;
        $elementid = $this->get_id();
        $textbox = parent::output_html($data, $query);
        $resetbutton = \html_writer::tag('button',
            get_string('resetbutton', 'local_datacleaner'),
            [
                'type' => 'button',
                'onclick' => "document.getElementById('{$elementid}').value = '{$CFG->wwwroot}'",
                'class' => 'btn btn-secondary',
            ],
        );
        $html = \html_writer::div(
            $textbox . $resetbutton,
            'mb-3'
        );
        return $html;
    }
}
