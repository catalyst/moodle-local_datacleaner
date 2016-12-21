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
 * cache_cleaner class.
 *
 * @package     cleaner_orphaned_sitedata
 * @author      Ghada El-Zoghbi <ghada@catalyst-au.net>
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace cleaner_orphaned_sitedata;

use cache_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * cache_cleaner class.
 *
 * @package     cleaner_orphaned_sitedata
 * @author      Ghada El-Zoghbi <ghada@catalyst-au.net>
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cache_cleaner {
    private $dryrun;

    public function __construct($dryrun) {
        $this->dryrun = $dryrun;
    }

    public function execute() {
        clean::println(
            get_string($this->dryrun ? 'wouldpurgecache' : 'willpurgecache', 'cleaner_orphaned_sitedata')
        );

        if (!$this->dryrun) {
            cache_helper::purge_all(true);
            purge_all_caches();
        }
    }
}
