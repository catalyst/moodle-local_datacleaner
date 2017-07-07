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

namespace cleaner_muc;

use cache_helper;
use cleaner_muc\cache\exposed_cache_config;
use cleaner_muc\dml\muc_config_db;

defined('MOODLE_INTERNAL') || die();

/**
 * @package     cleaner_muc
 * @subpackage  local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleaner {
    public static function extract_site_identifier($configuration) {
        $pattern = "/'siteidentifier'\\s+=>\\s+'(\w+)'/";
        if (!preg_match($pattern, $configuration, $matches)) {
            return null;
        }
        return $matches[1];
    }

    /** @var bool */
    private $dryrun;

    /** @var bool */
    private $verbose;

    public function __construct($dryrun, $verbose) {
        $this->dryrun = $dryrun;
        $this->verbose = $verbose;
    }

    public function execute() {
        global $CFG;

        $this->verbose("MUC Cleaner - Verbose Mode - Environment: {$CFG->wwwroot}");
        $config = muc_config_db::get_by_wwwroot($CFG->wwwroot);

        if (is_null($config)) {
            $this->print_configuration_not_found();
            return;
        }

        if (!$this->check_site_identifier($config->get_configuration())) {
            return;
        }

        $this->replace_muc_configuration($config->get_configuration());

        $this->purge_caches();
    }

    private function verbose($message) {
        if ($this->verbose) {
            mtrace($message);
        }
    }

    public function print_configuration_not_found() {
        mtrace("MUC Configuration not found in database.");
        if (!$this->verbose) {
            return;
        }

        $found = muc_config_db::get_environments();
        mtrace('Configurations found (' . count($found) . '):');

        foreach ($found as $wwwroot) {
            mtrace(" - {$wwwroot}");
        }
    }

    public function replace_muc_configuration($configuration) {
        if ($this->dryrun) {
            mtrace('DRY RUN - Would load MUC Configuration.');
        } else {
            file_put_contents(exposed_cache_config::get_config_file_path(), $configuration);
            mtrace('MUC Configuration Loaded!');
        }
    }

    public function purge_caches() {
        if ($this->dryrun) {
            mtrace('DRY RUN - Would purge caches.');
        } else {
            purge_all_caches();
            $this->verbose('Caches purged.');
        }
    }

    private function check_site_identifier($configuration) {
        $expected = cache_helper::get_site_identifier();
        $found = self::extract_site_identifier($configuration);

        $this->verbose('Site Identifier:');
        $this->verbose('* Expected: '.$expected);
        $this->verbose('*    Found: '.$found);

        if ($expected != $found) {
            mtrace('*** ERROR *** MUC Config not loaded, invalid site identifier.');
            return false;
        }

        return true;
    }
}
