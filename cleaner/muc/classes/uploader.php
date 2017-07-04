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

namespace cleaner_muc;

use context_user;
use moodleform;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->libdir}/formslib.php");

/**
 * Class downloader
 *
 * @package     cleaner_muc
 * @subpackage  local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class uploader extends moodleform {
    public function process_submit() {
        $data = $this->get_data();
        if ($data) {
            return true;
        }
        return false;
    }

    public function render_upload_section() {
        global $PAGE;
        $renderer = $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL);

        return $renderer->heading(get_string('setting_uploader', 'cleaner_muc')) .
               $this->render();
    }

    protected function definition() {
        $this->_form->addElement(
            'filemanager',
            'mucfiles',
            get_string('setting_uploader_files', 'cleaner_muc'),
            null,
            ['subdirs' => false]
        );

        $this->add_action_buttons();
    }

    public function get_data() {
        global $USER;

        $data = parent::get_data();
        if (is_null($data)) {
            return null;
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files(
            context_user::instance($USER->id)->id,
            'user',
            'draft',
            $data->mucfiles
        );

        $data->files = [];
        foreach ($files as $file) {
            if ($file->get_filename() === '.') {
                continue;
            }
            $data->files[$file->get_filename()] = $file->get_content();
        }

        return $data;
    }
}
