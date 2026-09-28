<?php
/**
 * One-off CLI maintenance script.
 *
 * Recursively find/replaces a search string inside EVERY block_instances.configdata
 * record, regardless of block type. Unlike a raw SQL REPLACE(), this safely
 * unserializes the PHP data structure, walks it recursively replacing any string
 * value found (including inside nested objects/arrays), then re-serializes and re-encodes it for the db.
 *
 * USAGE (run from your Moodle/Totara webroot, i.e. same directory as config.php):
 *
 *   php fix_block_configdata_replace.php --search="restlearn.resthaven.asn.au" \
 *       --replace="s-resthaven.catalyst-au.net"
 *
 * By default this runs in DRY-RUN mode and only reports what WOULD change.
 * Add --execute to actually write the changes to the database.
 *
 * Always take a full database backup before running with --execute.
 *
 * Optional:
 *   --blockname=html    Restrict to a single block type (blockname column value).
 *   --verbose           Print each matched instance id/block/before-after snippet.
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/config.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecognized) = cli_get_params(
    [
        'search'    => false,
        'replace'   => false,
        'blockname' => '',
        'execute'   => false,
        'verbose'   => false,
        'help'      => false,
    ],
    [
        'h' => 'help',
    ]
);

if ($options['help'] || empty($options['search']) || $options['replace'] === false) {
    echo "Recursively replace a string inside all block_instances.configdata.\n\n";
    echo "Options:\n";
    echo "  --search=STRING      Text to find (required)\n";
    echo "  --replace=STRING     Text to replace it with (required, can be empty string)\n";
    echo "  --blockname=NAME     Only process this block type, e.g. html (optional)\n";
    echo "  --execute            Actually write changes (default is dry-run)\n";
    echo "  --verbose            Show per-record details\n";
    echo "  -h, --help           This help\n";
    exit(0);
}

$search    = $options['search'];
$replace   = $options['replace'];
$blockname = $options['blockname'];
$execute   = (bool)$options['execute'];
$verbose   = (bool)$options['verbose'];

/**
 * Recursively replace $search with $replace in every string found within
 * $data, which may be a scalar, array, or object (stdClass or any class).
 * Returns [ $newdata, $changed (bool) ].
 */
function recursive_replace($data, $search, $replace, &$changed) {
    if (is_string($data)) {
        if (strpos($data, $search) !== false) {
            $changed = true;
            return str_replace($search, $replace, $data);
        }
        return $data;
    }

    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = recursive_replace($value, $search, $replace, $changed);
        }
        return $data;
    }

    if (is_object($data)) {
        foreach ($data as $key => $value) {
            $data->{$key} = recursive_replace($value, $search, $replace, $changed);
        }
        return $data;
    }

    // Ints, floats, bools, nulls - nothing to do.
    return $data;
}

$params = [];
$where = '1=1';
if (!empty($blockname)) {
    $where .= ' AND blockname = :blockname';
    $params['blockname'] = $blockname;
}

$rs = $DB->get_recordset_select('block_instances', $where, $params);

$total = 0;
$matched = 0;
$failed = 0;

foreach ($rs as $instance) {
    $total++;

    if (empty($instance->configdata)) {
        continue;
    }

    $decoded = base64_decode($instance->configdata, true);
    if ($decoded === false) {
        continue;
    }

    // Some configdata may not be serialized data at all - skip anything that
    // doesn't unserialize cleanly rather than risk corrupting it.
    $config = @unserialize($decoded);
    if ($config === false && $decoded !== serialize(false)) {
        continue;
    }

    $changed = false;
    $newconfig = recursive_replace($config, $search, $replace, $changed);

    if (!$changed) {
        continue;
    }

    $matched++;

    $newserialized = serialize($newconfig);
    $newencoded = base64_encode($newserialized);

    // Sanity check: make sure what we just built unserializes back cleanly.
    $roundtrip = @unserialize($newserialized);
    if ($roundtrip === false && $newserialized !== serialize(false)) {
        $failed++;
        mtrace("SKIPPED (failed round-trip check) - block instance id {$instance->id} ({$instance->blockname})");
        continue;
    }

    if ($verbose) {
        mtrace("Block instance id {$instance->id} ({$instance->blockname}) - match found.");
    }

    if ($execute) {
        $DB->set_field('block_instances', 'configdata', $newencoded, ['id' => $instance->id]);
        $DB->set_field('block_instances', 'timemodified', time(), ['id' => $instance->id]);
    }
}

$rs->close();

mtrace('');
mtrace("Scanned: $total block instance(s)");
mtrace("Matched: $matched block instance(s) contained '$search'");
if ($failed > 0) {
    mtrace("Skipped (round-trip failure): $failed - these were NOT changed, investigate manually.");
}

if (!$execute) {
    mtrace('');
    mtrace('DRY RUN ONLY - no changes were written. Re-run with --execute to apply.');
} else {
    mtrace('');
    mtrace('Changes written. Consider running: php admin/cli/purge_caches.php');
}
