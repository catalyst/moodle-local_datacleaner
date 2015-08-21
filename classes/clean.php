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

    protected static $dryrun = true;
    protected static $verbose = true;
    protected $needs_cascade_delete = false;

    protected static $numusers = 0;

    protected static $step = 0;
    protected static $maxsteps = 0;
    protected static $exectime = 0;

    /**
     * Constructor
     *
     * @param bool $dryrun Whether we're doing a dry run.
     */
    public function __construct($dryrun = true, $verbose = false) {
        self::$dryrun = $dryrun;
        self::$verbose = $verbose;
    }

    /**
     * Get whether this class needs cascade deletion.
     *
     * @return bool Whether cascade deletion is needed.
     */
    public function needs_cascade_delete() {
        return $this->needs_cascade_delete;
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
        if (self::$verbose) {
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
            $timeleft = intval($elapsed) . ' seconds elapsed, ' . intval($eta - $now) . ' seconds remaining';

        } else {
            // Save the start time for this task.
            self::$tasks[$taskname] = time();
        }

        printf ("\r %-20s %4d%% (%d/%d)    $timeleft  ", $taskname, $perc, $itemno, $total);

        if ($itemno == $total) {
            // No more output for this step; move to a new line.
            printf("\n");
        }
    }

    static protected function new_task($maxsteps) {
        static::$step = 0;
        static::$maxsteps = $maxsteps;
        static::update_status(static::TASK, static::$step, static::$maxsteps);
        static::$exectime = -microtime(true);
    }

    static protected function next_step() {
        static::$step++;
        static::update_status(static::TASK, static::$step, static::$maxsteps);

        // Print the execution time if we're done.
        if (static::$step == static::$maxsteps) {
            static::$exectime += microtime(true);
            echo "Execution took ", sprintf('%f', static::$exectime), " seconds.", PHP_EOL;
        }
    }

    /**
     * Build an array of criteria for get_users from the module config.
     *
     * @return array $criteria Criteria to pass to get_users.
     */
    protected static function get_criteria($config) {
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
     * Get the number of users that were returned by get_users below
     */
    public static function get_num_users() {
        return self::$numusers;
    }

    /**
     * Get an array of user objects meeting the criteria provided
     *
     * @param  array $criteria An array of criteria to apply.
     * @return array $result   The array of sql fragments & named parameters to add into queries.
     */
    public static function get_users($criteria = array()) {
        global $DB;

        $extrasql = '';
        $params = array();

        if (isset($criteria['timestamp'])) {
            $extrasql = ' AND lastaccess < :timestamp ';
            $params['timestamp'] = $criteria['timestamp'];
        }

        if (isset($criteria['ignored_uids'])) {
            list($newextrasql, $extraparams) = $DB->get_in_or_equal($criteria['ignored_uids'], SQL_PARAMS_NAMED, 'uid', false);
            $extrasql .= ' AND id ' . $newextrasql;
            $params = array_merge($params, $extraparams);
        }

        if (isset($criteria['ignored_usernames'])) {
            $keepusernames = explode(',', $criteria['ignored_usernames']);
            if (!empty($keepusernames)) {
                foreach ($keepusernames as &$name) {
                    $name = clean_param($name, PARAM_USERNAME);
                }
                list($newextrasql, $extraparams) = $DB->get_in_or_equal($keepusernames, SQL_PARAMS_NAMED, 'uname', false);
                $extrasql .= ' AND username ' . $newextrasql;
                $params = array_merge($params, $extraparams);
            }
        }

        if (isset($criteria['deleted'])) {
            $extrasql .= ' AND deleted = :deleted ';
            $params['deleted'] = $criteria['deleted'];
        }

        $uids = $DB->get_records_select('user', 'id > 2 ' . $extrasql, $params);
        if (empty($uids)) {
            return array();
        }

        $uids = array_keys($uids);
        self::$numusers = count($uids);
        $chunks = array_chunk($uids, 65000);
        foreach ($chunks as &$chunk) {
            list($sql, $params) = $DB->get_in_or_equal($chunk);
            $chunk = array('sql' => $sql, 'params' => $params, 'size' => count($chunk));
        }

        return $chunks;
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
}
