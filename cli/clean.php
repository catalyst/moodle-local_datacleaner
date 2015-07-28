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
 * @package    local_datacleaner
 * @copyright  2015 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Check if host name is prod.
// Check if cron is running or has run recently.
// Check last user login.
// if any of these are true then bail

// grab list of all cleaner plugins, in priority order, which are enabled
// record time stamps
// run the clean() method of each plugin
// ideally have an api for each plugin a standardish log function which it calls with incremental
// data so all the plugins output stuff in the same format
//
// something like cleaner_status('what I'm doing', $X, $total);
//



