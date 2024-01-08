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

use cleaner_muc\controller;
use cleaner_muc\dml\muc_config_db;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../cleaner_muc_testcase.php');

/**
 * Tests.
 *
 * @package     cleaner_muc
 * @subpackage  local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @SuppressWarnings(public) Allow as many methods as needed.
 */
class local_cleanurls_cleaner_muc_index_page_test extends local_datacleaner_cleaner_muc_testcase {
    protected function setUp() : void {
        global $PAGE, $OUTPUT;

        parent::setUp();

        $this->resetAfterTest(true);
        self::setAdminUser();

        $OUTPUT = $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL);

        global $USER;
        $USER->email = 'moodle26and27@require.this';
    }

    /**
     * Test controller::index() returns a complete HTML document by default.
     *
     * @covers \cleaner_muc\controller::index()
     * @return void
     */
    public function test_it_outputs_header_and_footer() {
        $html = $this->get_page();

        self::assertNotEmpty($html);
        self::assertStringContainsString('<html', $html);
        self::assertStringContainsString('</html', $html);
    }

    public function test_it_outputs_the_configuratoin_list_section() {
        global $CFG;
        $html = $this->get_page();

        self::assertStringContainsString('<h2>MUC Configurations</h2>', $html);
        self::assertStringContainsString('id="local_cleanurls_cleaner_muc_configurations_table', $html);
        self::assertStringContainsString('Environment', $html);
        self::assertStringContainsString('Actions', $html);
        self::assertStringContainsString("<i>{$CFG->wwwroot}</i> (current configuration)", $html);
    }

    public function test_it_outputs_the_configuratoin_list_section_with_a_muc_config_entry() {
        self::create_muc_config('http://sometest.somewhere/everywhere', 'Cool Dude!');
        $html = $this->get_page();

        self::assertStringContainsString('http://sometest.somewhere/everywhere', $html);
    }

    public function test_it_outputs_the_upload_section() {
        $html = $this->get_page();

        self::assertStringContainsString('<form', $html);
        self::assertStringContainsString('type="submit"', $html);
    }

    public function test_it_provides_download_html5_tag() {
        $html = $this->get_page();
        $expected = 'download="';
        self::assertStringContainsString($expected, $html);
    }

    public function test_it_downloads_the_current_config_file() {
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

        $_GET['action'] = 'current';
        $_GET['sesskey'] = sesskey();
        $actual = self::get_page();

        $expected = file_get_contents($mucfile);

        self::assertSame($expected, trim($actual));
        $this->resetDebugging(); // This may show some debugging messages because cache definitions changed.
    }

    /**
     * Test that download view is rendered by controller::index() when action is download.
     *
     * @covers \cleaner_muc\controller::index()
     * @return void
     */
    public function test_it_downloads_environment_config_file() {
        self::create_muc_config('http://moodle.test/somewhere', 'My Config');

        $_GET['action'] = 'download';
        $_GET['environment'] = rawurlencode('http://moodle.test/somewhere');
        $_GET['sesskey'] = sesskey();
        $actual = self::get_page();

        self::assertSame('My Config', trim($actual));
    }

    private function get_page() {
        global $CFG;

        ob_start();
        try {
            (new controller())->index();
            $html = ob_get_contents();
            $html .= $CFG->closingtags ?? ''; // Fix for MDL-79276, if null then set fallback to empty string.
        } finally {
            ob_end_clean();
        }
        return $html;
    }
}
