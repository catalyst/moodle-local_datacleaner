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
        $correctcontextlevels = isset($config->contextlevels) && !empty($config->contextlevels);
        $contextlevels = !empty($correctcontextlevels) ? explode(',', $config->contextlevels) : array(CONTEXT_USER);

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

        $results = $DB->get_records_sql($sql, $params);
        $count = count($results);

        $filetypes = new cleaner_sitedata_supported_file_types();

        if (self::$options['dryrun']) {

            if ($count > 0) {
                foreach ($results as $result) {
                    $mimetype = $result->mimetype;
                    list($newmimetype, $placeholderfilename) = $filetypes->get_placeholder_file_name_for_type($mimetype);

                    $extensions = $filetypes->get_file_extension_for_type($mimetype);
                    $info = new \stdClass();
                    $info->total = $result->total;
                    $info->mimetype = $result->mimetype;
                    $info->newmimetype = $newmimetype;
                    $info->extensions = $extensions;
                    $info->placeholderfilename = $placeholderfilename;
                    if ($mimetype != $newmimetype) {
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
                // Instantiate once - i.e. not inside the foreach loop.
                $fs = get_file_storage();

                foreach ($results as $result) {
                    $mimetype = $result->mimetype;
                    list($newmimetype, $placeholderfilename) = $filetypes->get_placeholder_file_name_for_type($mimetype);
                    $extensions = $filetypes->get_file_extension_for_type($mimetype);

                    // Display the information we are about to replace
                    // before we copy any files - in case it throws
                    // an error so we know which file caused it.
                    $info = new \stdClass();
                    $info->total = $result->total;
                    $info->mimetype = $result->mimetype;
                    $info->newmimetype = $newmimetype;
                    $info->placeholderfilename = $placeholderfilename;
                    $info->extensions = $extensions;
                    if ($mimetype != $newmimetype) {
                        printf("\n\n\r " . get_string('willreplaceunknowntype', 'cleaner_sitedata', $info) . "\n");
                    } else {
                        printf("\n\n\r " . get_string('willreplace', 'cleaner_sitedata', $info) . "\n");
                    }

                    // Copy the placeholder file to sitedata.
                    $source = $CFG->dirroot . '/local/datacleaner/cleaner/sitedata/fixtures/' . $placeholderfilename;

                    try {
                        // Copy the file to sitedata.
                        list($contenthash, $filesize, $newfile) = $fs->add_file_to_pool($source);
                    } catch (\Exception $e) {
                        printf("\r " . get_string('filecopyerror', 'cleaner_sitedata') . "\n");
                        self::next_step();
                        continue;
                    }

                    // Reset all the status to 0 -> OK.
                    $status = 0;

                    // Filename and pathnamehash are staying the same
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
                    $params[] = $newmimetype;
                    $params[] = $status;
                    $params[] = $mimetype;
                    $DB->execute($sql, $params);
                    self::next_step();
                }

            } else {
                printf("\n\r " . get_string('nothingtoupdate', 'cleaner_sitedata') . "\n");
            }
        }

        printf("\n");

    }

}
