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

namespace local_datacleaner;

/**
 * A textarea admin setting that auto-sizes to fit its content.
 *
 * Rows are computed from the stored value on page load, and a small
 * inline script keeps the height in sync as the user types.
 *
 * @package    local_datacleaner
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  2026 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_sql_textarea extends \admin_setting_configtextarea {
    /**
     * Returns an XHTML string for the editor, sized to fit the current content.
     *
     * @param string $data
     * @param string $query
     * @return string
     */
    public function output_html($data, $query = '') {
        $html = parent::output_html($data, $query);

        $id = $this->get_id();

        // Resize on input and trigger once on load to handle the initial value.
        $script = \html_writer::script("
(function() {
    var el = document.getElementById('{$id}');
    if (!el) { return; }
    function resize() { el.style.height = 'auto'; el.style.height = el.scrollHeight + 'px'; }
    el.style.overflow = 'hidden';
    el.style.fontFamily = 'monospace';
    el.style.fontSize = '0.85em';
    el.addEventListener('input', resize);
    resize();
})();
");

        return $html . $script;
    }
}
