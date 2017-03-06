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

        // Construct environment header group. This will be used multiple times. Once for search and configured items.
        $environmentheader = [];
        $environmentheader[] = &$mform->createElement('checkbox', 'cb_header', 'name', '', ['class' => 'cb_header']);
        foreach ($environments as $eid => $env) {
            $site = $env->environment;
            $root = $env->wwwroot;

            $key = "environments[$eid]";
            $environmentheader[] = &$mform->createElement('text', $key, '', ['disabled']);
            $mform->setType($key, PARAM_TEXT);
            $mform->setDefault($key, "$site ($root)");
        }

        // Add the search element group.
        $searchstring = get_string('button_search', 'cleaner_environment_matrix');
        $searchgroup = [];
        $searchgroup[] = &$mform->createElement('checkbox', 'search_cb', 'name', '', ['class' => 'cb_header']);
        $searchgroup[] = &$mform->createElement('text', 'search', '', null);
        $searchgroup[] = &$mform->createElement('submit', 'searchbutton', $searchstring, null);
        $mform->setType('search', PARAM_TEXT);
        $mform->addGroup($searchgroup, 'searchgroup', '' , ' ', false);

        if (!empty($searchitems)) {
            $searchtitle = [];
            $searchtitle[] = &$mform->createElement('checkbox', 'searchtitle_cb', 'name', '', ['class' => 'cb_header']);
            $searchtitle[] = &$mform->createElement('static', 'stitle', 'stitle', get_string('search_results', 'cleaner_environment_matrix'));
            $mform->addGroup($searchtitle, 'searchtitle', '' , ' ', false);

            // Basic separator between search and the defined list.
            $mform->addElement('html', html_writer::empty_tag('hr'));
            // Add an environment header group.
            $mform->addGroup($environmentheader, 'group_header1', '', ' ', false);
        }

        // Display the configured items associated to each environment.
        foreach ($searchitems as $item) {
            $configname = $item->name;
            $plugin = $item->plugin;

            $group = [];
            $cbkey = "selected[$plugin][$configname]";
            $group[] = &$mform->createElement('advcheckbox', $cbkey, 'name', '', '', [0, 1]);
            $mform->setDefault($cbkey, 0);

            foreach ($environments as $eid => $env) {
                $key = "config[$plugin][$configname][$eid]";
                $group[] = &$mform->createElement('text', $key, '');
                $mform->setType($key, PARAM_TEXT);
            }

            $mform->addGroup($group, "group_$configname", $plugin . ' | ' . $configname, ' ', false);
        }

        if (!empty($configitems)) {
            $mform->addElement('html', html_writer::empty_tag('br'));

            $existingtitle = [];
            $existingtitle[] = &$mform->createElement('checkbox', 'existing_cb', 'name', '', ['class' => 'cb_header']);
            $existingtitle[] = &$mform->createElement('static', 'etitle', 'etitle', get_string('existing_configuration', 'cleaner_environment_matrix'));
            $mform->addGroup($existingtitle, 'existingtitle', '' , ' ', false);

            // Basic separator between search and the defined list.
            $mform->addElement('html', html_writer::empty_tag('hr'));
            // Add an environment header group.
            $mform->addGroup($environmentheader, 'group_header2', '', ' ', false);
        }

        // Display the configured items associated to each config type.
        foreach ($configitems as $configname => $items) {
            $plugin = array_values($items)[0]->plugin;
            $group = [];
            $cbkey = "selected[$plugin][$configname]";
            $group[] = &$mform->createElement('advcheckbox', $cbkey, 'name', '', '', [0, 1]);
            $mform->setDefault($cbkey, 1);

            foreach ($environments as $eid => $env) {
                $key = "config[$plugin][$configname][$eid]";
                $group[] = &$mform->createElement('text', $key, '');
                $mform->setType($key, PARAM_TEXT);
                $value = empty($items[$eid]->value) ? '' : $items[$eid]->value;
                $mform->setDefault($key, $value);
            }

            $mform->addGroup($group, "group_$configname", $plugin . ' | ' . $configname, ' ', false);
        }

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('checkbox', 'submit_cb', 'name', '', ['class' => 'cb_header']);
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
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
