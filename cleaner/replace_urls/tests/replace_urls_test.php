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

use cleaner_replace_urls\clean;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for replace_urls
 *
 * @package    cleaner_replace_urls
 * @copyright  2015 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(clean::class)]
final class replace_urls_test extends advanced_testcase {
    /** @var Course values */
    private $course;

    /**
     * Insert some config make sure they are gone
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);

        // Set config for original and new site.
        set_config('origsiteurl', 'local.origin', 'cleaner_replace_urls');
        set_config('enabled', 1, 'cleaner_replace_urls');
        set_config('cleantext', 1, 'cleaner_replace_urls');

        // Create a course to test.
        $coursearray = [
            'fullname' => get_config('cleaner_replace_urls', 'origsiteurl'),
        ];
        $this->course = $this->getDataGenerator()->create_course($coursearray);
    }

    /**
     * Teardown unit tests.
     */
    protected function tearDown(): void {
        $this->course = null;
        parent::tearDown();
    }

    /**
     * Regression test for old functions
     * @group test_replace_url
     */
    public function test_replace_url(): void {
        global $DB;

        $this->resetAfterTest(true);

        // Set the newsiteurl config.
        set_config('newsiteurl', 'new.origin', 'cleaner_replace_urls');

        $configcleaner = new clean();
        $configcleaner::execute();

        $namesafter = $DB->get_record_sql('SELECT fullname FROM {course} WHERE id=:name', ['name' => $this->course->id]);

        $this->assertEquals(get_config('cleaner_replace_urls', 'newsiteurl'), $namesafter->fullname);
    }

    /**
     * Test that wysiwyg fields (columns paired with a *format column) are replaced.
     *
     * Regression test for https://github.com/catalyst/moodle-local_datacleaner/issues/201
     * The bug was an inner foreach reusing the outer $column variable, causing the
     * wysiwyg scan to only run once (on the last outer iteration) and silently corrupt
     * earlier columns — so content-type fields were never cleaned.
     *
     * @group test_replace_wysiwyg
     */
    public function test_replace_wysiwyg(): void {
        global $DB;

        $this->resetAfterTest(true);

        // Use only wysiwyg mode (not cleantext) to isolate the wysiwyg code path.
        set_config('cleantext', 0, 'cleaner_replace_urls');
        set_config('cleanwysiwyg', 1, 'cleaner_replace_urls');
        set_config('newsiteurl', 'new.origin', 'cleaner_replace_urls');

        // Course_sections.summary / summaryformat is a classic wysiwyg pair.
        $section = $this->getDataGenerator()->create_course_section([
            'course'  => $this->course->id,
            'section' => 1,
        ]);
        $DB->set_field(
            'course_sections',
            'summary',
            'Visit http://local.origin/course/view.php?id=1',
            ['id' => $section->id]
        );

        $configcleaner = new clean();
        $configcleaner::execute();

        $after = $DB->get_field('course_sections', 'summary', ['id' => $section->id]);
        $this->assertStringContainsString('new.origin', $after);
        $this->assertStringNotContainsString('local.origin', $after);
    }

    /**
     * Test the replace without newsite
     * @group without
     */
    public function test_replace_url_with_wwwroot(): void {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        // Set the newsiteurl to null.
        set_config('newsiteurl', '', 'cleaner_replace_urls');

        $configcleaner = new clean();
        $configcleaner::execute();

        $namesafter = $DB->get_record_sql('SELECT fullname FROM {course} WHERE id=:name', ['name' => $this->course->id]);

        $this->assertEquals($CFG->wwwroot, $namesafter->fullname);
    }

    /**
     * Creates a block instance with the given raw configdata.
     *
     * @param string $blockname
     * @param string|null $configdata
     * @return int
     */
    private function create_block(string $blockname, ?string $configdata): int {
        global $DB;
        return $DB->insert_record('block_instances', (object)[
            'blockname' => $blockname,
            'parentcontextid' => context_course::instance($this->course->id)->id,
            'showinsubcontexts' => 0,
            'requiredbytheme' => 0,
            'pagetypepattern' => 'course-view-*',
            'defaultregion' => 'side-pre',
            'defaultweight' => 0,
            'configdata' => $configdata,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * Nothing is touched when cleanblocks is disabled.
     * @group test_replace_blocks
     */
    public function test_block_configdata_disabled(): void {
        global $DB;

        set_config('newsiteurl', 'new.origin', 'cleaner_replace_urls');
        // Switch the block cleaning off.
        set_config('cleanblocks', 0, 'cleaner_replace_urls');
        // A block that does have the old address in it.
        $data = base64_encode(serialize((object)['link' => 'local.origin']));
        $id = $this->create_block('calendar_month', $data);

        $cleaner = new clean(['dryrun' => false, 'verbose' => false]);
        $cleaner::execute();

        // Cleaning was off, so the block is exactly as it was.
        $this->assertSame($data, $DB->get_field('block_instances', 'configdata', ['id' => $id]));
    }

    /**
     * Data that can't be safely rewritten is skipped untouched.
     * @group test_replace_blocks
     */
    public function test_block_configdata_skipped(): void {
        global $DB;

        set_config('newsiteurl', 'new.origin.longer', 'cleaner_replace_urls');
        set_config('cleanblocks', 1, 'cleaner_replace_urls');

        // Blocks the cleaner should be too careful to touch.
        $untouched = [
            // Not a valid base64 envelope at all.
            'badbase64' => '!!not-base64!!',
            // A valid envelope, but plain text inside rather than saved settings.
            'notserialized' => base64_encode('plain local.origin'),
            // The text has a quote and semicolon in it, which looks like the end of a stored text piece.
            'quotesemicolon' => base64_encode(serialize((object)['a' => 'x";y local.origin'])),
            // Saved settings that got cut off, so PHP can't read them.
            'truncated' => base64_encode(substr(serialize((object)['a' => 'local.origin']), 0, -3)),
        ];
        // Create a block for each one.
        $ids = [];
        foreach ($untouched as $key => $data) {
            $ids[$key] = $this->create_block('calendar_month', $data);
        }

        $cleaner = new clean(['dryrun' => false, 'verbose' => false]);
        $cleaner::execute();

        // Every one of them is exactly as it was.
        foreach ($untouched as $key => $data) {
            $this->assertSame($data, $DB->get_field('block_instances', 'configdata', ['id' => $ids[$key]]), $key);
        }
    }

    /**
     * A real HTML block saved through core: URLs in extra settings (including nested ones) are replaced,
     * while other settings and other blocks are left intact.
     * @group test_replace_blocks
     */
    public function test_block_configdata_real_html_block(): void {
        global $DB;

        // The html block ships with core, but skip rather than fail if it's somehow missing.
        if (core_component::get_plugin_directory('block', 'html') === null) {
            $this->markTestSkipped('block_html is not installed.');
        }

        $this->setAdminUser();
        set_config('newsiteurl', 'new.origin.longer', 'cleaner_replace_urls');
        set_config('cleanblocks', 1, 'cleaner_replace_urls');

        // Add an HTML block instance to the course and load it as the real block class.
        $blockid = $this->create_block('html', null);
        $block = block_instance('html', $DB->get_record('block_instances', ['id' => $blockid]));
        $this->assertNotFalse($block);

        // Let the block save its own settings, so core decides how they are stored.
        $data = (object)[
            'title' => 'Help',
            'text' => [
                'text' => '<a href="http://local.origin/help">Help</a>',
                'itemid' => file_get_unused_draft_itemid(),
                'format' => FORMAT_HTML,
            ],
            // The html block's own hook only handles "text", so this is only fixed by our step.
            'customlink' => 'http://local.origin/custom',
            'nested' => ['a' => (object)['b' => 'http://local.origin/nested']],
        ];
        $block->instance_config_save($data);

        // A second block that never mentions the old address.
        $otherdata = base64_encode(serialize((object)['text' => 'nothing']));
        $otherid = $this->create_block('calendar_upcoming', $otherdata);

        // Prove the data is stored the way we expect: it really holds the old address.
        $stored = $DB->get_field('block_instances', 'configdata', ['id' => $blockid]);
        $before = unserialize_object(base64_decode($stored));
        $this->assertEquals('http://local.origin/custom', $before->customlink);

        $cleaner = new clean(['dryrun' => false, 'verbose' => false]);
        $cleaner::execute();

        // The extra settings are swapped and the rest of the settings are intact.
        $stored = $DB->get_field('block_instances', 'configdata', ['id' => $blockid]);
        $after = unserialize_object(base64_decode($stored));
        $this->assertEquals('http://new.origin.longer/custom', $after->customlink);
        $this->assertEquals('http://new.origin.longer/nested', $after->nested['a']->b);
        $this->assertEquals('Help', $after->title);
        $this->assertEquals(FORMAT_HTML, $after->format);
        // The block without the old address is exactly as it was.
        $this->assertSame($otherdata, $DB->get_field('block_instances', 'configdata', ['id' => $otherid]));
    }
}
