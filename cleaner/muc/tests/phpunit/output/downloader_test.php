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

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../envbar_adapter_test.php');

class  local_cleanurls_cleaner_muc_output_downloader_test extends advanced_testcase {
    const DOWNLOAD_LINK = '/local/datacleaner/cleaner/muc/downloader.php?download=muc&amp;sesskey=';

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        // Trigger classloaders.
        class_exists(envbar_adapter::class);
        class_exists(downloader::class);
    }

    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
        self::setAdminUser();
        local_cleanurls_cleaner_muc_envbar_adapter_test::create_envbar_data();
    }

    /** @var downloader */
    private $downloader = null;

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

    public function test_it_downloads_the_config_file() {
        global $CFG;

        $_GET['download'] = 'muc';
        $_GET['sesskey'] = sesskey();
        $actual = $this->get_page();

        $mucfile = "{$CFG->dataroot}/muc/config.php";
        $expected = file_get_contents($mucfile);

        self::assertSame($expected, $actual);
    }

    public function test_it_provides_download_html5_tag() {
        $html = $this->get_page();
        $filename = 'muc-config.php';
        $expected = 'download="' . $filename . '"';
        self::assertContains($expected, $html);
    }

    public function test_it_requires_sesskey_to_download_file() {
        $_GET['download'] = 'muc';
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('sesskey');
        $this->get_page();
    }

    public function test_it_does_not_allow_download_in_production_environment() {
        local_cleanurls_cleaner_muc_envbar_adapter_test::mock_production_site();
        $_GET['download'] = 'muc';
        $_GET['sesskey'] = sesskey();
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('production environment');
        $this->get_page();
    }

    public function test_it_does_not_allow_download_if_not_admin() {
        // It should already be blocked by downloader page, but add one more layer of check.

        self::setUser($this->getDataGenerator()->create_user());

        $_GET['download'] = 'muc';
        $_GET['sesskey'] = sesskey();
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Only admins can download MUC configuration');
        $this->get_page();
    }

    private function get_page() {
        ob_start();
        try {
            $this->downloader = downloader::output();
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        return $html;
    }
}
