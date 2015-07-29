<?php

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
 * Data cleaner local plugin template external functions and service definitions.
 *
 * @package    local_datacleaner
 * @copyright  2015 Catalyst IT
 * @author     Nigel Cunningham
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.
$functions = array(
        'local_datacleaner_get_datacleaner_state' => array(
                'classname'   => 'local_datacleaner_external',
                'methodname'  => 'get_datacleaner_state',
                'classpath'   => 'local/datacleaner/externallib.php',
                'description' => 'Return json encoded state of the current or last data cleaner run',
                'type'        => 'read',
        )
);
