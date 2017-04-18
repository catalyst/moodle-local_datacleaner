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

        // A text field to seach for config and plugin items.
        $this->render_search_field();

        // Create the header to be used multiple times.
        $environmentheader = $this->render_environment_list();

        // Search results that do not have any saved configuration will be displayed here.
        $this->render_search_results($environmentheader);

        // Saved configuration items will be displayed here.
        $this->render_saved_results($environmentheader);

        // Submit / cancel buttons.
        $this->render_submit_buttons();
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

    /**
     * A list of environments from data passed to the forum. This is populated from the environment bar configuration.
     *
     * @return array
     */
    private function render_environment_list() {
        $mform = $this->_form;

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

        return $environmentheader;
    }

    /**
     * Search field.
     *
     * The order of search terms is important.
     *
     * Word 1: Searches {config} name
     * Word 2: Searches {config_plugins} plugin.
     *
     * If one word is specified it will search for both {config} name and {config_plugins} name.
     * If both words are specified it will search for both {config} name and {config_plugins} name, plugin.
     */
    private function render_search_field() {
        $mform = $this->_form;

        // Add the search element group.
        $searchstring = get_string('button_search', 'cleaner_environment_matrix');
        $searchgroup = [];
        $searchgroup[] = &$mform->createElement('checkbox', 'search_cb', 'name', '', ['class' => 'cb_header']);
        $searchgroup[] = &$mform->createElement('text', 'search', '', null);
        $searchgroup[] = &$mform->createElement('submit', 'searchbutton', $searchstring, null);
        $mform->setType('search', PARAM_TEXT);
        $mform->addGroup($searchgroup, 'searchgroup', '' , ' ', false);
    }

    /**
     * Display only results from the search that do not have saved configuration.
     *
     * @param array $environmentheader
     */
    private function render_search_results($environmentheader) {
        $mform = $this->_form;

        $searchitems = $this->_customdata['searchitems'];
        $environments = $this->_customdata['environments'];

        if (!empty($searchitems)) {
            $header = html_writer::tag('h2', get_string('search_results', 'cleaner_environment_matrix'));
            $searchtitle = [];
            $searchtitle[] = &$mform->createElement('checkbox', 'searchtitle_cb', 'name', '', ['class' => 'cb_header']);
            $searchtitle[] = &$mform->createElement('static', 'stitle', 'stitle', $header);
            $mform->addGroup($searchtitle, 'searchtitle', '' , ' ', false);

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

    }

    /**
     * Display only results that have saved configuration.
     *
     * @param array $environmentheader
     */
    private function render_saved_results($environmentheader) {
        $mform = $this->_form;

        $configitems = $this->_customdata['configitems'];
        $environments = $this->_customdata['environments'];

        if (!empty($configitems)) {
            $mform->addElement('html', html_writer::empty_tag('br'));

            $header = html_writer::tag('h2', get_string('existing_configuration', 'cleaner_environment_matrix'));

            $existingtitle = [];
            $existingtitle[] = &$mform->createElement('checkbox', 'existing_cb', 'name', '', ['class' => 'cb_header']);
            $existingtitle[] = &$mform->createElement('static', 'etitle', 'etitle', $header);
            $mform->addGroup($existingtitle, 'existingtitle', '' , ' ', false);

            // Add an environment header group.
            $mform->addGroup($environmentheader, 'group_header2', '', ' ', false);
        }

        // Display the configured items associated to each config type.
        foreach ($configitems as $plugin => $confignames) {
            foreach ($confignames as $configname => $items) {
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

        }
    }

    /**
     * Display submit / cancel buttons.
     */
    private function render_submit_buttons() {
        $mform = $this->_form;

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('checkbox', 'submit_cb', 'name', '', ['class' => 'cb_header']);
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}
