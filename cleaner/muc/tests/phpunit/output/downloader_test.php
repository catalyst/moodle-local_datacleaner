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
use local_envbar\local\envbarlib;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../envbar_adapter_test.php');

class  local_cleanurls_cleaner_muc_output_downloader_test extends advanced_testcase {
    const DOWNLOAD_LINK = '/local/datacleaner/cleaner/muc/downloader.php?download=muc';

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        // Trigger classloaders.
        class_exists(envbar_adapter::class);
        class_exists(downloader::class);
    }

    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
        local_cleanurls_cleaner_muc_envbar_adapter_test::create_envbar_data();
    }

    public function test_it_outputs_header_and_footer() {
        $html = $this->get_page();

        self::assertNotEmpty($html);
        self::assertContains('<html', $html);
        self::assertContains('</html', $html);
    }

    public function test_it_outputs_the_download_link() {
        $html = $this->get_page();

        self::assertContains('<h2>MUC Config Downloader</h2>', $html);
        self::assertContains(self::DOWNLOAD_LINK, $html);
    }

    public function test_it_outputs_a_warning_message_if_production() {
        local_cleanurls_cleaner_muc_envbar_adapter_test::mock_production_site();

        $html = $this->get_page();

        self::assertContains('<h2>MUC Config Downloader</h2>', $html);
        self::assertNotContains(self::DOWNLOAD_LINK, $html);
        self::assertContains('Sorry, downloading the MUC Config file is not allowed in production environment.', $html);
    }

    private function get_page() {
        ob_start();
        downloader::output();
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }
}
