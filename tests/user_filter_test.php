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
 * Unit tests for the user filter
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
require_once($CFG->dirroot . '/blocks/configurable_reports/components/filters/user/plugin.class.php');

$remotedb = $DB;

/**
 * Tests for plugin_user filter
 *
 * @package    block_configurable_reports
 */
class user_filter_test extends advanced_testcase {
    /**
     * @var \stdClass Test report object
     */
    protected \stdClass $report;

    /**
     * @var \plugin_user The filter instance
     */
    protected \plugin_user $filter;

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
        $this->report->type = 'users';
        $this->report->components = '';

        // Instantiate the filter
        $this->filter = new \plugin_user($this->report);
    }

    /**
     * Test summary method
     */
    public function test_summary_method(): void {
        $data = new \stdClass();
        $data->userid = 2;

        $summary = $this->filter->summary($data);

        $this->assertIsString($summary);
    }

    /**
     * Test execute method returns array
     */
    public function test_execute_returns_array(): void {
        $user = $this->getDataGenerator()->create_user();
        $finalelements = [$user->id];

        $data = new \stdClass();
        $data->userid = $user->id;

        $result = $this->filter->execute($finalelements);

        $this->assertIsArray($result);
    }

    /**
     * Test filter with valid user
     */
    public function test_filter_with_valid_user(): void {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $finalelements = [$user1->id, $user2->id];

        $data = new \stdClass();
        $data->userid = $user1->id;

        $result = $this->filter->execute($finalelements);

        $this->assertIsArray($result);
        $this->assertContains($user1->id, $result);
    }

    /**
     * Test filter with empty elements
     */
    public function test_filter_with_empty_elements(): void {
        $finalelements = [];

        $data = new \stdClass();
        $data->userid = 1;

        $result = $this->filter->execute($finalelements);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test execute_for_sql_report returns array
     */
    public function test_execute_for_sql_report_returns_array(): void {
        $sql = "SELECT * FROM {user} WHERE 1=1 %%FILTER_COURSEUSER:id%%";
        $_GET['filter_user'] = 22;

        $data = new \stdClass();
        $data->userid = 2;

        $result = $this->filter->execute_for_sql_report($sql, $data);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertIsString($result[0]);
        $this->assertIsArray($result[1]);
    }
}
