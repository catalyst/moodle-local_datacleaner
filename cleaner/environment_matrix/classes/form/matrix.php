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
 * Environment matrix edit form.
 *
 * @package    cleaner_environment_matrix
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  2017 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace cleaner_environment_matrix\form;

use html_writer;
use moodleform;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

require_once($CFG->libdir . '/formslib.php');

/**
 * A form to edit the environment matrix.
 *
 * @package    cleaner_environment_matrix
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  2017 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class matrix extends moodleform {
    /**
     * {@inheritDoc}
     * @see moodleform::definition()
     */
    public function definition() {
        $mform = $this->_form;

        // Obtain the data that has been passed to this form.
        $searchitems = $this->_customdata['searchitems'];
        $configitems = $this->_customdata['configitems'];
        $environments = $this->_customdata['environments'];

        // Construct environment header group. This will be used multiple times.
        $environmentheader = [];
        $environmentheader[] = &$mform->createElement('checkbox', "cb_header", 'name', '');
        foreach ($environments as $eid => $env) {
            $environmentheader[] = &$mform->createElement('text', "text_$eid$env", '', null);
            $mform->setType("text_$eid$env", PARAM_TEXT);
        }

        // Add the search element.
        $searchgroup = [];
        $searchgroup[] = &$mform->createElement('text', 'search', '', null);
        $searchgroup[] = &$mform->createElement('submit', 'searchbutton', get_string('button_search', 'cleaner_environment_matrix'), null);
        $mform->setType('search', PARAM_TEXT);
        $mform->addGroup($searchgroup, 'searchgroup', '' ,' ', false);

        // Basic separator between search and the defined list.
        $mform->addElement('html', html_writer::empty_tag('hr'));

        // Add an environment header group.
        $mform->addGroup($environmentheader, "group_header1", '', ' ', false);

        // Display the configured items associated to each environment.
        foreach ($searchitems as $sid => $item) {

            $id = $item->id;
            $configname = $item->name;
            $value = $item->value;

            $group = [];
            $group[] = &$mform->createElement('checkbox', "cb_$configname", 'name', '');

            foreach ($environments as $eid => $env) {
                $group[] = &$mform->createElement('text', "text_$eid$env", '', null);
                $mform->setType("text_$eid$env", PARAM_TEXT);
            }

            $mform->addGroup($group, "group_$configname", $configname, ' ', false);
        }

        // Basic separator between search and the defined list.
        $mform->addElement('html', html_writer::empty_tag('hr'));

        // Add an environment header group.
        $mform->addGroup($environmentheader, "group_header2", '', ' ', false);

        // Display the configured items associated to each environment.
        foreach ($configitems as $cid => $config) {
            $group = [];
            $group[] = &$mform->createElement('checkbox', "cb_$config", 'name', '');

            foreach ($environments as $eid => $env) {
                $group[] = &$mform->createElement('text', "text_$eid$env", '', null);
                $mform->setType("text_$eid$env", PARAM_TEXT);
            }

            $mform->addGroup($group, "group_$config", '', ' ', false);
        }


        foreach ($environments as $eid => $env) {
            $mform->setDefault("text_$eid$env", $env);
        }

    }

    /**
     * Validate the parts of the request form for this module
     *
     * @param array $data An array of form data
     * @param array $files An array of form files
     * @return array of error messages
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}
