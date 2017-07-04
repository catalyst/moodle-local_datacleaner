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

use cleaner_muc\envbar_adapter;
use cleaner_muc\output\downloader;
use cleaner_muc\output\index;

defined('MOODLE_INTERNAL') || die();

class  local_cleanurls_cleaner_muc_output_index_test extends advanced_testcase {
    public function test_it_outputs_header_and_footer() {
        $html = $this->get_page();

        self::assertNotEmpty($html);
        self::assertContains('<html', $html);
        self::assertContains('</html', $html);
    }

    public function test_it_outputs_the_download_section() {
        $html = $this->get_page();

        self::assertContains('<h2>MUC Config Downloader</h2>', $html);
    }

    private function get_page() {
        ob_start();
        try {
            index::output();
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        return $html;
    }
}
