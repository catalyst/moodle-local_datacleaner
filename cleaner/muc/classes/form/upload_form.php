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

namespace cleaner_muc\form;

use cleaner_muc\dml\muc_config_db;
use context_user;
use moodleform;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->libdir}/formslib.php");

/**
 * @package     cleaner_muc
 * @subpackage  local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_form extends moodleform {
    public static function filename_to_wwwroot($wwwroot) {
        $wwwroot = preg_replace('#\.muc$#', '', $wwwroot); // Remove .muc suffix.
        $wwwroot = rawurldecode($wwwroot);
        return $wwwroot;
    }

    public function process_submit() {
        $data = $this->get_data();
        if ($data) {
            foreach ($data->files as $filename => $config) {
                $wwwroot = self::filename_to_wwwroot($filename);
                muc_config_db::save($wwwroot, $config);
            }
            return true;
        }
        return false;
    }

    protected function definition() {
        $this->_form->addElement(
            'filemanager',
            'mucfiles',
            get_string('setting_uploader_files', 'cleaner_muc'),
            null,
            ['subdirs' => false]
        );

        $this->add_action_buttons(false);
    }

    public function get_data() {
        $data = parent::get_data();
        if (is_null($data)) {
            return null;
        }

        $data->files = $this->prepare_files_data($data);

        return $data;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $filesdata = $this->prepare_files_data($data);

        foreach ($filesdata as $filename => $config) {
            if (substr($filename, -4) != '.muc') {
                $errors['mucfiles'] = get_string('error_upload_invalid_muc_extension', 'cleaner_muc', $filename);
            }

            if (substr($config, 0, 6) != '<?php ') {
                $errors['mucfiles'] = get_string('error_upload_invalid_php', 'cleaner_muc', $filename);
            }
        }

        return $errors;
    }

    private function prepare_files_data($data) {
        global $USER;

        $data = (array)$data;
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            context_user::instance($USER->id)->id,
            'user',
            'draft',
            $data['mucfiles']
        );

        $filesdata = [];
        foreach ($files as $file) {
            if ($file->get_filename() === '.') {
                continue;
            }
            $filesdata[$file->get_filename()] = $file->get_content();
        }

        return $filesdata;
    }

    public function get_errors() {
        return $this->_form->_errors;
    }
}
