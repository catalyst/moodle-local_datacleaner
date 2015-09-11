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
 * @package    cleaner_orphaned_sitedata
 * @author     Ghada El-Zoghbi <ghada@catalyst-au.net>
 * @copyright  2015 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_orphaned_sitedata;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/moodlelib.php');

class clean extends \local_datacleaner\clean {
    const TASK = 'Delete orphaned sitedata files';

    private static $backup_files = array();
    private static $orphaned_files = array();
    private static $database_files = array();
    private static $sitedata_files = array();

    static public function execute() {
        global $DB, $CFG;

        // Get the settings.
        $config = get_config('cleaner_orphaned_sitedata');

        // Retrieve what the user wants us to clean.
        $delete_backups = isset($config->deletebackups) && $config->deletebackups == 1 ? true : false;
        $delete_cached_files = isset($config->deletecachedfiles) && $config->deletecachedfiles == 1 ? true : false;
        $delete_tmp_files = isset($config->deletetmpfiles) && $config->deletetmpfiles == 1 ? true : false;
        $delete_orphaned_files = isset($config->deleteorphanedfiles) && $config->deleteorphanedfiles == 1 ? true : false;
        $delete_muc_file = isset($config->deletemucfile) && $config->deletemucfile == 1 ? true : false;


        // Set the default directories.
        $muc_directory = $CFG->dataroot . '/muc';
        $temp_directory = $CFG->tempdir;

        // Calculate how many backups will/would be deleted.
        $total_backups = 0;
        if ($delete_backups) {
            self::get_backups_files();
            $total_backups = count(self::$backup_files);
        }

        // Calculate how many orphaned files will be deleted.
        $total_orphaned = 0;
        if ($delete_orphaned_files) {
            // Read all the files in filedir and compare to DB.
            self::get_orphaned_files();
            $total_orphaned = count(self::$orphaned_files);
        }

        if (self::$dryrun) {

            if ($delete_backups) {
                printf("\n\r " . get_string('woulddeletebackups', 'cleaner_orphaned_sitedata', $total_backups) . "\n");
            }

            if ($delete_cached_files) {
                // Not getting a count for the number of cached files will be deleting.
                printf("\n\r " . get_string('wouldpurgecache', 'cleaner_orphaned_sitedata') . "\n");
            }

            if ($delete_tmp_files) {
                // Not getting a count for the number of temp files will be deleting.
                printf("\n\r " . get_string('woulddeletetemp', 'cleaner_orphaned_sitedata') . "\n");
            }

            if ($delete_muc_file) {
                // There's only one file here.
                printf("\n\r " . get_string('woulddeletemuc', 'cleaner_orphaned_sitedata') . "\n");
            }

            if ($delete_orphaned_files) {
                printf("\n\r " . get_string('woulddeleteorphanedfiles', 'cleaner_orphaned_sitedata', $total_orphaned) . "\n");
            }

        } else {

            if ($delete_backups) {
                // Notify the user how many files will be deleted.
                printf("\n\r " . get_string('willdeletebackups', 'cleaner_orphaned_sitedata', $total_backups) . "\n");
                if ($total_backups > 0) {
                    self::delete_backup_files();
                }
            }

            if ($delete_cached_files) {
                // Not getting a count for the number of cached files will be deleting.
                printf("\n\r " . get_string('willpurgecache', 'cleaner_orphaned_sitedata') . "\n");
                \cache_helper::purge_all(true);
                purge_all_caches();
            }

            if ($delete_tmp_files) {
                // Not getting a count for the number of temp files will be deleting.
                printf("\n\r " . get_string('willdeletetemp', 'cleaner_orphaned_sitedata') . "\n");
                // Delete the content of the temp directory.
                if (!remove_dir($temp_directory, true)) {
                    printf("\r " . get_string('errordeletingdir', 'cleaner_orphaned_sitedata', $temp_directory) . "\n");
                }
            }

            if ($delete_muc_file) {
                // TODO: need to clear out muc settings in the database as well.
                printf("\n\r " . get_string('willdeletemuc', 'cleaner_orphaned_sitedata') . "\n");
                if (!remove_dir($muc_directory, true)) {
                    printf("\r " . get_string('errordeletingdir', 'cleaner_orphaned_sitedata', $muc_directory) . "\n");
                }
            }

            if ($delete_orphaned_files) {
                printf("\n\r " . get_string('willdeleteorphanedfiles', 'cleaner_orphaned_sitedata', $total_orphaned) . "\n");
                if ($total_orphaned > 0) {
                    self::delete_sitedata_files(self::$orphaned_files);
                }
            }

        }

        printf("\n");

    }

    /**
     * The function file_storage::path_from_hash() is protected.
     * Need to create our own function to generate the path.
     *
     * @param string $contenthash the hash / filename of the file.
     * @return string path to the file.
     */
    private static function get_file_path_from_contenthash($contenthash) {
        global $CFG;

        $file_dir = $CFG->dataroot . '/filedir';

        $l1 = $contenthash[0].$contenthash[1];
        $l2 = $contenthash[2].$contenthash[3];

        return $file_dir . '/' . $l1 . '/' . $l2;
    }

    /**
     * Get the name of the directory location where backups are kept.
     *
     * @return string directory location.
     */
    private static function get_backup_location() {
        $config = get_config('backup');
        return $config->backup_auto_destination;
    }

    /**
     * Get a list of all the backups - if in a special backup directory
     * and / or all backups in the database.
     *
     * It sets the private class array: $backup_files.
     *                  keys: 'path' => the path of the file to delete excluding the file name.
     *                                  No trailing slash.
     *                        'file_name' => the name of the file to delete (without the path).
     *                                  No slash prefix.
     *                        'from_db' => 1 : this file comes from the DB and may not exist
     *                                         in site data.
     *                                     0 : this file is in site data.
     */
    private static function get_backups_files() {
        global $DB;

        $backup_directory = self::get_backup_location();
        if (!empty($backup_directory)) {
            self::$backup_files = self::get_sitedata_files($backup_directory);
        }

        // Now get a list of backups from the database.
        $sql = 'SELECT DISTINCT f.contenthash
                FROM {files} f
                INNER JOIN {context} c on f.contextid = c.id
                WHERE f.component = ?
                AND c.contextlevel = ?';

        $params = array('backup', CONTEXT_COURSE);
        $results = $DB->get_recordset_sql($sql, $params);

        foreach ($results as $file) {
            $file_name = $file->contenthash;
            if (!isset(self::$backup_files[$file_name])) {
                // Add it to the array if it's not already in there.
                // Generate what the path is supposed to be.
                $file_path = self::get_file_path_from_contenthash($file_name);
                self::$backup_files[$file_name] = array('file_name' => $file_name,
                                                        'path'      => $file_path,
                                                        'from_db'   => 1);
            }
        }
        //printf("backup files: " . print_r(self::$backup_files, true) . "\n");
    }

    /**
     * Delete backup files in sitedata as well as from the database.
     * All files should be populated in the class array: self::$backup_files.
     *
     */
    private static function delete_backup_files() {
        global $DB;

        // First, delete the files from sitedata.
        self::delete_sitedata_files(self::$backup_files);

        // Next, remove backups from the database.
        $sql = "DELETE FROM {files} f
                WHERE f.component = ?
                AND   f.contextid IN (SELECT id 
                                      FROM {context}
                                      WHERE contextlevel = ?)";
        $params = array('backup', CONTEXT_COURSE);
        $DB->execute($sql, $params);

    }

    /**
     * Recursively get a list of all the files in the $directory.
     *
     * @param string $directory - directory to recursively traverse.
     * @return array of files:
     *                  keys: 'path' => the path of the file to delete excluding the file name.
     *                                  No trailing slash.
     *                        'file_name' => the name of the file to delete (without the path).
     *                                  No slash prefix.
     *                        'from_db' => 1 : this file comes from the DB and may not exist
     *                                         in site data.
     *                                     0 : this file is in site data.
     */
    private static function get_sitedata_files($directory) {

        $files = array();
        try {
            $file_objs =  new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($directory),
                    \RecursiveIteratorIterator::CHILD_FIRST
                    );
        } catch (\Exception $e) {
            // This directory must not exist! Nothing left to do.
            return $files;
        }

        // Go through the file objects and save the file details
        // in an array.
        foreach( $file_objs as $file_obj) {
            try {
                if ($file_obj->isFile()) {
                    // We're only interested in files not directories.
                    $file_name = (string) $file_obj->getFilename();
                    if ($file_name == 'warning.txt') {
                        // Skip the Moodle warning file.
                        continue;
                    }
                    $files[$file_name] = array(
                                    'file_name' => $file_name,
                                    'path'      => (string) $file_obj->getPath(),
                                    'from_db'   => 0,
                                    );
                }
            } catch (\Exception $e) {
                // Something is wrong with this file / directory.
                // Skip it and continue.
                continue;
            }
        }

        return $files;
    }

    /**
     * Sets the list of files (self::$database_files) referenced in the database.
     *
     * It sets the private class array: $database_files.
     * Array of files:
     *              keys: 'path' => the path of the file to delete excluding the file name.
     *                              No trailing slash.
     *                    'file_name' => the name of the file referenced in the database (without the path).
     *                              No slash prefix.
     *                    'from_db' => 1 : this file comes from the DB and may not exist
     *                                     in site data.
     *                                 0 : this file is in site data.
     */
    private static function get_database_files() {
        global $DB;

        $sql = 'SELECT DISTINCT contenthash FROM {files}';
        $results = $DB->get_records_sql($sql);
        self::$database_files = array();
        foreach ($results as $file) {
            $file_name = $file->contenthash;
            $file_path = self::get_file_path_from_contenthash($file_name);
            self::$database_files[$file_name] = array(
                            'file_name' => $file_name,
                            'path'      => $file_path,
                            'from_db'   => 1,
                            );
        }

    }

    /**
     * Get a list of orpaned files by finding the difference of sitedata files
     * and database files.
     *
     * It sets the private class array: $orphaned_files.
     */
    private static function get_orphaned_files() {
        global $CFG;

        // Read all the files in /filedir.
        $file_dir = $CFG->dataroot . '/filedir';
        self::$sitedata_files = self::get_sitedata_files($file_dir);
        self::get_database_files();

        // Since backups may be in a different directory, merge sitedata and backup files.
        $all_files = array_merge(self::$sitedata_files, self::$backup_files);

        // Get the difference of the sitedata files and the database files.
        // These are the orphaned files which are no longer referenced
        // in the database.
        self::$orphaned_files = array_diff_key($all_files, self::$database_files);
        //printf("orphaned_files=" . print_r(self::$orphaned_files, true) . "\n");
    }

    /**
     * Delete all the files in the array passed into the function.
     *
     * @param array $files - list of files and their paths to delete. Pass by refernce so we're not
     *                       copying the array as it may be large.
     *                       keys: 'path' => the path of the file to delete excluding the file name.
     *                                       No trailing slash.
     *                             'file_name' => the name of the file to delete (without the path).
     *                                       No slash prefix.
     *                             'from_db' => 1 : this file comes from the DB and may not exist
     *                                              in site data.
     *                                          0 : this file is in site data.
     */
    private static function delete_sitedata_files($files) {

        foreach ($files as $file_item) {
            $file = $file_item['path'] . '/' . $file_item['file_name'];
            if (!@unlink($file)) {
                if (!isset($file_item['from_db']) || !$file_item['from_db']) {
                    // If it's originally from the database, no need to display a warning that the file
                    // was not found in site data.
                    printf("\r " . get_string('errordeletingfile', 'cleaner_orphaned_sitedata', $file) . "\n");
                }
            }
        }

    }
}
