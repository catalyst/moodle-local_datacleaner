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
 * External Web Service Definition
 *
 * @package    local_datacleaner
 * @copyright  2015 Catalyst IT
 * @author     Nigel Cunningham
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");

class local_datacleaner_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_datacleaner_state_parameters() {
        return new external_function_parameters(
                array('cancel' => new external_value(PARAM_BOOL, 'Whether we are requesting that the clean be stopped."', VALUE_DEFAULT, false))
        );
    }

    /**
     * Returns the progress of the datacleaning operation.
     *
     * It doesn't make sense to run more than one cleansing at once, so we don't worry about multiple instances.
     *
     * @return array state for each plugin.
     */
    public static function get_datacleaner_state($cancel = false) {
        global $USER;

        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(self::datacleaner_parameters(),
                array('cancel' => $cancel));

        //Capability checking
        if (!is_siteadmin($USER)) {
            throw new moodle_exception('forbiddenwsuser', 'webservice');
        }

        return json_encode(get_config('status', 'local_datacleaner'));
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_datacleaner_state_returns() {
        return new external_value(PARAM_RAW, 'The datacleaning status');
    }

    /**
     * This API can be used from Ajax (that's the whole point!)
     *
     * @return bool Whether it's allowed.
     */
    public static function get_datacleaner_state_is_allowed_from_ajax() {
        return true;
    }
}
