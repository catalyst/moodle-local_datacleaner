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

use cleaner_muc\output\downloader;

defined('MOODLE_INTERNAL') || die();

class  local_cleanurls_cleaner_muc_output_downloader_test extends advanced_testcase {
    const DOWNLOAD_LINK = '/local/datacleaner/cleaner/muc/downloader.php?sesskey=';

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        // Trigger classloaders.
        class_exists(downloader::class);
    }

    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
        self::setAdminUser();
    }

    public function test_it_does_not_output_header_and_footer() {
        $html = $this->get_download_section();

        self::assertNotContains('<html', $html);
        self::assertNotContains('</html', $html);
    }

    public function test_it_outputs_the_download_link() {
        $html = $this->get_download_section();

        self::assertContains('<h2>MUC Config Downloader</h2>', $html);
        self::assertContains(self::DOWNLOAD_LINK, $html);
    }

    public function test_it_downloads_the_config_file() {
        global $CFG;

        $mucfile = "{$CFG->dataroot}/muc/config.php";
        $create = !file_exists($mucfile);
        if ($create) {
            $dirname = dirname($mucfile);
            if (!is_dir($dirname)) {
                mkdir($dirname);
            }
            file_put_contents($mucfile, '<?php // Test MUC File');
        }

        $expected = file_get_contents($mucfile);

        $_GET['sesskey'] = sesskey();
        $actual = $this->get_download_file();

        if ($create) {
            unlink($mucfile);
        }

        self::assertSame($expected, $actual);
    }

    public function test_it_provides_download_html5_tag() {
        global $CFG;
        $CFG->httpswwwroot = $CFG->wwwroot = 'https://moodle.test/subdir';

        $html = $this->get_download_section();
        $filename = rawurlencode($CFG->wwwroot);
        $expected = 'download="' . $filename . '"';
        self::assertContains($expected, $html);
    }

    public function test_it_requires_sesskey_to_download_file() {
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('sesskey');
        $this->get_download_file();
    }

    public function test_it_does_not_allow_download_if_not_admin() {
        // It should already be blocked by downloader page, but add one more layer of check.

        self::setUser($this->getDataGenerator()->create_user());

        $_GET['sesskey'] = sesskey();
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Only admins can download MUC configuration');
        $this->get_download_file();
    }

    public function test_it_generates_the_correct_filename() {
        global $CFG;

        $CFG->wwwroot = 'http://thesite.url.to-use';
        $expected = rawurlencode($CFG->wwwroot);
        $actual = downloader::get_filename();
        self::assertSame($expected, $actual);
    }

    private function get_download_section() {
        $downloader = new downloader();
        return $downloader->render_download_section();
    }

    private function get_download_file() {
        ob_start();
        try {
            downloader::download();
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        return $html;
    }
}
