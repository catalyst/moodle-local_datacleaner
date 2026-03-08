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

namespace cleaner_orphaned_sitedata;

/**
 * Data cleaner class for orphaned sitedata.
 *
 * @package     cleaner_orphaned_sitedata
 * @copyright   2016 Ghada El-Zoghbi <ghada@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tempfiles_cleaner {
    /**
     * Dry run.
     *
     * @var mixed
     */
    private $dryrun;

    /**
     * Constructor.
     *
     * @param mixed $dryrun
     */
    public function __construct($dryrun) {
        $this->dryrun = $dryrun;
    }

    /**
     * Execute the cleaning process.
     */
    public function execute() {
        global $CFG;
        $tempdirectory = $CFG->tempdir;

        clean::println(
            get_string(
                $this->dryrun ? 'woulddeletetemp' : 'willdeletetemp',
                'cleaner_orphaned_sitedata',
                $tempdirectory
            )
        );

        if (!$this->dryrun) {
            if (!remove_dir($tempdirectory, true)) {
                clean::println(get_string('errordeletingdir', 'local_datacleaner', $tempdirectory));
            }
        }
    }
}
