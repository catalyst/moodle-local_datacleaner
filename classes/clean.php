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

    protected static $numusers = 0;

    protected static $step = 0;
    protected static $maxsteps = 0;
    protected static $exectime = 0;

    static public function execute() {

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
     * Add cascade deletion to courseIDs.
     */
    static public function add_cascade_deletion($schema, $parent = 'course', $depth = 1) {
        global $DB;

        if ($depth > 8) {
            return;
        }

        // echo ">> Setting up cascade deletion for {$parent}\n";
        $dbmanager = $DB->get_manager();

        // Add index.
        try {
            // echo "Adding index to {$parent} for id ... ";
            $DB->execute("CREATE INDEX {$parent}id ON {{$parent}} USING btree (id)");
            // echo "success.\n";
        } catch (\dml_write_exception $e) {
            // We don't mind if it already exists.
            if (substr($e->error, -14) == "already exists") {
                // echo "already exists\n";
            } else {
                // echo "failed {$e->error}.\n";
            }
        }
        // Iterate over tables in the schema ...
        foreach($schema->getTables() as $table) {
            $tableName = $table->getName();
            if ($tableName == $parent) {
                continue;
            }
            $fields = $table->getFields();
            // ... and over fields in the table ...
            foreach ($fields as $field) {
                $fieldName = $field->getName();
                // ... looking for a field of interest ...
                if ($fieldName == $parent || $fieldName == "{$parent}id" || $fieldName == "{$parent}instance" ||
                        ($fieldName == 'assignment' && substr($tableName, 0, 7) == 'assign_')) {
                    // Got one? Get the matching foreign key.
                    $indices = $table->getIndexes();
                    $indexName = false;
                    foreach ($indices as $index) {
                        $indexFields = $index->getFields();
                        if (count($indexFields) == 1 && $indexFields[0] == $fieldName) {
                            $indexName = $index->getName();
                        }
                    }

                    if (!$indexName) {
                        $indexName = "u_{$parent}";
                    }

                    try {
                        /* Before we try to add the index, look for records that will prevent it */
                        $conflicts = $DB->count_records_sql(
                                "SELECT COUNT('x') FROM {{$tableName}}
                                LEFT JOIN {{$parent}} ON {{$tableName}}.{$fieldName} = {{$parent}}.id
                                WHERE {{$parent}}.id IS NULL");
                        if ($conflicts) {
                            $total = $DB->count_records($tableName);
                            if ($total > 100 && ($conflicts / $total) < 0.05) {
                                echo "Deleting {$conflicts} of {$total} records from {$tableName} that don't match {$parent} ... ";
                                $DB->execute(
                                        "DELETE FROM {{$tableName}} WHERE NOT EXISTS (
                                    SELECT 1 FROM {{$parent}} WHERE {{$tableName}}.{$fieldName} = {{$parent}}.id)");
                            } else {
                                echo "{$conflicts}/{$total} records from {$tableName} don't match. Assuming this is not really a candidate for referential integrity).\n";
                                continue;
                            }
                        }
                        echo "Adding index to {$tableName} for field {$fieldName} ... ";
                        $DB->execute("ALTER TABLE {{$tableName}}
                                ADD CONSTRAINT c_{$indexName}
                                FOREIGN KEY ({$fieldName})
                                REFERENCES {{$parent}}(id)
                                ON DELETE CASCADE");
                        echo "success\n";
                    } catch (\dml_write_exception $e) {
                        // TODO: Double check that already exists.
                        if (substr($e->error, -14) == "already exists") {
                            echo "already exists\n";
                        } else {
                            echo "failed ({$e->error})\n";
                        }
                    } catch (\dml_read_exception $e) {
                        // Trying to match fields of different types?
                        if (substr($e->error, 0, 32) == "ERROR:  operator does not exist:") {
                            echo "different data types.\n";
                        } else if (substr($e->error, 0, 16) == "ERROR:  relation") {
                            echo "{$tableName} table missing?! Perhaps there's an upgrade to be done.";
                        } else {
                            echo "failed ({$e->error})\n";
                        }
                    }

                    self::add_cascade_deletion($schema, $tableName, $depth + 1);
                }
            }
        }
    }

    /**
     * Remove cascade deletion from courseIDs.
     */
    static public function remove_cascade_deletion($schema, $parent = 'course', $depth = 1) {
        global $DB;

        if ($depth > 8) {
            return;
        }

        $dbmanager = $DB->get_manager();

        // Iterate over tables in the schema ...
        foreach($schema->getTables() as $table) {
            $tableName = $table->getName();
            if ($tableName == $parent) {
                continue;
            }
            $fields = $table->getFields();
            // ... and over fields in the table ...
            foreach ($fields as $field) {
                $fieldName = $field->getName();
                // ... looking for a field of interest ...
                if ($fieldName == $parent || $fieldName == '{$parent}id' || $fieldName == "{$parent}instance") {
                    // Got one? Get the matching foreign key.
                    $indices = $table->getIndexes();
                    foreach ($indices as $index) {
                        $indexFields = $index->getFields();
                        if (count($indexFields) == 1 && $indexFields[0] == $fieldName) {
                            $indexName = $index->getName();
                            try {
                                $DB->execute("ALTER TABLE {{$tableName}}
                                          DROP CONSTRAINT {$indexName}");
                            } catch (\dml_write_exception $e) {
                                // TODO: Double check that didn't exist.
                            }
                            self::remove_cascade_deletion($schema, $tableName, $depth + 1);
                        }
                    }
                }
            }
        }

        // Remove index.
        try {
            $DB->execute("DROP INDEX {$parent}id ON {course}");
        } catch (\dml_write_exception $e) {
            // We don't mind if it didn't exist.
        }
    }

}

