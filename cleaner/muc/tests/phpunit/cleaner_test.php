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

use cleaner_muc\cache\cleaner_cache_config;
use cleaner_muc\clean;
use cleaner_muc\cleaner;
use cleaner_muc\dml\muc_config_db;
use cleaner_muc\muc_config;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/cleaner_muc_testcase.php');

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
class local_cleanurls_cleaner_muc_cleaner_test extends local_datacleaner_cleaner_muc_testcase {
    /** @var string */
    protected $original = null;

    protected function setUp(): void {
        global $CFG;

        parent::setUp();
        $this->resetAfterTest(true);
        $CFG->wwwroot = $CFG->httpswwwroot = self::URL;

        purge_all_caches(); // Force creating MUC file.
        $this->original = file_get_contents(cleaner_cache_config::get_config_file_path());
    }

    public function test_it_has_a_task() {
        self::assertSame(clean::TASK, 'MUC Config File Replacement');
    }

    public function test_it_shows_verbose_mode() {
        $output = $this->execute(true, true);
        self::assertContains('Verbose', $output);
    }

    public function test_it_replaces_the_muc_file() {
        $configuration = self::create_muc_config()->get_configuration();
        $output = $this->execute(false, false);
        $found = file_get_contents(cleaner_cache_config::get_config_file_path());

        self::assertContains('MUC Configuration Loaded!', $output);
        self::assertSame($configuration, $found);
    }

    public function test_it_does_not_replace_in_dry_run() {
        self::create_muc_config();
        $output = $this->execute(true, false);
        $found = file_get_contents(cleaner_cache_config::get_config_file_path());

        self::assertContains('DRY RUN - Would load MUC Configuration.', $output);
        self::assertSame($this->original, $found);
    }

    public function test_it_shows_a_message_if_config_not_found() {
        $output = $this->execute(true, false);
        self::assertContains('Configuration not found', $output);
    }

    public function test_it_shows_verbose_message_with_available_configurations_if_current_not_found() {
        muc_config_db::save(new muc_config(['wwwroot' => 'http://site1.moodle']));
        muc_config_db::save(new muc_config(['wwwroot' => 'http://site2.moodle']));
        $output = $this->execute(true, true);
        self::assertContains('Configurations found (2):', $output);
        self::assertContains('http://site1.moodle', $output);
        self::assertContains('http://site2.moodle', $output);
    }

    public function test_it_purges_caches_after_loading_new_configuration() {
        if (!class_exists('cache_config_testing')) {
            $this->markTestSkipped('No cache_config_testing for this Moodle version.');
            return;
        }

        $instance = cache_config_testing::instance();
        $instance->phpunit_add_definition('phpunit/simpletest', [
            'mode'       => cache_store::MODE_APPLICATION,
            'component'  => 'phpunit',
            'area'       => 'simpletest',
            'simplekeys' => true,
        ]);
        $cache = cache::make('phpunit', 'simpletest');
        $cache->set('foo', 'bar');

        self::create_muc_config();
        $output = $this->execute(false, true);

        $cache = cache::make('phpunit', 'simpletest');
        self::assertFalse($cache->get('foo'));
        self::assertContains('Caches purged', $output);
    }

    public function test_it_would_purges_caches_in_dry_run() {
        if (!class_exists('cache_config_testing')) {
            $this->markTestSkipped('No cache_config_testing for this Moodle version.');
            return;
        }

        $instance = cache_config_testing::instance();
        $instance->phpunit_add_definition('phpunit/simpletest', [
            'mode'       => cache_store::MODE_APPLICATION,
            'component'  => 'phpunit',
            'area'       => 'simpletest',
            'simplekeys' => true,
        ]);
        $cache = cache::make('phpunit', 'simpletest');
        $cache->set('foo', 'bar');

        self::create_muc_config();
        $output = $this->execute(true, true);

        $cache = cache::make('phpunit', 'simpletest');
        self::assertSame('bar', $cache->get('foo'));
        self::assertContains('DRY RUN - Would purge caches', $output);
    }

    protected function execute($dryrun, $verbose) {
        $cleaner = new cleaner($dryrun, $verbose);

        ob_start();
        $cleaner->execute();
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}
