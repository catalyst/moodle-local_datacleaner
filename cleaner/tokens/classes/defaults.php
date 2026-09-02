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

namespace cleaner_tokens;

/**
 * Default values for cleaner_tokens config settings.
 *
 * @package    cleaner_tokens
 * @copyright  2026 Catalyst IT
 * @author     Jason den Dulk <jasondendulk@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class defaults {
    /** @var string[] Default tables to truncate. */
    public const DEFAULT_TABLES_TO_TRUNCATE = [
        'user_private_key',
        'user_password_resets',
        'registration_hubs',
        'oauth2_issuer',
        'oauth2_system_account',
        'oauth2_access_token',
        'oauth2_refresh_token',
    ];

    /** @var string[] Default fields to rehash. */
    public const DEFAULT_FIELDS_TO_REHASH = [
        'external_tokens:token:32',
    ];

    /** @var string[] Default fields to regenerate. */
    public const DEFAULT_FIELDS_TO_REGENERATE = [
        'external_tokens:privatetoken:64',
    ];

    /** @var int Default seed for rehashing. */
    public const DEFAULT_REHASH_SEED = 64;
}
