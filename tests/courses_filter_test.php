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
 * Unit tests for the courses filter
 *
 * @package    block_configurable_reports
 * @copyright  2024 Test Suite
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_configurable_reports;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG, $DB;
require_once($CFG->dirroot . '/blocks/configurable_reports/filter.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/plugin.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/components/filters/courses/plugin.class.php');

$remotedb = $DB;

/**
 * Tests for plugin_courses filter
 *
 * @package    block_configurable_reports
 */
class courses_filter_test extends advanced_testcase {
    /**
     * @var \stdClass Test report object
     */
    protected \stdClass $report;

    /**
     * @var \plugin_courses The filter instance
     */
    protected \plugin_courses $filter;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        // Create a mock report object
        $this->report = new \stdClass();
        $this->report->id = 1;
        $this->report->name = 'Test Report';
        $this->report->type = 'courses';
        $this->report->components = '';

        // Instantiate the filter
        $this->filter = new \plugin_courses($this->report);
    }

    /**
     * Test execute method with courses
     */
    public function test_execute_with_courses(): void {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $finalelements = [$course1->id, $course2->id];

        $data = new \stdClass();
        $data->courseid = $course1->id;

        $result = $this->filter->execute($finalelements);

        $this->assertEquals($finalelements, $result);
    }

    /**
     * Test filter with empty course list
     */
    public function test_filter_with_empty_courses(): void {
        $finalelements = [];

        $data = new \stdClass();
        $data->courseid = 1;

        $result = $this->filter->execute($finalelements);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test execute_for_sql_report with courses
     */
    public function test_execute_for_sql_report_with_courses(): void {
        $sql = "SELECT * FROM {course} WHERE 1=1 %%FILTER_COURSES:id%%";

        $data = new \stdClass();
        $data->courseid = 1;

        $result = $this->filter->execute_for_sql_report($sql, $data);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertStringNotContainsString('%%FILTER_COURSES:id%%', $result[0]);
        $this->assertIsArray($result[1]);
        $this->assertEquals(['filtercourses' => 1], $result[1]);
    }

    /**
     * Test filter with course category
     */
    public function test_filter_with_course_category(): void {
        $category = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category->id]);

        $finalelements = [$course1->id, $course2->id];

        $data = new \stdClass();
        $data->courseid = $course1->id;

        $result = $this->filter->execute($finalelements);

        $this->assertIsArray($result);
    }

    /**
     * Test multiple course filtering
     */
    public function test_multiple_course_filtering(): void {
        $courses = [];
        for ($i = 0; $i < 5; $i++) {
            $courses[] = $this->getDataGenerator()->create_course();
        }

        $finalelements = array_map(fn($course) => $course->id, $courses);

        $data = new \stdClass();
        $data->courseid = $courses[0]->id;

        $result = $this->filter->execute($finalelements);

        $this->assertIsArray($result);
        $this->assertCount(5, $result);
    }
}
