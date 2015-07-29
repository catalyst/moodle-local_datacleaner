<?php

namespace cleaner_logstore_standard;

defined('MOODLE_INTERNAL') || die();

class clean extends \local_datacleaner\clean {
    static public function execute() {
        self::$status = 'Done';
        self::$percent = 100;
    }
}

