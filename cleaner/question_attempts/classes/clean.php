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

namespace cleaner_question_attempts;

/**
 * Data cleaner class for deleting old question attempts.
 *
 * @package    cleaner_question_attempts
 * @copyright  2026 Catalyst IT Canada
 * @author     Artem Garanin <artemgaranin@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clean extends \local_datacleaner\clean {
    /**
     * Task name for progress display.
     *
     * @var string
     */
    const TASK = 'Removing question attempts';

    /** @var int Chunk size for batch deletion. */
    const CHUNK_SIZE = 10000;

    /**
     * Execute the cleaning process.
     */
    public static function execute() {
        global $DB;

        $config = get_config('cleaner_question_attempts');
        $minimumage = isset($config->minimumage) ? (int)$config->minimumage : 30;

        // Fast path: delete everything when minimumage is 0.
        if ($minimumage === 0) {
            if (self::$options['dryrun']) {
                $count = $DB->count_records('question_usages');
                echo "Would delete all {$count} question usages and related data.\n";
                return;
            }
            self::delete_all();
            return;
        }

        $cutoff = time() - ($minimumage * DAYSECS);
        $count = self::get_old_usage_count($cutoff);

        if ($count == 0) {
            echo "No question attempt data to delete.\n";
            return;
        }

        if (self::$options['dryrun']) {
            echo "Would delete {$count} question usages and related data.\n";
            return;
        }

        self::new_task((int)ceil($count / self::CHUNK_SIZE));

        $lastid = 0;
        $deleted = 0;
        while (true) {
            $ids = self::get_old_usage_ids_chunk($cutoff, $lastid, self::CHUNK_SIZE);
            if (empty($ids)) {
                break;
            }

            $qubaids = new \qubaid_list($ids);
            try {
                \question_engine::delete_questions_usage_by_activities($qubaids);
            } catch (\Exception $e) {
                self::debug("File cleanup error: " . $e->getMessage());
            }

            $deleted += count($ids);
            $lastid = max($ids);
            self::next_step();
            self::debugmemory();
        }

        echo "Deleted {$deleted} question usages and related data.\n";
    }

    /**
     * Fast path: delete all question attempt data by truncating tables.
     */
    protected static function delete_all() {
        global $DB;

        echo "Deleting all question attempt data...\n";

        // Order matters: child tables first.
        $DB->delete_records('question_attempt_step_data');
        $DB->delete_records('question_attempt_steps');
        $DB->delete_records('question_attempts');
        $DB->delete_records('question_usages');

        // Clean orphaned response file metadata only.
        // IMPORTANT: do NOT delete all component=question — that includes question
        // definition files (questiontext, answer, feedback images etc.)
        $DB->delete_records_select(
            'files',
            "component = 'question' AND filearea LIKE 'response_%'"
        );

        echo "Deleted all question usages and related data.\n";
    }

    /**
     * Count question usages where all attempt steps are older than the cutoff.
     *
     * @param int $cutoff Unix timestamp cutoff.
     * @return int Number of old usages.
     */
    protected static function get_old_usage_count(int $cutoff): int {
        global $DB;

        $sql = "SELECT COUNT(DISTINCT qu.id)
                  FROM {question_usages} qu
                  JOIN {question_attempts} qa ON qa.questionusageid = qu.id
                 WHERE NOT EXISTS (
                           SELECT 1 FROM {question_attempt_steps} qas
                            WHERE qas.questionattemptid = qa.id
                              AND qas.timecreated >= :cutoff
                       )";

        return (int)$DB->count_records_sql($sql, ['cutoff' => $cutoff]);
    }

    /**
     * Get a chunk of old usage IDs using keyset pagination.
     *
     * @param int $cutoff Unix timestamp cutoff.
     * @param int $lastid Last processed ID for keyset pagination.
     * @param int $limit Maximum number of IDs to return.
     * @return int[] Array of question usage IDs.
     */
    protected static function get_old_usage_ids_chunk(int $cutoff, int $lastid, int $limit): array {
        global $DB;

        $sql = "SELECT DISTINCT qu.id
                  FROM {question_usages} qu
                  JOIN {question_attempts} qa ON qa.questionusageid = qu.id
                 WHERE qu.id > :lastid
                   AND NOT EXISTS (
                           SELECT 1 FROM {question_attempt_steps} qas
                            WHERE qas.questionattemptid = qa.id
                              AND qas.timecreated >= :cutoff
                       )
              ORDER BY qu.id ASC";

        $records = $DB->get_records_sql($sql, ['lastid' => $lastid, 'cutoff' => $cutoff], 0, $limit);
        return array_keys($records);
    }
}
