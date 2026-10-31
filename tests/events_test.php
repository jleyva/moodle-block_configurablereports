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
 * Unit tests for the report events
 *
 * @package    block_configurable_reports
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_configurable_reports;

use advanced_testcase;
use block_configurable_reports\event\report_created;
use block_configurable_reports\event\report_deleted;
use block_configurable_reports\event\report_event;
use block_configurable_reports\event\report_exported;
use block_configurable_reports\event\report_updated;
use block_configurable_reports\event\report_viewed;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/configurable_reports/locallib.php');

/**
 * Tests for the report events
 *
 * @package    block_configurable_reports
 * @covers     \block_configurable_reports\event\report_event
 */
#[CoversClass(report_event::class)]
final class events_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Create a users report listing usernames.
     *
     * @param int $courseid
     * @return \stdClass
     */
    private function create_report(int $courseid): \stdClass {
        global $DB, $USER;

        $components = [
            'columns' => ['elements' => [[
                'id' => 'c1',
                'pluginname' => 'userfield',
                'pluginfullname' => 'User profile field',
                'summary' => 'Username',
                'formdata' => (object) ['column' => 'username', 'columname' => 'Username'],
            ]]],
        ];
        $report = (object) [
            'courseid' => $courseid,
            'ownerid' => $USER->id,
            'visible' => 1,
            'global' => 0,
            'name' => 'People',
            'summary' => '',
            'type' => 'users',
            'components' => cr_serialize($components),
            'remote' => 0,
        ];
        $report->id = $DB->insert_record('block_configurable_reports', $report);

        return $DB->get_record('block_configurable_reports', ['id' => $report->id]);
    }

    /**
     * Every event records the report, its course context and what happened.
     *
     * @dataProvider event_provider
     * @param string $classname
     * @param string $crud
     * @param array $other
     * @param string $description Part of the description.
     */
    #[DataProvider('event_provider')]
    public function test_event_data(string $classname, string $crud, array $other, string $description): void {
        global $USER;

        $course = $this->getDataGenerator()->create_course();
        $report = $this->create_report($course->id);

        $sink = $this->redirectEvents();
        $classname::create_from_report($report, null, $other)->trigger();
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf($classname, $event);
        $this->assertSame($crud, $event->crud);
        $this->assertEquals($report->id, $event->objectid);
        $this->assertEquals(\context_course::instance($course->id), $event->get_context());
        $this->assertEquals($USER->id, $event->userid);
        $this->assertSame('People', $event->other['name']);
        $this->assertSame('users', $event->other['type']);
        $this->assertStringContainsString($description, $event->get_description());
        $this->assertNotEmpty($classname::get_name());
        $this->assertEquals(new \moodle_url('/blocks/configurable_reports/viewreport.php', ['id' => $report->id]),
            $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertDebuggingNotCalled();
    }

    /**
     * Data provider for test_event_data.
     *
     * @return array
     */
    public static function event_provider(): array {
        return [
            'viewed' => [report_viewed::class, 'r', [], 'viewed the configurable report'],
            'created' => [report_created::class, 'c', [], 'created the configurable report'],
            'updated' => [report_updated::class, 'u', ['component' => 'columns'], 'updated the configurable report'],
            'deleted' => [report_deleted::class, 'd', [], 'deleted the configurable report'],
            'exported' => [report_exported::class, 'r', ['format' => 'csv'], "in the format 'csv'"],
        ];
    }

    /**
     * Site reports are logged in the system context.
     */
    public function test_site_report_context(): void {
        global $SITE;

        $event = report_viewed::create_from_report($this->create_report($SITE->id));

        $this->assertEquals(\context_system::instance(), $event->get_context());
    }

    /**
     * The exported event needs the format.
     */
    public function test_exported_requires_format(): void {
        global $SITE;

        $this->expectException(\coding_exception::class);
        report_exported::create_from_report($this->create_report($SITE->id));
    }

    /**
     * Importing a report logs that it was created.
     */
    public function test_import_triggers_created(): void {
        global $DB, $SITE;

        $components = base64_encode(cr_serialize(['columns' => ['elements' => []]]));
        $xml = '<?xml version="1.0" encoding="UTF-8" ?><report version="2024051300"><name><![CDATA[Imported]]></name>' .
            '<type><![CDATA[users]]></type><components><![CDATA[' . $components . ']]></components></report>';

        $sink = $this->redirectEvents();
        $this->assertTrue(cr_import_xml($xml, $SITE));
        $events = array_values(array_filter($sink->get_events(), fn($e) => $e instanceof report_created));
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertTrue($events[0]->other['imported']);
        $this->assertEquals($DB->get_field('block_configurable_reports', 'id', []), $events[0]->objectid);
    }

    /**
     * Reading report data through the web service logs that the report was viewed.
     */
    public function test_web_service_triggers_viewed(): void {
        $course = $this->getDataGenerator()->create_course();
        $report = $this->create_report($course->id);

        $sink = $this->redirectEvents();
        external::get_report_data($report->id, (int) $course->id);
        $events = array_values(array_filter($sink->get_events(), fn($e) => $e instanceof report_viewed));
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertEquals($report->id, $events[0]->objectid);
        $this->assertEquals(\context_course::instance($course->id), $events[0]->get_context());
    }
}
