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

    protected static $debugging = true;

    protected static $numusers = 0;

    protected static $step = 0;
    protected static $maxsteps = 0;
    protected static $exectime = 0;

    protected static $constraint_removal_queries = array();

    protected static $unrelated = array();
    protected static $depth = 0;

    /**
     * Constructor
     *
     * @param bool $dryrun Whether we're doing a dry run.
     */
    public function __construct($dryrun) {
        self::$debugging = $dryrun;
    }

    /**
     * Get whether debugging is enabled (doing a dry run)
     *
     * @return bool True if debugging else false
     */
    static public function get_debugging() {
        return self::$debugging;
    }

    /**
     * Execute the plugin. Template to be overridden.
     */
    static public function execute() {

    }

    /**
     * Possibly output a debugging message.
     */
    private static function debug($message) {
        if (self::$debugging) {
            echo $message;
        }
    }

    /*
     * $taskname String A unique name for a cleaning task
     *
     * SHOULD be called at the start with an itemno of 0?
     */
    static protected function update_status($taskname, $itemno, $total) {

        $perc = $itemno * 100 / $total;

        $eta = null;
        $delta = null;
        $now = time();
        $start = null;
        $timeleft = null;
        if (isset(self::$tasks[$taskname])) {

            $start = self::$tasks[$taskname];
            $eta = ($now - $start) * $total / $itemno + $start;
            $elapsed = $now - $start;
            $timeleft = intval($elapsed) . ' seconds elapsed, ' . intval($eta - $now) . ' seconds remaining';

        } else {
            self::$tasks[$taskname] = time();
        }

        // If first status record time stamp
        // Do calculation of ETA based on first status.

        printf ("\r %-20s %4d%% (%d/%d)    $timeleft  ", $taskname, $perc, $itemno, $total);

        if ($itemno == $total) {
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
    public static function get_criteria($config) {
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

    /**
     * Load an install.xml file, checking that it exists, and that the structure is OK.
     *
     * This is copied from lib/ddl/database_manager.php because it's a private method there.
     *
     * @param string $file the full path to the XMLDB file.
     * @return xmldbfile the loaded file.
     */
    public static function load_xmldbfile($file) {
        global $CFG;

        $xmldbfile = new \xmldb_file($file);

        if (!$xmldbfile->fileExists()) {
            throw new ddl_exception('ddlxmlfileerror', null, 'File does not exist');
        }

        $loaded = $xmldbfile->loadXMLStructure();
        if (!$loaded || !$xmldbfile->isLoaded()) {
            // Show info about the error if we can find it.
            if ($structure = $xmldbfile->getStructure()) {
                if ($errors = $structure->getAllErrors()) {
                    throw new ddl_exception('ddlxmlfileerror', null, 'Errors found in XMLDB file: '. implode (', ', $errors));
                }
            }
            throw new ddl_exception('ddlxmlfileerror', null, 'not loaded??');
        }

        return $xmldbfile;
    }

    /**
     * Based on get_install_xml_schema in lib/ddl/database_manager.php.
     *
     * Reads the install.xml files for Moodle core and modules and returns an array of
     * xmldb_structure object with xmldb_table from these files.
     * @return xmldb_structure schema from install.xml files
     */
    static public function get_xml_schema() {
        global $CFG;

        $cache = \cache::make('local_datacleaner', 'schema');
        $schema = $cache->get('schema');

        if ($schema) {
            return $schema;
        }

        require_once($CFG->libdir.'/adminlib.php');

        $schema = new \xmldb_structure('export');
        $schema->setVersion($CFG->version);
        $dbdirs = get_db_directories();
        foreach ($dbdirs as $dbdir) {
            $xmldb_file = new \xmldb_file($dbdir.'/install.xml');
            if (!$xmldb_file->fileExists() or !$xmldb_file->loadXMLStructure()) {
                continue;
            }
            $structure = $xmldb_file->getStructure();
            $tables = $structure->getTables();
            foreach ($tables as $table) {
                $table->setPrevious(null);
                $table->setNext(null);
                $schema->addTable($table);
            }
        }

        $cache->set('schema', $schema);

        return $schema;
    }

    /**
     * Add a database SQL query to be passed to $DB->execute() later
     *
     * @param string $query The string to save
     */
    static protected function add_constraint_removal_query($query) {
        if (empty(self::$constraint_removal_queries)) {
            register_shutdown_function(array('local_datacleaner\clean', 'remove_cascade_deletion'));
        }
        self::$constraint_removal_queries[] = $query;
    }

    /**
     * Get additional field names to try for a parent table.
     *
     * @param string $parent The parent table name
     * @return array The list of base field names to consider
     */
    static public function get_checks_for_parent_table($parent) {
        $checks = array($parent);

        // Additional table names to try. Eg. an assignment[id|instance|_id] field will be tested against assign.
        switch ($parent) {
            case 'assign':
                $checks[] = 'assignment';
                break;
            case 'course_sections':
                $checks[] = 'section';
                break;
            case 'course_modules':
                $checks[] = 'coursemodule';
                break;
            case 'user':
                $checks[] = 'student';
                break;
            case 'course':
                $checks[] = 'courses';
                break;
            case 'grade_grades':
                $checks[] = 'grade';
                break;
            case 'context':
                $checks[] = 'parentcontext';
                break;
        }

        return $checks;
    }

    /**
     * Does this fieldname look like a candidate for a foreign key?
     *
     * @param array $checks The list of base field names to match
     * @param string $fieldname The field to consider
     *
     * @return bool Whether the fieldname matches the checks.
     */
    static public function will_use_table($checks, $fieldname) {
        foreach ($checks as $test) {
            if ($fieldname == $test || $fieldname == "{$test}id" || $fieldname == "{$test}instance" ||
                    $fieldname == "{$test}_id") {
                return true;
            }
        }

        return false;
    }

    /**
     * Add cascade deletion to courseIDs.
     *
     * @param array $schema The database schema
     * @param string $param The parent table for which we're seeking children.
     */
    static public function add_cascade_deletion($schema, $parent = 'course') {
        static $visited = array();
        global $DB;

        self::$depth++;

        if (isset($visited[$parent])) {
            self::$depth--;
            return;
        }

        if (self::$depth == 1) {
            foreach($schema->getTables() as $table) {
                self::$unrelated[$table->getName()] = 1;
            }

            if (self::get_debugging()) {
                echo "\n";
            }
        }

        $visited[$parent] = true;

        if (!self::get_debugging()) {
            self::debug(">> Setting up cascade deletion for {$parent}\n");

            // Add index.
            try {
                self::debug("Adding index to {$parent} for id ... ");
                $DB->execute("CREATE INDEX {$parent}_id ON {{$parent}} USING btree (id)");
                self::add_constraint_removal_query("DROP INDEX {$parent}_id");
                self::debug("success.\n");
            } catch (\dml_write_exception $e) {
                // We don't mind if it already exists.
                if (substr($e->error, -14) == "already exists") {
                    self::debug("already exists\n");
                } else {
                    self::debug("failed {$e->error}.\n");
                }
            }
        }

        $checks = self::get_checks_for_parent_table($parent);

        // Iterate over tables in the schema ...
        foreach($schema->getTables() as $table) {
            $tablename = $table->getName();
            if ($tablename == $parent) {
                continue;
            }
            $fields = $table->getFields();
            // ... and over fields in the table ...
            foreach ($fields as $field) {
                $fieldname = $field->getName();
                // ... looking for a field of interest ...
                $willuse = self::will_use_table($checks, $fieldname);

                if (self::get_debugging() && $willuse) {
                    echo ($willuse ? 'X ' : '  ') . " {$parent}: {$fieldname} in {$tablename}\n";
                }

                if ($willuse) {
                    unset(self::$unrelated[$tablename]);

                    $indices = $table->getIndexes();
                    $indexname = false;
                    foreach ($indices as $index) {
                        $indexfields = $index->getFields();
                        if (count($indexfields) == 1 && $indexfields[0] == $fieldname) {
                            $indexname = $index->getName();
                        }
                    }

                    if (!$indexname) {
                        $indexname = "u_{$parent}";
                    }

                    if (!self::get_debugging()) {
                        try {
                            /* Before we try to add the index, look for records that will prevent it */
                            self::debug("Checking for mismatches between {$parent} and {$tablename}.{$fieldname}.\r");
                            $conflicts = $DB->count_records_sql(
                                    "SELECT COUNT('x') FROM {{$tablename}}
                                    LEFT JOIN {{$parent}} ON {{$tablename}}.{$fieldname} = {{$parent}}.id
                                    WHERE {{$parent}}.id IS NULL");
                            if ($conflicts) {
                                self::debug("Getting total number of records in {$tablename}.\r");
                                $total = $DB->count_records($tablename);
                                if ($total > 100 && ($conflicts / $total) < 0.05) {
                                    self::debug("Deleting {$conflicts} of {$total} records from {$tablename} that don't match {$parent} ... ");
                                    $DB->execute(
                                            "DELETE FROM {{$tablename}} WHERE NOT EXISTS (
                                        SELECT 1 FROM {{$parent}} WHERE {{$tablename}}.{$fieldname} = {{$parent}}.id)");
                                } else {
                                    if ($conflicts < $total) {
                                        self::debug("{$conflicts}/{$total} records from the {$fieldname} field in {$tablename} don't match {$parent} ids. Assuming this is not really a candidate for referential integrity.\n");
                                    }
                                    continue;
                                }
                            }
                            self::debug("Adding cascade delete to {$tablename}, field {$fieldname} for deletions from table {$parent} ... ");
                            $DB->execute("ALTER TABLE {{$tablename}}
                                    ADD CONSTRAINT c_{$indexname}
                                    FOREIGN KEY ({$fieldname})
                                    REFERENCES {{$parent}}(id)
                                    ON DELETE CASCADE");
                            self::add_constraint_removal_query("ALTER TABLE {{$tablename}} DROP CONSTRAINT c_{$indexname}");
                            self::debug("success.\n");
                        } catch (\dml_write_exception $e) {
                            if (substr($e->error, -14) == "already exists") {
                                self::debug("already exists.\n");
                            } else {
                                self::debug("failed ({$e->error}).\n");
                            }
                        } catch (\dml_read_exception $e) {
                            // Trying to match fields of different types?
                            if (substr($e->error, 0, 32) == "ERROR:  operator does not exist:") {
                                self::debug("ID field from {$parent} table and {$fieldname} from {$tablename} have different data types.\n");
                            } else if (substr($e->error, 0, 16) == "ERROR:  relation") {
                                self::debug("{$tablename} table missing?! Perhaps there's an upgrade to be done.\n");
                            } else {
                                self::debug("failed ({$e->error})\n");
                            }
                        }
                    }

                    self::add_cascade_deletion($schema, $tablename);
                }
            }
        }
        self::$depth--;

        if (!self::$depth && !empty(self::$unrelated)) {
            $toprint = array_keys(self::$unrelated);
            sort($toprint);
            foreach($toprint as $table) {
                echo "- {$table}\n";
            }
        }
    }

    /**
     * Remove cascade deletion from courseIDs.
     */
    static public function remove_cascade_deletion() {
        global $DB;

        self::debug("Removing cascade deletions and indices that were added.\n");

        foreach (array_reverse(self::$constraint_removal_queries) as $query) {
            $DB->execute($query);
        }

        self::$constraint_removal_queries = array();
    }

}
