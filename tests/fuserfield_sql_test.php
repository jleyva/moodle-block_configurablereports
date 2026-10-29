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
 * Unit tests for the user field filter with real queries (SQL and users reports)
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
require_once($CFG->dirroot . '/blocks/configurable_reports/components/filters/fuserfield/plugin.class.php');

/**
 * Tests for plugin_fuserfield
 *
 * @package    block_configurable_reports
 * @covers     \plugin_fuserfield
 */
#[CoversClass(\plugin_fuserfield::class)]
final class fuserfield_sql_test extends advanced_testcase {

    /** @var \plugin_fuserfield */
    private \plugin_fuserfield $filter;

    /** @var \stdClass[] Test users keyed by name */
    private array $users = [];

    protected function setUp(): void {
        global $DB, $remotedb;
        parent::setUp();
        $this->resetAfterTest();
        $remotedb = $DB;

        $this->filter = new \plugin_fuserfield((object) ['id' => 1, 'type' => 'sql', 'components' => '']);

        $generator = $this->getDataGenerator();
        $this->users['ana'] = $generator->create_user(['city' => 'Oberhausen', 'suspended' => 1,
            'description' => 'Likes long descriptions']);
        $this->users['bob'] = $generator->create_user(['city' => 'Madrid', 'suspended' => 0, 'description' => 'Short']);

        $generator->create_custom_profile_field(['datatype' => 'text', 'shortname' => 'dept', 'name' => 'Department']);
        $generator->create_custom_profile_field(['datatype' => 'text', 'shortname' => 'site', 'name' => 'Site']);
        $this->set_profile('ana', 'dept', 'Engineering');
        $this->set_profile('ana', 'site', 'North');
        $this->set_profile('bob', 'dept', 'Sales');
        $this->set_profile('bob', 'site', 'Engineering campus');
    }

    protected function tearDown(): void {
        foreach (array_keys($_GET) as $key) {
            if (strpos($key, 'filter_fuserfield_') === 0) {
                unset($_GET[$key]);
            }
        }
        parent::tearDown();
    }

    /**
     * Store a custom profile field value.
     *
     * @param string $user
     * @param string $shortname
     * @param string $value
     */
    private function set_profile(string $user, string $shortname, string $value): void {
        global $DB;
        $fieldid = $DB->get_field('user_info_field', 'id', ['shortname' => $shortname], MUST_EXIST);
        $DB->insert_record('user_info_data', ['userid' => $this->users[$user]->id, 'fieldid' => $fieldid, 'data' => $value]);
    }

    /**
     * Apply the filter for each field/value and run the query.
     *
     * @param string $sql Query with placeholders.
     * @param array $values Filter values keyed by field.
     * @return array [$sql, $params, test user names returned]
     */
    private function run_filters(string $sql, array $values): array {
        global $DB;

        $params = [];
        foreach ($values as $field => $value) {
            $_GET['filter_fuserfield_' . $field] = base64_encode($value);
            [$sql, $filterparams] = $this->filter->execute_for_sql_report($sql, (object) ['field' => $field]);
            $params = array_merge($params, $filterparams);
        }

        $ids = array_keys($DB->get_records_sql($sql, $params));
        $names = [];
        foreach ($this->users as $name => $user) {
            if (in_array($user->id, $ids)) {
                $names[] = $name;
            }
        }
        return [$sql, $params, $names];
    }

    /**
     * Issue #299: a filter with no value left the underscore form of its placeholder in the query, and the
     * ":column" inside what was left behind was then counted as a named parameter.
     */
    public function test_an_empty_filter_removes_the_underscore_placeholder(): void {
        global $DB;

        $sql = "SELECT u.id FROM {user} u WHERE u.deleted = 0 %%FILTER_USERS_city:u.city:~%%";
        [$sql, $params] = $this->filter->execute_for_sql_report($sql, (object) ['field' => 'city']);

        $this->assertStringNotContainsString('%%', $sql);
        $this->assertSame([], $params);
        $this->assertNotEmpty($DB->get_records_sql($sql, $params));
    }

    /**
     * Issue #190: PARAM_BASE64 rejects a single line of over 64 characters, which is any value over 48 bytes.
     */
    public function test_a_value_longer_than_48_characters_still_filters(): void {
        $long = 'Oberhausen, in the Ruhr, and then some more town name to pass forty-eight';
        $this->users['carla'] = $this->getDataGenerator()->create_user(['city' => $long]);

        // The test only means anything while the encoded value is past what PARAM_BASE64 accepts.
        $this->assertGreaterThan(48, strlen($long));
        $this->assertGreaterThan(64, strlen(base64_encode($long)));

        $sql = "SELECT u.id FROM {user} u WHERE u.deleted = 0 %%FILTER_USERS_city:u.city:~%%";
        [, $params, $names] = $this->run_filters($sql, ['city' => $long]);

        $this->assertSame(['carla'], $names);
        $this->assertSame(['%' . $long . '%'], array_values($params));
    }

    /**
     * Custom profile fields are accepted and filter with "contains" on an aliased column.
     */
    public function test_profile_field_contains(): void {
        $sql = "SELECT u.id FROM {user} u
                  JOIN {user_info_data} uid ON uid.userid = u.id
                  JOIN {user_info_field} uif ON uif.id = uid.fieldid AND uif.shortname = 'dept'
                 WHERE 1 = 1 %%FILTER_USERS_profile_dept:uid.data:~%%";

        [$sql, $params, $names] = $this->run_filters($sql, ['profile_dept' => 'ngineer']);

        $this->assertSame(['ana'], $names);
        $this->assertSame(['%ngineer%'], array_values($params));
        foreach (array_keys($params) as $name) {
            $this->assertMatchesRegularExpression('/^[a-z][a-z0-9_]*$/', $name);
        }
    }

    /**
     * Two profile field filters on the same column alias get distinct params.
     */
    public function test_two_profile_fields_do_not_share_params(): void {
        $sql = "SELECT u.id FROM {user} u
                  JOIN {user_info_data} uid ON uid.userid = u.id
                  JOIN {user_info_field} uif ON uif.id = uid.fieldid
                 WHERE uif.shortname = 'site' %%FILTER_USERS_profile_site:uid.data:=%%
                    OR uif.shortname = 'dept' %%FILTER_USERS_profile_dept:uid.data:=%%";

        [, $params, $names] = $this->run_filters($sql, ['profile_site' => 'North', 'profile_dept' => 'Sales']);

        $this->assertCount(2, $params);
        $this->assertEqualsCanonicalizing(['North', 'Sales'], array_values($params));
        $this->assertEqualsCanonicalizing(['ana', 'bob'], $names);
    }

    /**
     * Unknown custom profile fields still throw nosuchcolumn.
     */
    public function test_unknown_profile_field(): void {
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessageMatches('/nosuchcolumn/');
        $this->filter->execute_for_sql_report('SELECT 1', (object) ['field' => 'profile_doesnotexist']);
    }

    /**
     * Small integer columns (tinyint on MySQL) are accepted.
     */
    public function test_small_int_column(): void {
        $sql = 'SELECT u.id FROM {user} u WHERE u.deleted = 0 %%FILTER_USERS_suspended:u.suspended:=%%';

        [, $params, $names] = $this->run_filters($sql, ['suspended' => '1']);

        $this->assertSame(['ana'], $names);
        $this->assertEquals([1], array_values($params));
    }

    /**
     * Non-numeric values for integer columns are rejected.
     */
    public function test_int_column_rejects_text(): void {
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessageMatches('/invalidfiltervalue/');
        $this->run_filters('SELECT u.id FROM {user} u WHERE 1 = 1 %%FILTER_USERS_suspended:u.suspended:=%%',
            ['suspended' => 'yes']);
    }

    /**
     * Long text columns are accepted and "contains" is the default operator.
     */
    public function test_text_column_default_contains(): void {
        $sql = 'SELECT u.id FROM {user} u WHERE u.deleted = 0 %%FILTER_USERS_description:u.description%%';

        [, , $names] = $this->run_filters($sql, ['description' => 'long desc']);

        $this->assertSame(['ana'], $names);
    }

    /**
     * The "in" operator binds each value.
     */
    public function test_in_operator(): void {
        $sql = 'SELECT u.id FROM {user} u WHERE u.deleted = 0 %%FILTER_USERS_city:u.city:in%%';

        [, $params, $names] = $this->run_filters($sql, ['city' => 'Madrid,Oberhausen']);

        $this->assertEqualsCanonicalizing(['ana', 'bob'], $names);
        $this->assertEqualsCanonicalizing(['Madrid', 'Oberhausen'], array_values($params));
    }

    /**
     * When the placeholder is not in the query the SQL is returned unchanged (not emptied).
     */
    public function test_sql_unchanged_without_placeholder(): void {
        $_GET['filter_fuserfield_city'] = base64_encode('Madrid');

        [$sql, $params] = $this->filter->execute_for_sql_report('SELECT id FROM {user}', (object) ['field' => 'city']);

        $this->assertSame('SELECT id FROM {user}', $sql);
        $this->assertSame([], $params);
    }

    /**
     * Users reports: filtering by a user column uses "contains".
     */
    public function test_users_report_column(): void {
        $_GET['filter_fuserfield_city'] = base64_encode('hausen');

        $result = $this->filter->execute([$this->users['ana']->id, $this->users['bob']->id], (object) ['field' => 'city']);

        $this->assertEquals([$this->users['ana']->id], array_values($result));
    }

    /**
     * Users reports: filtering by a custom profile field uses "contains" and only returns users in scope.
     */
    public function test_users_report_profile_field(): void {
        $_GET['filter_fuserfield_profile_site'] = base64_encode('engineering');

        $result = $this->filter->execute([$this->users['ana']->id, $this->users['bob']->id],
            (object) ['field' => 'profile_site']);
        $this->assertEquals([$this->users['bob']->id], array_values($result));

        $result = $this->filter->execute([$this->users['ana']->id], (object) ['field' => 'profile_site']);
        $this->assertEquals([$this->users['ana']->id], array_values($result));
    }
}
