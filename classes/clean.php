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

namespace local_datacleaner;

use core\base;

defined('MOODLE_INTERNAL') || die();

abstract class clean {
    private static $tasks = array(); // For storing task start times.

    protected static $options = array();

    protected $needscascadedelete = false;

    protected static $step = 0;

    protected static $maxsteps = 0;

    protected static $exectime = 0;

    /**
     * Constructor
     *
     * @param bool $options Runtime configuration options for the plugin to apply.
     */
    public function __construct($options = array()) {
        self::$options = $options;
    }

    /**
     * Get whether this class needs cascade deletion.
     *
     * @return bool Whether cascade deletion is needed.
     */
    public function needs_cascade_delete() {
        return $this->needscascadedelete;
    }

    /**
     * Execute the plugin. Template to be overridden.
     */
    static public function execute() {
    }

    /**
     * Possibly output a debugging message.
     */
    static protected function debug($message) {
        if (self::$options['verbose']) {
            echo $message;
        }
    }

    /**
     * Print the current status of the task.
     */
    static protected function update_status() {

        $taskname = static::TASK;
        $itemno = static::$step;
        $total = static::$maxsteps;

        $perc = $itemno * 100 / $total;
        $timeleft = null;

        if (isset(self::$tasks[$taskname])) {
            // Print the elapsed and remaining time.
            $now = time();

            $start = self::$tasks[$taskname];
            $eta = ($now - $start) * $total / $itemno + $start;
            $elapsed = $now - $start;
            $timeleft = intval($elapsed).' seconds elapsed, '.intval($eta - $now).' seconds remaining';
        } else {
            // Save the start time for this task.
            self::$tasks[$taskname] = time();
        }

        printf("\r %-20s %4d%% (%d/%d)    $timeleft  ", $taskname, $perc, $itemno, $total);

        if ($itemno == $total) {
            // No more output for this step; move to a new line.
            printf("\n");
        }
    }

    /**
     * Start a new task.
     *
     * @param int $maxsteps The number of steps for the task.
     */
    static protected function new_task($maxsteps) {
        static::$step = 0;
        static::$maxsteps = $maxsteps;
        static::update_status();
        static::$exectime = -microtime(true);
    }

    /**
     * Completed a step. Possibly the last one.
     *
     * @param int $increment The amount by which to increase the step number.
     */
    static protected function next_step($increment = 1) {
        static::$step += $increment;
        static::update_status();

        // Print the execution time if we're done.
        if (static::$step == static::$maxsteps) {
            static::$exectime += microtime(true);
            echo "Execution took ", sprintf('%f', static::$exectime), " seconds.", PHP_EOL;
        }
    }

    // The following routines are shared by the user scramble/delete subplugins.

    /**
     * Build an array of criteria from the module config.
     *
     * @return array $criteria Criteria to pass to the where fragment generator.
     */
    protected static function get_user_criteria($config) {
        global $CFG;

        $criteria = array();

        /* Minimum age? */
        if (isset($config->minimumage)) {
            $interval = $config->minimumage;
            $criteria['timestamp'] = time() - ($interval * 24 * 60 * 60);
        }

        /* Keep site admins? */
        $keepsiteadmins = isset($config->keepsiteadmins) ? $config->keepsiteadmins : true;
        if ($keepsiteadmins) {
            $criteria['ignored_uids'] = explode(',', $CFG->siteadmins);
        }

        /* Keep user names */
        $keepusernames = isset($config->keepusernames) ? trim($config->keepusernames) : "";
        if (!empty($keepusernames)) {
            $criteria['ignored_usernames'] = $keepusernames;
        }

        return $criteria;
    }

    /**
     * Build a SQL where clause from the criteria provided.
     *
     * @param  array $criteria The criteria to apply
     *
     * @return array $sql, $params The SQL & parameters
     */
    public static function get_user_where_sql($criteria = array()) {
        global $DB;

        $extrasql = '';
        $params = array();

        if (isset($criteria['timestamp'])) {
            $extrasql = ' AND lastaccess < :timestamp ';
            $params['timestamp'] = $criteria['timestamp'];
        }

        if (isset($criteria['ignored_uids'])) {
            list($newextrasql, $extraparams) = $DB->get_in_or_equal($criteria['ignored_uids'], SQL_PARAMS_NAMED, 'uid', false);
            $extrasql .= ' AND id '.$newextrasql;
            $params = array_merge($params, $extraparams);
        }

        if (isset($criteria['ignored_usernames'])) {
            $keepusernames = explode(',', $criteria['ignored_usernames']);
            if (!empty($keepusernames)) {
                foreach ($keepusernames as &$name) {
                    $name = clean_param($name, PARAM_USERNAME);
                }
                list($newextrasql, $extraparams) = $DB->get_in_or_equal($keepusernames, SQL_PARAMS_NAMED, 'uname', false);
                $extrasql .= ' AND username '.$newextrasql;
                $params = array_merge($params, $extraparams);
            }
        }

        if (isset($criteria['deleted'])) {
            $extrasql .= ' AND deleted = :deleted ';
            $params['deleted'] = $criteria['deleted'];
        }

        return array($extrasql, $params);
    }

    /**
     * Get the number of users that will be returned by get_users below.
     *
     * @param  array $config An array of plugin configuration settings to apply.
     *
     * @return int The number of users that meet the criteria.
     */
    public static function get_user_count($config = array()) {
        global $DB;

        $criteria = self::get_user_criteria($config);
        list($where, $whereparams) = self::get_user_where_sql($criteria);

        return $DB->count_records_select('user', 'id > 2 '.$where, $whereparams);
    }

    /**
     * Get an array of user objects meeting the criteria provided - possibly not all of them.
     *
     * @param array $config An array of plugin configuration settings to apply.
     * @param string $sort A SQL ORDER BY parameter.
     * @param string $fields A command separated list of fields to return.
     *
     * @return array $result An array of user records.
     */
    public static function get_user_chunk($config = array(), $offset = 0) {
        global $DB;

        $criteria = self::get_user_criteria($config);
        list($where, $whereparams) = self::get_user_where_sql($criteria);

        $uids = $DB->get_records_select('user', 'id > 2 '.$where, $whereparams, 'id', 'id', $offset, 10000);
        return array_keys($uids);
    }

    /**
     * Delete a list of records in chunks.
     *
     * @param string $table The table from which to delete records
     * @param string $field The field against which to compare values
     * @param array $ids An array of IDs to match
     */
    public static function delete_records_list_chunked($table, $field, $ids) {
        global $DB;

        $chunks = array_chunk($ids, 65000);
        foreach ($chunks as &$chunk) {
            list($sql, $params) = $DB->get_in_or_equal($chunk);
            $DB->delete_records_list($table, $field, $params);
        }
    }

    /**
     * Get the criteria for the list of courses.
     */
    protected static function get_courses_criteria($config) {
        $criteria = array();

        if (isset($config->minimumage)) {
            $criteria = array();
            $criteria['timestamp'] = time() - ($config->minimumage * 24 * 60 * 60);
        }

        if (isset($config->categories) && !empty($config->categories)) {
            $criteria['categories'] = $config->categories;
        }
        if (isset($config->courses) && !empty($config->courses)) {
            $criteria['courses'] = $config->courses;
        }

        return $criteria;
    }

    /**
     * Get an array of course objects meeting the criteria provided
     *
     * @param  array $criteria An array of criteria to apply.
     * @return array $result   The array of matching course objects.
     */
    protected static function get_courses($criteria = array()) {
        global $DB;

        $extrasql = '';
        $params = array();

        // If no criteria are selected, clean nothing rather than everything.
        if (empty($criteria)) {
            return array();
        }

        if (isset($criteria['timestamp'])) {
            $extrasql .= ' AND startdate <= :startdate ';
            $params['startdate'] = $criteria['timestamp'];
        }

        if (isset($criteria['categories'])) {
            list($sql, $sqlparams) = $DB->get_in_or_equal(explode(",", $criteria['categories']), SQL_PARAMS_NAMED, 'crit_');
            $extrasql .= ' AND category '.$sql;
            $params = array_merge($params, $sqlparams);
        }

        if (isset($criteria['courses'])) {
            list($sql, $sqlparams) = $DB->get_in_or_equal(explode("\n", $criteria['courses']), SQL_PARAMS_NAMED, 'course_', false);
            $extrasql .= ' AND shortname '.$sql;
            $params = array_merge($params, $sqlparams);
        }

        return $DB->get_records_select_menu('course', 'id > 1 '.$extrasql, $params, '', 'id, id');
    }
}
