<?php

namespace local_datacleaner\task;

defined('MOODLE_INTERNAL') || die();

use local_datacleaner\clean;
use local_datacleaner\plugininfo\cleaner;

class postwash extends \core\task\scheduled_task {
    public function get_name() {
        return "Postwash task for local_datacleaner";
    }

    public function execute() {
        global $CFG;

        require_once($CFG->dirroot . '/local/datacleaner/cli/lib.php');

        // Throw instead of exit()ing so an unsafe environment just fails this task,
        // rather than killing the whole cron run.
        safety_checks(false, true);

        clean::debug_info();
        mtrace('Running postwash task...');
        $plugins = cleaner::get_enabled_plugins_by_sortorder();
        clean::run_wash($plugins, ['run-post-wash' => true, 'verbose' => true]);
        mtrace('Postwash task completed.');
    }
}