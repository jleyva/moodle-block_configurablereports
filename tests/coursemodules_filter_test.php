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
 * Unit tests for the course modules filter on SQL reports
 *
 * @package    block_configurable_reports
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_configurable_reports;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/configurable_reports/filter.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/plugin.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/components/filters/coursemodules/plugin.class.php');

/**
 * Tests for plugin_coursemodules::execute_for_sql_report()
 *
 * @package    block_configurable_reports
 * @covers     \plugin_coursemodules
 */
#[CoversClass(\plugin_coursemodules::class)]
final class coursemodules_filter_test extends advanced_testcase {

    /** @var string Query using the three placeholders */
    private const QUERY = 'SELECT cm.id %%FILTER_COURSEMODULEFIELDS:, m.name AS modname%%
                             FROM {course_modules} cm %%FILTER_COURSEMODULE:cm.instance%%
                            WHERE cm.course = :courseid %%FILTER_COURSEMODULEID:cm.module%%
                         ORDER BY cm.id';

    /** @var \plugin_coursemodules */
    private \plugin_coursemodules $filter;

    protected function setUp(): void {
        global $DB, $remotedb;
        parent::setUp();
        $this->resetAfterTest();
        $remotedb = $DB;

        $this->filter = new \plugin_coursemodules((object) ['id' => 1, 'type' => 'sql', 'components' => '']);
    }

    protected function tearDown(): void {
        unset($_GET['filter_coursemodules']);
        parent::tearDown();
    }

    /**
     * Selecting a module type joins its table on the template column and lists its fields.
     */
    public function test_filter_by_module(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $forum1 = $this->getDataGenerator()->create_module('forum', ['course' => $course->id, 'name' => 'Forum one']);
        $forum2 = $this->getDataGenerator()->create_module('forum', ['course' => $course->id, 'name' => 'Forum two']);
        $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'name' => 'A page']);

        $_GET['filter_coursemodules'] = $DB->get_field('modules', 'id', ['name' => 'forum']);
        [$sql, $params] = $this->filter->execute_for_sql_report(self::QUERY);

        $this->assertStringNotContainsString('%%', $sql);
        $this->assertStringContainsString('JOIN {forum} AS m ON m.id = cm.instance', $sql);
        $this->assertStringContainsString(', m.name AS modname', $sql);

        $records = $DB->get_records_sql($sql, $params + ['courseid' => $course->id]);
        $this->assertEqualsCanonicalizing([$forum1->cmid, $forum2->cmid], array_keys($records));
        $this->assertEqualsCanonicalizing(['Forum one', 'Forum two'], array_column($records, 'modname'));
    }

    /**
     * Without a selected module the SQL is returned unchanged (placeholders are removed later by prepare_sql()).
     */
    public function test_no_module_selected(): void {
        [$sql, $params] = $this->filter->execute_for_sql_report(self::QUERY);

        $this->assertSame(self::QUERY, $sql);
        $this->assertSame([], $params);
    }

    /**
     * An unknown module id gives a clean exception rather than a PHP error.
     */
    public function test_unknown_module(): void {
        $_GET['filter_coursemodules'] = 999999;

        $this->expectException(\dml_missing_record_exception::class);
        $this->filter->execute_for_sql_report(self::QUERY);
    }
}
