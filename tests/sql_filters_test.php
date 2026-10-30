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
 * Unit tests for the filters of SQL reports that had no tests: each one runs a real query through prepare_sql()
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
require_once($CFG->dirroot . '/blocks/configurable_reports/locallib.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/report.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/reports/sql/report.class.php');

/**
 * Tests for the SQL placeholders of the categories, role, users, enrolled students, subcategories, semester, year and
 * user field search filters
 *
 * @package    block_configurable_reports
 * @covers     \report_sql::prepare_sql
 */
#[CoversClass(\report_sql::class)]
final class sql_filters_test extends advanced_testcase {

    /** @var string[] Request parameters set by the test, removed in tearDown() */
    private array $requestparams = [];

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        global $DB, $remotedb;
        $remotedb = $DB;
    }

    protected function tearDown(): void {
        foreach ($this->requestparams as $name) {
            unset($_GET[$name]);
        }
        parent::tearDown();
    }

    /**
     * Run a query through a SQL report with one filter, and return the first column of the rows.
     *
     * @param string $pluginname
     * @param string $query
     * @param array $request Request parameters, as the filter form submits them.
     * @param \stdClass|null $formdata
     * @return array
     */
    private function run_filter(string $pluginname, string $query, array $request = [], ?\stdClass $formdata = null): array {
        global $DB, $SITE, $USER;

        foreach ($request as $name => $value) {
            $_GET[$name] = $value;
            $this->requestparams[] = $name;
        }

        $components = [
            'customsql' => ['config' => (object) ['querysql' => $query]],
            'filters' => ['elements' => [['pluginname' => $pluginname, 'formdata' => $formdata ?? new \stdClass()]]],
        ];
        $report = (object) [
            'id' => 0,
            'courseid' => $SITE->id,
            'ownerid' => $USER->id,
            'remote' => 0,
            'type' => 'sql',
            'components' => cr_serialize($components),
        ];

        [$sql, $params] = (new \report_sql($report))->prepare_sql($query);
        $this->assertStringNotContainsString('%%', $sql);

        $rows = $DB->get_fieldset_sql($sql, $params);
        sort($rows);

        return $rows;
    }

    /**
     * Course categories filter: %%FILTER_CATEGORIES%%.
     */
    public function test_categories(): void {
        $cat1 = $this->getDataGenerator()->create_category();
        $cat2 = $this->getDataGenerator()->create_category();
        $c1 = $this->getDataGenerator()->create_course(['category' => $cat1->id]);
        $c2 = $this->getDataGenerator()->create_course(['category' => $cat2->id]);
        $query = "SELECT c.id FROM {course} c WHERE c.id IN ($c1->id, $c2->id) %%FILTER_CATEGORIES:c.category%%";

        $this->assertEquals([$c1->id], $this->run_filter('categories', $query, ['filter_categories' => $cat1->id]));
        $this->assertEquals([$c1->id, $c2->id], $this->run_filter('categories', $query, ['filter_categories' => 0]));
    }

    /**
     * Role filter: %%FILTER_ROLE%%.
     */
    public function test_role(): void {
        global $DB;
        $student = $DB->get_field('role', 'id', ['shortname' => 'student']);
        $teacher = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        $query = "SELECT r.id FROM {role} r WHERE r.id IN ($student, $teacher) %%FILTER_ROLE:r.id%%";

        $this->assertEquals([$student], $this->run_filter('role', $query, ['filter_role' => $student]));
        $expected = [$student, $teacher];
        sort($expected);
        $this->assertEquals($expected, $this->run_filter('role', $query, ['filter_role' => 0]));
    }

    /**
     * Users filter: %%FILTER_SYSTEMUSER%%.
     */
    public function test_users(): void {
        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $query = "SELECT u.id FROM {user} u WHERE u.id IN ($u1->id, $u2->id) %%FILTER_SYSTEMUSER:u.id%%";

        $this->assertEquals([$u2->id], $this->run_filter('users', $query, ['filter_users' => $u2->id]));
        $this->assertEquals([$u1->id, $u2->id], $this->run_filter('users', $query, ['filter_users' => 0]));
    }

    /**
     * Enrolled students filter: %%FILTER_COURSEENROLLEDSTUDENTS%%.
     */
    public function test_enrolled_students(): void {
        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $query = "SELECT u.id FROM {user} u WHERE u.id IN ($u1->id, $u2->id) %%FILTER_COURSEENROLLEDSTUDENTS:u.id%%";

        $this->assertEquals([$u1->id],
            $this->run_filter('enrolledstudents', $query, ['filter_enrolledstudents' => $u1->id]));
        $this->assertEquals([$u1->id, $u2->id],
            $this->run_filter('enrolledstudents', $query, ['filter_enrolledstudents' => 0]));
    }

    /**
     * Subcategories filter: %%FILTER_SUBCATEGORIES%% matches the category and everything below it.
     */
    public function test_subcategories(): void {
        $parent = $this->getDataGenerator()->create_category();
        $child = $this->getDataGenerator()->create_category(['parent' => $parent->id]);
        $grandchild = $this->getDataGenerator()->create_category(['parent' => $child->id]);
        $other = $this->getDataGenerator()->create_category();
        $ids = implode(',', [$parent->id, $child->id, $grandchild->id, $other->id]);
        $query = "SELECT cc.id FROM {course_categories} cc WHERE cc.id IN ($ids) %%FILTER_SUBCATEGORIES:cc.path%%";

        $this->assertEquals([$child->id, $grandchild->id],
            $this->run_filter('subcategories', $query, ['filter_subcategories' => $child->id]));
        $this->assertEquals([$parent->id, $child->id, $grandchild->id],
            $this->run_filter('subcategories', $query, ['filter_subcategories' => $parent->id]));
        $this->assertCount(4, $this->run_filter('subcategories', $query, ['filter_subcategories' => 0]));
    }

    /**
     * Semester filter: %%FILTER_SEMESTER%% is a case insensitive match, with the value bound.
     */
    public function test_semester(): void {
        $c1 = $this->getDataGenerator()->create_course(['idnumber' => 'spring']);
        $c2 = $this->getDataGenerator()->create_course(['idnumber' => 'autumn']);
        $query = "SELECT c.id FROM {course} c WHERE c.id IN ($c1->id, $c2->id) %%FILTER_SEMESTER:c.idnumber%%";

        $this->assertEquals([$c1->id], $this->run_filter('semester', $query, ['filter_semester' => 'Spring']));
        $this->assertEquals([], $this->run_filter('semester', $query, ['filter_semester' => "x' OR '1'='1"]));
        $this->assertEquals([$c1->id, $c2->id], $this->run_filter('semester', $query, ['filter_semester' => '']));
    }

    /**
     * Numeric year filter: %%FILTER_YEARNUMERIC%%.
     */
    public function test_year_numeric(): void {
        $c1 = $this->getDataGenerator()->create_course(['idnumber' => '2026']);
        $c2 = $this->getDataGenerator()->create_course(['idnumber' => '2027']);
        $query = "SELECT c.id FROM {course} c WHERE c.id IN ($c1->id, $c2->id) %%FILTER_YEARNUMERIC:c.idnumber%%";

        $this->assertEquals([$c2->id], $this->run_filter('yearnumeric', $query, ['filter_yearnumeric' => 2027]));
        $this->assertEquals([$c1->id, $c2->id], $this->run_filter('yearnumeric', $query, ['filter_yearnumeric' => 0]));
    }

    /**
     * Hebrew year filter: %%FILTER_YEARHEBREW%% matches values containing the year.
     */
    public function test_year_hebrew(): void {
        $c1 = $this->getDataGenerator()->create_course(['idnumber' => 'תשפ"ו-a']);
        $c2 = $this->getDataGenerator()->create_course(['idnumber' => 'תשפ"ז-a']);
        $query = "SELECT c.id FROM {course} c WHERE c.id IN ($c1->id, $c2->id) %%FILTER_YEARHEBREW:c.idnumber%%";

        $this->assertEquals([$c2->id], $this->run_filter('yearhebrew', $query, ['filter_yearhebrew' => 'תשפ"ז']));
        $this->assertEquals([$c1->id, $c2->id], $this->run_filter('yearhebrew', $query, ['filter_yearhebrew' => '']));
    }

    /**
     * User field search filter: %%FILTER_USERS%% with the value encoded as the filter form sends it.
     */
    public function test_user_field_search(): void {
        $u1 = $this->getDataGenerator()->create_user(['city' => 'Madrid']);
        $u2 = $this->getDataGenerator()->create_user(['city' => 'Barcelona']);
        $query = "SELECT u.id FROM {user} u WHERE u.id IN ($u1->id, $u2->id) %%FILTER_USERS:u.city%%";
        $formdata = (object) ['field' => 'city'];

        $this->assertEquals([$u1->id], $this->run_filter('fsearchuserfield', $query,
            ['filter_fuserfield_city' => base64_encode('Madrid')], $formdata));
        $this->assertEquals([$u1->id, $u2->id], $this->run_filter('fsearchuserfield', $query,
            ['filter_fuserfield_city' => ''], $formdata));
    }
}
