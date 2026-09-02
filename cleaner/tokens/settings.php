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
 * Settings for the task logs cleaner.
 *
 * @package    cleaner_tokens
 * @copyright  2026 Catalyst IT
 * @author     Jason den Dulk <jasondendulk@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use cleaner_tokens\defaults;

if (!$ADMIN->fulltree) {
    return;
}

// Tables to be truncated.
$settings->add(
    new admin_setting_configtextarea(
        'cleaner_tokens/tablestotruncate',
        new lang_string('tablestotruncate', 'cleaner_tokens'),
        new lang_string('tablestotruncatedesc', 'cleaner_tokens'),
        implode("\r\n", defaults::DEFAULT_TABLES_TO_TRUNCATE),
        PARAM_RAW
    )
);

// Fields to be rehashed.
$settings->add(
    new admin_setting_configtextarea(
        'cleaner_tokens/fieldstorehash',
        new lang_string('fieldstorehash', 'cleaner_tokens'),
        new lang_string('fieldstorehashdesc', 'cleaner_tokens'),
        implode("\r\n", defaults::DEFAULT_FIELDS_TO_REHASH),
        PARAM_RAW
    )
);

// Seed to use for rehashing.
$settings->add(
    new admin_setting_configtext(
        'cleaner_tokens/rehashseed',
        new lang_string('rehashseed', 'cleaner_tokens'),
        new lang_string('rehashseeddesc', 'cleaner_tokens'),
        defaults::DEFAULT_REHASH_SEED,
        PARAM_INT
    )
);

// Fields to be regenerated.
$settings->add(
    new admin_setting_configtextarea(
        'cleaner_tokens/fieldstoregenerate',
        new lang_string('fieldstoregenerate', 'cleaner_tokens'),
        new lang_string('fieldstoregeneratedesc', 'cleaner_tokens'),
        implode("\r\n", defaults::DEFAULT_FIELDS_TO_REGENERATE),
        PARAM_RAW
    )
);
