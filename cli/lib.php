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
 * @package    cleaner
 * @copyright  2015 Catalyst IT
 * @author     Nigel Cunningham <nigelc@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Print a message to the terminal.
 *
 * @param string $text      The text to print.
 * @param bool   $highlight Whether to add highlighting.
 */
function print_message($text, $highlight = false) {
    $highlightstart = "\033[1m";
    $highlightend = "\033[0m";

    if ($highlight) {
        echo "{$highlightstart}{$text}{$highlightend}\n";
    } else {
        echo $text;
    }
}

/**
 * Print a message about aborting.
 *
 * @param string $text      The text to print.
 * @param bool   $highlight Whether to highlight the text.
 */
function abort_message($text, $highlight = false) {
    static $haverun = false;

    if (!$haverun) {
        print_message("Aborting for the following reason(s):\n");
        $haverun = true;
    }

    print_message($text, $highlight);
}

/**
 * Safety checks.
 *
 * Make sure it's safe for us to continue. Don't wash prod!
 */
function safety_checks() {
    global $CFG, $DB;

    $willdie = false;

    // 1. Is $CFG->wwwroot the same as it was when this module was installed.
    $saved = $CFG->original_wwwroot;

    if (empty($saved)) {
        print_message("No wwwroot has been saved yet. Assuming we're in dev and it's safe to continue.", true);
    } else if ($CFG->wwwroot == $saved) {
        abort_message("\$CFG->wwwroot is '{$CFG->wwwroot}'. This is what I have saved as the production URL. Aborting.", true);
        $willdie = true;
    }

    // 2. Non admins logged in recently? Same logic as online users block.
    $timetoshowusers = 300; // Seconds default.
    $minutes = $timetoshowusers / 60;
    $now = time();
    $timefrom = $now - $timetoshowusers; // Unlike original code, don't care about caches for this.
    $params = array('now' => $now, 'timefrom' => $timefrom);

    $csql = "SELECT COUNT(u.id)
               FROM {user} u
              WHERE u.lastaccess > :timefrom
                AND u.lastaccess <= :now
                AND u.deleted = 0";

    if ($usercount = $DB->count_records_sql($csql, $params)) {
        $namefields = "u." . implode(', u.', get_all_user_name_fields());

        $sql = "SELECT u.id, u.username, {$namefields}
                  FROM {user} u
                 WHERE u.lastaccess > :timefrom
                   AND u.lastaccess <= :now
                   AND u.deleted = 0
              GROUP BY u.id
              ORDER BY lastaccess DESC ";
        $users = $DB->get_records_sql($sql, $params);

        $message = "The following users have logged in within the last {$minutes} minutes:\n";
        $nonadmins = 0;
        foreach ($users as $user) {
            $message .= ' - ' . fullname($user) . ' (' . $user->username . ')';
            if (is_siteadmin($user)) {
                $message .= ' (siteadmin)';
            } else {
                $nonadmins++;
            }
            $message .= "\n";
        }

        if ($nonadmins) {
            abort_message($message);
            abort_message("Aborting because there are non site-administrators in the list of recent users.", true);
            $willdie = true;
        }
    }

    // 3. Has cron run recently?
    $lastrun = -1;
    if ($CFG->version >= 2014051207) {
        $lastrun = $DB->get_field_sql("SELECT MAX(lastruntime) FROM {task_scheduled}");
    }

    if ($lastrun > $timefrom) {
        abort_message("Aborting because cron has run within the last {$minutes} minutes.", true);
        $willdie = true;
    }

    if ($willdie) {
        exit(1);
    }
}

