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
 * Unit tests for the fuserfield filter
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
require_once($CFG->dirroot . '/blocks/configurable_reports/components/filters/fuserfield/plugin.class.php');

$remotedb = $DB;

/**
 * Tests for plugin_fuserfield filter
 *
 * @package    block_configurable_reports
 */
class fuserfield_filter_test extends advanced_testcase {
    /**
     * @var \stdClass Test report object
     */
    protected \stdClass $report;

    /**
     * @var \plugin_fuserfield The filter instance
     */
    protected \plugin_fuserfield $filter;

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
        $this->filter = new \plugin_fuserfield($this->report);
    }

    /**
     * Test the summary method returns field name
     */
    public function test_summary_returns_field_name(): void {
        $data = new \stdClass();
        $data->field = 'username';

        $summary = $this->filter->summary($data);
        $this->assertEquals('username', $summary);
    }

    /**
     * Test execute_users with no filter applied
     */
    public function test_execute_users_no_filter_applied(): void {
        $user1 = $this->getDataGenerator()->create_user(['username' => 'testuser1']);
        $user2 = $this->getDataGenerator()->create_user(['username' => 'testuser2']);

        $finalelements = [$user1->id, $user2->id];

        $data = new \stdClass();
        $data->field = 'username';

        // When no filter is applied, should return elements unchanged
        $result = $this->filter->execute($finalelements, $data);

        $this->assertIsArray($result);
        $this->assertEquals($finalelements, $result);
    }

    /**
     * Test execute_users filters by standard user field
     */
    public function test_execute_users_filters_by_standard_field(): void {
        $user1 = $this->getDataGenerator()->create_user([
            'firstname' => 'John',
            'lastname' => 'Doe',
        ]);
        $user2 = $this->getDataGenerator()->create_user([
            'firstname' => 'Jane',
            'lastname' => 'Smith',
        ]);
        $user3 = $this->getDataGenerator()->create_user([
            'firstname' => 'Johnny',
            'lastname' => 'Test',
        ]);

        $finalelements = [$user1->id, $user2->id, $user3->id];

        $data = new \stdClass();
        $data->field = 'firstname';

        $result = $this->filter->execute($finalelements, $data);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    /**
     * Test that execute_for_sql_report returns SQL and params array
     */
    public function test_execute_for_sql_report_returns_array_structure(): void {
        $userid = 123;
        $_GET['filter_fuserfield_'] = $userid;

        $sql = "SELECT * FROM {user} WHERE 1=1 %%FILTER_USERS:id%%";

        $data = new \stdClass();
        $data->field = 'id';

        $result = $this->filter->execute_for_sql_report($sql, $data);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertIsString($result[0]);
        $this->assertIsArray($result[1]);
    }

    /**
     * Test execute_for_sql_report without filter removes filter placeholder
     */
    public function test_execute_for_sql_report_removes_filter_placeholder(): void {
        $sql = "SELECT * FROM {user} WHERE %%FILTER_USERS:username:=%% AND active = 1";

        $_GET['filter_fuserfield_username'] = base64_encode('testuser');
        $data = new \stdClass();
        $data->field = 'username';

        $result = $this->filter->execute_for_sql_report($sql, $data);

        $this->assertIsArray($result);
        $this->assertStringNotContainsString('%%FILTER_USERS:username', $result[0]);
    }

    /**
     * Test multiple users in filter scope
     */
    public function test_multiple_users_in_scope(): void {
        // Create multiple test users
        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $users[] = $this->getDataGenerator()->create_user([
                'firstname' => 'User' . $i,
                'email' => 'user' . $i . '@example.com',
            ]);
        }

        $finalelements = array_map(fn($user) => $user->id, $users);

        $data = new \stdClass();
        $data->field = 'email';

        $result = $this->filter->execute($finalelements, $data);

        $this->assertIsArray($result);
        $this->assertCount(5, $result);
    }

    /**
     * Test that empty elements array returns empty result
     */
    public function test_empty_elements_returns_empty_array(): void {
        $finalelements = [];
        $_GET['filter_user'] = base64_encode(22);

        $data = new \stdClass();
        $data->field = 'username';

        $result = $this->filter->execute($finalelements, $data);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
