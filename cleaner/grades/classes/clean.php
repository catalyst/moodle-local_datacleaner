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
 * @package    cleaner_delete_users
 * @copyright  2015 Catalyst IT
 * @author     Nigel Cunningham
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_grades;

defined('MOODLE_INTERNAL') || die();

class clean extends \local_datacleaner\clean {
    const TASK = 'Removing grades';

    /**
     * Delete all grade history.
     */
    protected static function delete_all_grades() {
        global $DB;

    }

    /**
     * Do the hard work of cleaning up users.
     */
    static public function execute() {

        global $DB, $CFG;

        // Get the settings, handling the case where new ones (dev) haven't been set yet.
        $config = get_config('cleaner_grades');

        self::update_status(self::TASK, 0, 1);

        if ($config->deleteall) {
            $DB->delete_records('grade_grades');
            $DB->delete_records('grade_grades_history');
        } else {
            $DB->execute('UPDATE {grade_grades} SET rawgrade = (id % rawgrademax) WHERE rawgrademax > 0');
            $DB->execute('UPDATE {grade_grades} SET rawgrade = 0 WHERE rawgrademax = 0');
            $DB->execute('UPDATE {grade_grades} SET finalgrade = (id % rawgrademax) WHERE rawgrademax > 0');
            $DB->execute('UPDATE {grade_grades} SET finalgrade = 0 WHERE rawgrademax = 0');
            $DB->execute('UPDATE {grade_grades_history} SET rawgrade = (id % rawgrademax) WHERE rawgrademax > 0');
            $DB->execute('UPDATE {grade_grades_history} SET rawgrade = 0 WHERE rawgrademax = 0');
            $DB->execute('UPDATE {grade_grades_history} SET finalgrade = (id % rawgrademax) WHERE rawgrademax > 0');
            $DB->execute('UPDATE {grade_grades_history} SET finalgrade = 0 WHERE rawgrademax = 0');
        }

        self::update_status(self::TASK, 1, 1);
    }
}
