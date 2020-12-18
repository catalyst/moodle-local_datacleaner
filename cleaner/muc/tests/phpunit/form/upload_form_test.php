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

use cleaner_muc\dml\muc_config_db;
use cleaner_muc\form\upload_form;

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
class local_cleanurls_cleaner_muc_upload_form_test extends local_datacleaner_cleaner_muc_testcase {
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        // Trigger classloaders.
        class_exists(upload_form::class);
    }

    private static function mock_submit($files) {
        global $USER;

        $itemid = file_get_unused_draft_itemid();
        $contextid = context_user::instance($USER->id)->id;
        $fs = get_file_storage();
        foreach ($files as $filename => $contents) {
            $fileinfo = (object)[
                'filearea'  => 'draft',
                'component' => 'user',
                'itemid'    => $itemid,
                'contextid' => $contextid,
                'filepath'  => '/',
                'filename'  => $filename,
            ];
            $fs->create_file_from_string($fileinfo, $contents);
        }

        $_POST = [
            'sesskey'                           => sesskey(),
            'mucfiles'                          => $itemid,
            '_qf__cleaner_muc_form_upload_form' => '1',
            '_qf__cleaner_muc\form\upload_form' => '1', // Moodle 2.6 identifier.
        ];
    }

    protected function setUp(): void {
        global $CFG;

        parent::setUp();
        $this->resetAfterTest(true);
        self::setAdminUser();

        if ($CFG->branch < 30) {
            $this->markTestSkipped('Cannot test due to a bug fixed in MDL-56250 for Moodle 3.0+, skip tests below that version.');
        }
    }

    public function test_it_exists() {
        self::assertInstanceOf(upload_form::class, new upload_form());
    }

    public function test_it_detects_form_not_submitted() {
        $upload = new upload_form();
        self::assertFalse($upload->process_submit());
    }

    public function test_it_requires_muc_files() {
        self::mock_submit(['site.notmuc' => '<?php // Not Muc']);
        $upload = new upload_form();

        self::assertNull($upload->get_data());

        self::assertSame(['mucfiles' => 'Not a .MUC file: site.notmuc'], $upload->get_errors());
    }

    public function test_it_requires_a_php_file() {
        self::mock_submit(['site.muc' => 'Not a PHP. <?php echo "now I am PHP";']);
        $upload = new upload_form();

        self::assertNull($upload->get_data());

        $errors = $upload->get_errors();
        self::assertSame(['mucfiles' => 'Not starting with "<?php ": site.muc'], $errors);
    }

    public function test_it_gets_data_with_files() {
        $expected = [
            'test1.muc' => '<?php // Mock1',
            'test2.muc' => '<?php // Mock2',
        ];
        self::mock_submit($expected);
        $upload = new upload_form();
        $data = $upload->get_data();
        self::assertSame($expected, $data->files);
    }

    public function test_it_mocks_submitted_file() {
        global $USER;
        $mock = [
            'test1.muc' => 'Mock1',
            'test2.muc' => 'Mock2',
        ];
        self::mock_submit($mock);

        self::assertSame(sesskey(), $_POST['sesskey'], 'Invalid sesskey.');
        self::assertSame('1', $_POST['_qf__cleaner_muc_form_upload_form'], 'Invalid submitted flag.');

        $fs = get_file_storage();
        $files = $fs->get_area_files(
            context_user::instance($USER->id)->id,
            'user',
            'draft',
            $_POST['mucfiles']
        );

        $actual = [];
        foreach ($files as $file) {
            $actual[$file->get_filename()] = $file->get_content();
        }

        $expected = array_merge(['.' => ''], $mock);
        self::assertSame($expected, $actual, 'Invalid files.');
    }

    public function test_it_saves_the_configuration() {
        $mock = [
            'http%3A%2F%2Fmoodle.test.muc'             => '<?php // Mock Moodle',
            'http%3A%2F%2Fmoodle.test%2Fsubmoodle.muc' => '<?php // Mock SubMoodle',
        ];
        self::mock_submit($mock);

        $upload = new upload_form();
        $saved = $upload->process_submit();
        self::assertTrue($saved);

        $expected = [
            'http://moodle.test'           => '<?php // Mock Moodle',
            'http://moodle.test/submoodle' => '<?php // Mock SubMoodle',
        ];

        $actual = muc_config_db::get_all();
        foreach ($actual as $wwwroot => $config) {
            $actual[$wwwroot] = $config->get_configuration();
        }

        self::assertSame($expected, $actual);
    }

    public function test_it_updates_the_configuration() {
        $wwwroot = 'https://moodle2.test';
        self::create_muc_config($wwwroot, '<?php // Old Config');

        $mock = [
            'http%3A%2F%2Fmoodle.test.muc'             => '<?php // Mock Moodle',
            'http%3A%2F%2Fmoodle.test%2Fsubmoodle.muc' => '<?php // Mock SubMoodle',
            rawurlencode($wwwroot) . '.muc'            => '<?php // New Config',
        ];
        self::mock_submit($mock);

        $upload = new upload_form();
        $saved = $upload->process_submit();
        self::assertTrue($saved);

        $expected = [
            'http://moodle.test'           => '<?php // Mock Moodle',
            'http://moodle.test/submoodle' => '<?php // Mock SubMoodle',
            $wwwroot                       => '<?php // New Config',
        ];

        $actual = muc_config_db::get_all();
        foreach ($actual as $wwwroot => $config) {
            $actual[$wwwroot] = $config->get_configuration();
        }

        self::assertSame($expected, $actual);
    }
}
