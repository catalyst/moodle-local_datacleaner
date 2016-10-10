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

    private static $backupfiles = array();
    private static $orphanedfiles = array();
    private static $databasefiles = array();
    private static $sitedatafiles = array();

    static public function execute() {
        global $CFG;

        // Get the settings.
        $config = get_config('cleaner_orphaned_sitedata');

        // Retrieve what the user wants us to clean.
        $deletebackups = isset($config->deletebackups) && $config->deletebackups == 1 ? true : false;
        $deletecachedfiles = isset($config->deletecachedfiles) && $config->deletecachedfiles == 1 ? true : false;
        $deletetmpfiles = isset($config->deletetmpfiles) && $config->deletetmpfiles == 1 ? true : false;
        $deleteorphanedfiles = isset($config->deleteorphanedfiles) && $config->deleteorphanedfiles == 1 ? true : false;

        // Set the default directories.
        $tempdirectory = $CFG->tempdir;

        // Calculate how many backups will/would be deleted.
        $totalbackups = 0;
        if ($deletebackups) {
            self::get_backups_files();
            $totalbackups = count(self::$backupfiles);
        }

        // Calculate how many orphaned files will be deleted.
        $totalorphaned = 0;
        if ($deleteorphanedfiles) {
            // Read all the files in filedir and compare to DB.
            self::get_orphaned_files();
            $totalorphaned = count(self::$orphanedfiles);
        }

        if (self::$options['dryrun']) {

            if ($deletebackups) {
                printf("\n\r " . get_string('woulddeletebackups', 'cleaner_orphaned_sitedata', $totalbackups) . "\n");
            }

            if ($deletecachedfiles) {
                // Not getting a count for the number of cached files will be deleting.
                printf("\n\r " . get_string('wouldpurgecache', 'cleaner_orphaned_sitedata') . "\n");
            }

            if ($deletetmpfiles) {
                // Not getting a count for the number of temp files will be deleting.
                printf("\n\r " . get_string('woulddeletetemp', 'cleaner_orphaned_sitedata') . "\n");
            }

            if ($deleteorphanedfiles) {
                printf("\n\r " . get_string('woulddeleteorphanedfiles', 'cleaner_orphaned_sitedata', $totalorphaned) . "\n");
            }

        } else {

            if ($deletebackups) {
                // Notify the user how many files will be deleted.
                printf("\n\r " . get_string('willdeletebackups', 'cleaner_orphaned_sitedata', $totalbackups) . "\n");
                if ($totalbackups > 0) {
                    self::delete_backup_files();
                }
            }

            if ($deletecachedfiles) {
                // Not getting a count for the number of cached files will be deleting.
                printf("\n\r " . get_string('willpurgecache', 'cleaner_orphaned_sitedata') . "\n");
                \cache_helper::purge_all(true);
                purge_all_caches();
            }

            if ($deletetmpfiles) {
                // Not getting a count for the number of temp files will be deleting.
                printf("\n\r " . get_string('willdeletetemp', 'cleaner_orphaned_sitedata') . "\n");
                // Delete the content of the temp directory.
                if (!remove_dir($tempdirectory, true)) {
                    printf("\r " . get_string('errordeletingdir', 'local_datacleaner', $tempdirectory) . "\n");
                }
            }

            if ($deleteorphanedfiles) {
                printf("\n\r " . get_string('willdeleteorphanedfiles', 'cleaner_orphaned_sitedata', $totalorphaned) . "\n");
                if ($totalorphaned > 0) {
                    self::delete_sitedata_files(self::$orphanedfiles);
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

        $filedir = $CFG->dataroot . '/filedir';

        $dir1 = $contenthash[0].$contenthash[1];
        $dir2 = $contenthash[2].$contenthash[3];

        return $filedir . '/' . $dir1 . '/' . $dir2;
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

        $backupdirectory = self::get_backup_location();
        if (!empty($backupdirectory)) {
            self::$backupfiles = self::get_sitedata_files($backupdirectory);
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
            $filename = $file->contenthash;
            if (!isset(self::$backupfiles[$filename])) {
                // Add it to the array if it's not already in there.
                // Generate what the path is supposed to be.
                $filepath = self::get_file_path_from_contenthash($filename);
                self::$backupfiles[$filename] = array('file_name' => $filename,
                                                        'path'      => $filepath,
                                                        'from_db'   => 1);
            }
        }
    }

    /**
     * Delete backup files in sitedata as well as from the database.
     * All files should be populated in the class array: self::$backup_files.
     *
     */
    private static function delete_backup_files() {
        global $DB;

        // First, delete the files from sitedata.
        self::delete_sitedata_files(self::$backupfiles);

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
            $fileobjs = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($directory),
                    \RecursiveIteratorIterator::CHILD_FIRST
                    );
        } catch (\Exception $e) {
            // This directory must not exist! Nothing left to do.
            return $files;
        }

        // Go through the file objects and save the file details
        // in an array.
        foreach ($fileobjs as $fileobj) {
            try {
                if ($fileobj->isFile()) {
                    // We're only interested in files not directories.
                    $filename = (string) $fileobj->getFilename();
                    if ($filename == 'warning.txt') {
                        // Skip the Moodle warning file.
                        continue;
                    }
                    $files[$filename] = array(
                                    'file_name' => $filename,
                                    'path'      => (string) $fileobj->getPath(),
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
        self::$databasefiles = array();
        foreach ($results as $file) {
            $filename = $file->contenthash;
            $filepath = self::get_file_path_from_contenthash($filename);
            self::$databasefiles[$filename] = array(
                            'file_name' => $filename,
                            'path'      => $filepath,
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
        $filedir = $CFG->dataroot . '/filedir';
        self::$sitedatafiles = self::get_sitedata_files($filedir);
        self::get_database_files();

        // Since backups may be in a different directory, merge sitedata and backup files.
        $allfiles = array_merge(self::$sitedatafiles, self::$backupfiles);

        // Get the difference of the sitedata files and the database files.
        // These are the orphaned files which are no longer referenced
        // in the database.
        self::$orphanedfiles = array_diff_key($allfiles, self::$databasefiles);
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

        foreach ($files as $fileitem) {
            $file = $fileitem['path'] . '/' . $fileitem['file_name'];
            if (!@unlink($file)) {
                if (!isset($fileitem['from_db']) || !$fileitem['from_db']) {
                    // If it's originally from the database, no need to display a warning that the file
                    // was not found in site data.
                    printf("\r " . get_string('errordeletingfile', 'cleaner_orphaned_sitedata', $file) . "\n");
                }
            }
        }

    }
}
