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
use cleaner_muc\event\muc_config_deleted;
use cleaner_muc\event\muc_config_event;
use cleaner_muc\event\muc_config_saved;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/cleaner_muc_testcase.php');

class local_cleanurls_cleaner_muc_events_test extends local_datacleaner_cleaner_muc_testcase {
    protected function setUp() {
        parent::setUp();
        self::setAdminUser();
        $this->resetAfterTest(false);
    }

    public function provider_for_test_events_have_the_correct_properties() {
        return [
            [muc_config_saved::class, 'c'],
            [muc_config_deleted::class, 'd'],
        ];
    }

    /** @dataProvider provider_for_test_events_have_the_correct_properties */
    public function test_events_have_the_correct_properties($class, $crud) {
        $event = $class::create([
                                    'objectid' => 1,
                                    'other'    => ['wwwroot' => 'http://moodle.test/subpath'],
                                ]);
        $data = $event->get_data();

        $expected = [
            'contextid'   => context_system::instance()->id,
            'crud'        => $crud,
            'edulevel'    => muc_config_event::LEVEL_OTHER,
            'objecttable' => 'cleaner_muc_configs',
            'other'       => ['wwwroot' => 'http://moodle.test/subpath'],
        ];
        $actual = [
            'contextid'   => $data['contextid'],
            'crud'        => $data['crud'],
            'edulevel'    => $data[muc_config_event::get_data_level_key_name()],
            'objecttable' => $data['objecttable'],
            'other'       => $data['other'],
        ];

        self::assertSame($expected, $actual);
    }

    public function provider_for_test_events_have_the_correct_description() {
        return [
            [
                muc_config_saved::class,
                "The user #2 uploaded a muc configuration for: http://moodle.test/subpath",
            ],
            [
                muc_config_deleted::class,
                "The user #2 deleted the muc configuration for: http://moodle.test/subpath",
            ],
        ];
    }

    /** @dataProvider provider_for_test_events_have_the_correct_description */
    public function test_events_have_the_correct_description($class, $description) {
        $event = $class::create([
                                    'objectid' => 1,
                                    'other'    => ['wwwroot' => 'http://moodle.test/subpath'],
                                ]);

        self::assertSame($description, $event->get_description());
    }

    public function test_muc_config_saved_is_triggered() {
        $sink = $this->redirectEvents();
        self::create_muc_config('https://moodle.site', '<?php // PHP File');
        $events = $sink->get_events();
        $sink->close();

        self::assertCount(1, $events);
        self::assertInstanceOf(muc_config_saved::class, $events[0]);
    }

    public function test_muc_config_deleted_is_triggered() {
        self::create_muc_config('https://moodle.site', '<?php // PHP File');

        $sink = $this->redirectEvents();
        muc_config_db::delete('https://moodle.site');
        $events = $sink->get_events();
        $sink->close();

        self::assertCount(1, $events);
        self::assertInstanceOf(muc_config_deleted::class, $events[0]);
    }

    public function test_muc_config_deleted_and_saved_are_triggered() {
        self::create_muc_config('https://moodle.site', '<?php // PHP File');

        $sink = $this->redirectEvents();
        self::create_muc_config('https://moodle.site', '<?php // PHP File 2');
        $events = $sink->get_events();
        $sink->close();

        self::assertCount(2, $events);
        self::assertInstanceOf(muc_config_deleted::class, $events[0]);
        self::assertInstanceOf(muc_config_saved::class, $events[1]);
    }
}
