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
 * Library functions for data cleaner.
 *
 * @package    local_datacleaner
 * @copyright  2015 Nigel Cunningham <nigelc@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_datacleaner\clean;

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
    clean::log($text);
}

/**
 * Print a message about aborting.
 *
 * @param string $prefix    The prefix to the reason header.
 * @param string $text      The text to print.
 * @param bool   $highlight Whether to highlight the text.
 */
function abort_message($prefix, $text, $highlight = false) {
    static $haverun = false;

    if (!$haverun) {
        print_message("{$prefix} for the following reason(s):\n");
        $haverun = true;
    }

    print_message($text, $highlight);
}

/**
 * Safety checks.
 *
 * Make sure it's safe for us to continue. Don't wash prod!
 *
 * @param bool $dryrun If true, just reports potential issues without aborting.
 * @param bool $isscheduledtask If true, throws an exception instead of exiting to properly fail the scheduled task.
 *
 * @return bool True if it is unsafe to proceed.
 */
function safety_checks($dryrun, $isscheduledtask = false) {
    global $CFG, $DB;

    $willdie = false;
    $abort = $dryrun ? 'Would abort' : 'Aborting';

    // 1. Is $CFG->wwwroot the same as it was when this module was installed.
    $saved = isset($CFG->original_wwwroot) ? $CFG->original_wwwroot : false;

    if (empty($saved)) {
        abort_message(
            $abort,
            "No \$CFG->original_wwwroot has been saved yet. This needs to be set explicitly to run\n" .
            "Visit local/datacleaner/ on the production site to auto set this up.",
            true
        );
        $willdie = true;
    } else if ($CFG->wwwroot == base64_decode($saved)) {
        abort_message($abort, "\$CFG->wwwroot is '{$CFG->wwwroot}'. This is what I have saved as the production URL.", true);
        $willdie = true;
    }

    // 2. Check for an explicit flag in config.php just to be extra mega cautious!
    if (empty($CFG->config_php_settings['local_datacleaner_allowexecution'])) {
        abort_message($abort, get_string('error:explicitconfigphp', 'local_datacleaner'), true);
        $willdie = true;
    }

    if ($willdie && !$dryrun) {
        if ($isscheduledtask) {
            throw new \Exception(get_string('scheduledtaskpostwasherror', 'local_datacleaner'));
        }
        exit(1);
    }

    return $willdie;    /* For dryrun */
}
