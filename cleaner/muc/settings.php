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
 * @subpackage  local_datacleaner
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @var $ADMIN  admin_root
 */

defined('MOODLE_INTERNAL') || die();

if (!$hassiteconfig) {
    return;
}

$settings = null;

$ADMIN->add('datacleaner', new admin_externalpage(
    'cleaner_muc_downloader',
    get_string('setting_downloader', 'cleaner_muc'),
    new moodle_url('/local/datacleaner/cleaner/muc/index.php')
));
