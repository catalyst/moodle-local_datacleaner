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
 * @copyright  2015 Catalyst IT
 * @author     Nigel Cunningham <nigelc@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_datacleaner;

use core\base;

defined('MOODLE_INTERNAL') || die();

class schema_add_cascade_delete extends clean {
    protected static $constraintremovalqueries = array();
    protected static $unrelated = array();
    protected static $depth = 0;

    protected static $numindices = 0;
    protected static $numcascadedeletes = 0;

    /**
     * Based on get_install_xml_schema in lib/ddl/database_manager.php.
     *
     * Reads the install.xml files for Moodle core and modules and returns an array of
     * xmldb_structure object with xmldb_table from these files.
     * @return xmldb_structure schema from install.xml files
     */
    static private function get_xml_schema() {
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
            $xmldbfile = new \xmldb_file($dbdir.'/install.xml');
            if (!$xmldbfile->fileExists() or !$xmldbfile->loadXMLStructure()) {
                continue;
            }
            $structure = $xmldbfile->getStructure();
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
    static private function add_constraint_removal_query($query) {
        if (empty(self::$constraintremovalqueries)) {
            register_shutdown_function(array('local_datacleaner\schema_add_cascade_delete', 'revert'));
        }
        self::$constraintremovalqueries[] = $query;
    }

    /**
     * Get additional field names to try for a parent table.
     *
     * @param string $parent The parent table name
     * @return array The list of base field names to consider
     */
    static private function get_checks_for_parent_table($parent) {
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
                $checks[] = 'appraiser';
                $checks[] = 'manager';
                $checks[] = 'reportsto';
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
    static private function will_use_table($checks, $fieldname) {
        foreach ($checks as $test) {
            if ($fieldname == $test || $fieldname == "{$test}id" || $fieldname == "{$test}instance" ||
                    $fieldname == "{$test}_id") {
                return true;
            }
        }

        return false;
    }

    /**
     * Try to add a cascade delete.
     *
     * @param string $parent    The parent (one) table in the relationship.
     * @param string $tablename The child (many) table in the relationship.
     * @param string $fieldname The child field that may contain the parent id.
     * @param string $indexname The indexname upon which to base the constraint name.
     *
     * @return bool Whether a relationship was added.
     */
    static private function try_add_cascade_delete($parent, $tablename, $fieldname, $indexname) {
        global $DB;

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
                        self::debug("{$conflicts}/{$total} records from the {$fieldname} field in {$tablename} don't match " .
                                "{$parent} ids. Assuming this is not really a candidate for referential integrity.\n");
                    }
                    return false;
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
            return true;
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
        return false;
    }

    /**
     * Add cascade deletion to courseIDs.
     *
     * @param string $param The parent table for which we're seeking children.
     * @param array $schema The database schema
     */
    static public function execute($parent = 'user', $schema = null) {
        static $visited = array();
        global $DB;

        self::$depth++;

        if (is_null($schema)) {
            $schema = self::get_xml_schema();
            foreach ($schema->getTables() as $table) {
                if ($table == $parent) {
                    continue;
                }
                self::$unrelated[$table->getName()] = 1;
            }
        }

        if (isset($visited[$parent])) {
            self::$depth--;
            return;
        }

        $visited[$parent] = true;

        if (self::$options['dryrun']) {
            self::$numindices++;
        } else {
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
        $to_recurse_into = array();

        // Iterate over tables in the schema ...
        foreach ($schema->getTables() as $table) {
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

                if ($willuse) {
                    self::debug(($willuse ? 'X ' : '  ') . " {$parent}: {$fieldname} in {$tablename}\n");

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

                    if (self::$options['dryrun']) {
                        self::$numcascadedeletes++;
                    } else {
                        if (!self::try_add_cascade_delete($parent, $tablename, $fieldname, $indexname)) {
                            continue;
                        }
                    }

                    $to_recurse_into[] = $tablename;
                }
            }
        }

        foreach($to_recurse_into as $tablename) {
            self::execute($tablename, $schema);
        }

        self::$depth--;

        if (!self::$depth) {
            if (!empty(self::$unrelated) && self::$options['verbose']) {
                $toprint = array_keys(self::$unrelated);
                sort($toprint);
                foreach ($toprint as $table) {
                    echo "- {$table}\n";
                }
            }

            if (self::$options['dryrun'] && (self::$numindices || self::$numcascadedeletes)) {
                echo "Would attempt to add " . self::$numindices . " indices and " . self::$numcascadedeletes .
                    " cascade deletes flowing from table '{$parent}'.\n";
            }
        }
    }

    /**
     * Remove cascade deletion from courseIDs.
     */
    static public function revert() {
        global $DB;

        self::debug("Removing cascade deletions and indices that were added.\n");

        foreach (array_reverse(self::$constraintremovalqueries) as $query) {
            $DB->execute($query);
        }

        self::$constraintremovalqueries = array();
    }
}
