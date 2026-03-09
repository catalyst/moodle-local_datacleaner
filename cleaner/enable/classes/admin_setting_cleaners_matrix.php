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

namespace cleaner_enable;

/**
 * Admin setting that renders a matrix of all cleaners with enable/disable checkboxes.
 *
 * The enabled state for each cleaner is stored under the cleaner_enable plugin as
 * "enabled_<name>" (e.g. cleaner_enable/enabled_config). When this cleaner's execute()
 * runs it copies those values into each cleaner's own "enabled" config key.
 *
 * @package    cleaner_enable
 * @copyright  2026 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_cleaners_matrix extends \admin_setting {

    public function __construct() {
        parent::__construct(
            'cleaner_enable/matrix',
            new \lang_string('matrixheading', 'cleaner_enable'),
            new \lang_string('matrixdesc', 'cleaner_enable'),
            null
        );
    }

    /**
     * Returns an array of [cleaner_name => enabled (0|1)] for every cleaner except self.
     * Defaults to 1 (enabled) when no override has been configured yet.
     *
     * @return array
     */
    public function get_setting() {
        $cleaners = \local_datacleaner\plugininfo\cleaner::get_plugins_by_sortorder();
        $result = [];
        foreach ($cleaners as $cleaner) {
            if ($cleaner->name === 'enable') {
                continue;
            }
            $stored = get_config('cleaner_enable', 'enabled_' . $cleaner->name);
            // Default to enabled when not yet configured.
            $result[$cleaner->name] = ($stored === false) ? 1 : (int)(bool)$stored;
        }
        return $result;
    }

    public function get_defaultsetting() {
        return null;
    }

    /**
     * Save the enabled state for every cleaner.
     *
     * $data is the submitted POST array for this field. Unchecked boxes are absent
     * from the array; a hidden sentinel field (_submitted=1) ensures write_setting
     * is always called on form submit so unchecked cleaners are correctly disabled.
     *
     * @param  mixed $data Array from POST, or null.
     * @return string Empty string on success.
     */
    public function write_setting($data) {
        if (!is_array($data)) {
            return '';
        }
        $cleaners = \local_datacleaner\plugininfo\cleaner::get_plugins_by_sortorder();
        foreach ($cleaners as $cleaner) {
            if ($cleaner->name === 'enable') {
                continue;
            }
            $value = isset($data[$cleaner->name]) ? 1 : 0;
            set_config('enabled_' . $cleaner->name, $value, 'cleaner_enable');
        }
        return '';
    }

    /**
     * Render the matrix as an HTML table with one row per cleaner.
     *
     * @param  mixed  $data  Current setting value (array from get_setting()).
     * @param  string $query Search query string for highlighting.
     * @return string HTML output.
     */
    public function output_html($data, $query = '') {
        $cleaners = \local_datacleaner\plugininfo\cleaner::get_plugins_by_sortorder();

        $table = new \html_table();
        $table->head = [
            get_string('cleanername', 'cleaner_enable'),
            get_string('sortorder', 'local_datacleaner'),
            get_string('stage', 'local_datacleaner'),
            get_string('enabledisable', 'local_datacleaner'),
        ];
        $table->data = [];

        $fullname = $this->get_full_name();

        foreach ($cleaners as $cleaner) {
            if ($cleaner->name === 'enable') {
                continue;
            }

            $phase = ($cleaner->sortorder < 200)
                ? get_string('stageprewash', 'local_datacleaner')
                : get_string('stagepostwash', 'local_datacleaner');

            $attrs = [
                'type'  => 'checkbox',
                'name'  => $fullname . '[' . $cleaner->name . ']',
                'id'    => $fullname . '_' . $cleaner->name,
                'value' => 1,
            ];
            if (!empty($data[$cleaner->name])) {
                $attrs['checked'] = 'checked';
            }
            $checkbox = \html_writer::label(
                \html_writer::empty_tag('input', $attrs),
                '',
                false,
                ['class' => 'mr-1']
            );

            $table->data[] = [
                \html_writer::label(
                    get_string('pluginname', 'cleaner_' . $cleaner->name),
                    $fullname . '_' . $cleaner->name
                ),
                $cleaner->sortorder,
                $phase,
                $checkbox,
            ];
        }

        // Hidden sentinel so write_setting is called even when all boxes are unchecked.
        $sentinel = \html_writer::empty_tag('input', [
            'type'  => 'hidden',
            'name'  => $fullname . '[_submitted]',
            'value' => 1,
        ]);

        $element = \html_writer::table($table) . $sentinel;

        return format_admin_setting($this, $this->visiblename, $element, $this->description, true, '', null, $query);
    }
}
