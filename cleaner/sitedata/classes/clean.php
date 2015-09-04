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
 * @package    cleaner_sitedata
 * @copyright  2015 Ghada El-Zoghbi <ghada@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_sitedata;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->dirroot . '/local/datacleaner/cleaner/sitedata/classes/supported_file_types.php');

class clean extends \local_datacleaner\clean {
    const TASK = 'Replace sitedata files';

    static public function execute() {
        global $DB, $CFG;

        // Update the database record with the placeholder file details.

        // Get the settings.
        $config = get_config('cleaner_sitedata');

        $allfiletypes = isset($config->allfiletypes) && $config->allfiletypes == 1 ? true : false;
        $filetypes = isset($config->filetypes) && !empty($config->filetypes) ? explode(',', $config->filetypes) : array();

        // If no contextlevels selected, default to USER.
        $allcontextlevels = isset($config->allcontextlevels) && $config->allcontextlevels == 1 ? true : false;
        $contextlevels = isset($config->contextlevels) && !empty($config->contextlevels) ? explode(',', $config->contextlevels) : array(CONTEXT_USER);

        $sql = "SELECT f.mimetype, count(1) As total
                FROM {files} f
                INNER JOIN {context} c on f.contextid = c.id";

        $wherearray = array();
        $params = array();
        if ($allcontextlevels == false) { 
            if (empty($contextlevels)) {
                // They don't want to delete anything. This shouldn't happen as we default to USER.
                // This is here just in case the logic above changes.
                printf("\n\r " . get_string('checkcontextsettings', 'cleaner_sitedata') . "\n");
                $wherearray[] = '1 = 2';
            } else {
                // Replace specific context levels.
                $wherearray[] = "c.contextlevel IN (" . implode(',', array_fill(0, count($contextlevels), '?')) . ")";
                $params = array_merge($params, $contextlevels);
            }
        }

        if ($allfiletypes == false) {
            if (empty($filetypes)) {
                // No file types selected!
                printf("\n\r " . get_string('checkfiletypesettings', 'cleaner_sitedata') . "\n");
                $wherearray[] = '1 = 2';
            } else {
                // Replace specific file types.
                $wherearray[] = "f.mimetype IN (" . implode(',', array_fill(0, count($filetypes), '?')) . ")";
                $params = array_merge($params , $filetypes);
            }
        }

        if (!empty($wherearray)) {
            $sql .= " WHERE " . implode(' AND ', $wherearray);
        }

        $sql .= " GROUP BY f.mimetype
                  ORDER BY f.mimetype";

        //echo(print_r($sql, true) . "\n");
        //echo("Params: " . print_r($params, true) . "\n");

        $results = $DB->get_records_sql($sql, $params);
        $count = count($results);

        $file_types = new cleaner_sitedata_supported_file_types();

        if (self::$dryrun) {

            if ($count > 0) {
                foreach ($results as $result) {
                    $mimetype = $result->mimetype;
                    list($new_mimetype, $placeholder_filename) = $file_types->get_placeholder_file_name_for_type($mimetype);

                    $extensions = $file_types->get_file_extension_for_type($mimetype);
                    $info = new \stdClass();
                    $info->total = $result->total;
                    $info->mimetype = $result->mimetype;
                    $info->newmimetype = $new_mimetype;
                    $info->extensions = $extensions;
                    $info->placeholderfilename = $placeholder_filename;
                    if ($mimetype != $new_mimetype) {
                        printf("\n\r " . get_string('wouldreplaceunknowntype', 'cleaner_sitedata', $info) . "\n");
                    } else {
                        printf("\n\r " . get_string('wouldreplace', 'cleaner_sitedata', $info) . "\n");
                    }
                }
            } else {
                printf("\n\r " . get_string('nothingtoupdate', 'cleaner_sitedata') . "\n");
            }

        } else {

            if ($count > 0) {

                self::new_task($count);

                foreach ($results as $result) {
                    $mimetype = $result->mimetype;
                    list($new_mimetype, $placeholder_filename) = $file_types->get_placeholder_file_name_for_type($mimetype);
                    $extensions = $file_types->get_file_extension_for_type($mimetype);

                    // Display the information we are about to replace
                    // before we copy any files - in case it throws
                    // an error so we know which file caused it.
                    $info = new \stdClass();
                    $info->total = $result->total;
                    $info->mimetype = $result->mimetype;
                    $info->newmimetype = $new_mimetype;
                    $info->placeholderfilename = $placeholder_filename;
                    $info->extensions = $extensions;
                    if ($mimetype != $new_mimetype) {
                        printf("\n\n\r " . get_string('willreplaceunknowntype', 'cleaner_sitedata', $info) . "\n");
                    } else {
                        printf("\n\n\r " . get_string('willreplace', 'cleaner_sitedata', $info) . "\n");
                    }

                    // Copy the placeholder file to sitedata.
                    $source = $CFG->dirroot . '/local/datacleaner/cleaner/sitedata/fixtures/' . $placeholder_filename;

                    try {
                        $fs = get_file_storage();
                        // copy the file to sitedata.
                        list($contenthash, $filesize, $newfile) = $fs->add_file_to_pool($source);
                    } catch (\Exception $e) {
                        printf("\r " . get_string('filecopyerror', 'cleaner_sitedata') . "\n");
                        self::next_step();
                        continue;
                    }

                    // reset all the status to 0 -> OK.
                    $status = 0;

                    // filename and pathnamehash are staying the same
                    // to ensure the pathnamehash stays unique.
                    $sql = "UPDATE {files}
                            SET contenthash = ?,
                                filesize = ?,
                                mimetype = ?,
                                status = ?,
                                referencefileid = null,
                                author = null,
                                source = null
                            WHERE mimetype = ?";
                    $params = array();
                    $params[] = $contenthash;
                    $params[] = $filesize;
                    $params[] = $new_mimetype;
                    $params[] = $status;
                    $params[] = $mimetype;
                    $DB->execute($sql, $params);
                    self::next_step();
                }

            } else {
                printf("\n\r " . get_string('nothingtoupdate', 'cleaner_sitedata') . "\n");
            }
        }
    }

}
