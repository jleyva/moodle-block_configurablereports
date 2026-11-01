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
 * Unit tests for the filters of course, user and category reports that had no tests
 *
 * @package    block_configurable_reports
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_configurable_reports;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/configurable_reports/locallib.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/report.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/reports/courses/report.class.php');

/**
 * Tests for execute() of the filters in non SQL reports
 *
 * @package    block_configurable_reports
 * @coversNothing
 */
#[CoversNothing]
final class report_filters_test extends advanced_testcase {

    /** @var string[] Request parameters set by the test, removed in tearDown() */
    private array $requestparams = [];

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

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
     * Set a request parameter, as the filter form submits it.
     *
     * @param string $name
     * @param mixed $value
     */
    private function set_request(string $name, $value): void {
        $_GET[$name] = $value;
        $this->requestparams[] = $name;
    }

    /**
     * Create a filter plugin for a report of the given type.
     *
     * @param string $pluginname
     * @param string $reporttype
     * @return \plugin_base
     */
    private function filter(string $pluginname, string $reporttype): \plugin_base {
        $classname = cr_require_component_plugin('filters', $pluginname);
        $report = (object) ['id' => 0, 'type' => $reporttype, 'components' => cr_serialize([]), 'courseid' => SITEID];

        return new $classname($report);
    }

    /**
     * The id filters narrow the elements to the selected one, or leave them as they are without a value.
     * A value the report conditions did not select gives no elements, so a filter never widens a report.
     *
     * @dataProvider id_filter_provider
     * @param string $pluginname
     * @param string $reporttype
     * @param string $param
     */
    #[DataProvider('id_filter_provider')]
    public function test_id_filters(string $pluginname, string $reporttype, string $param): void {
        $filter = $this->filter($pluginname, $reporttype);

        $this->assertSame([4, 5, 6], $filter->execute([4, 5, 6]));

        $this->set_request($param, 5);
        $this->assertEquals([5], $filter->execute([4, 5, 6]));

        $this->set_request($param, 7);
        $this->assertSame([], $filter->execute([4, 5, 6]));
    }

    /**
     * Data provider for test_id_filters.
     *
     * @return array
     */
    public static function id_filter_provider(): array {
        return [
            'categories' => ['categories', 'categories', 'filter_categories'],
            'role' => ['role', 'categories', 'filter_role'],
            'users' => ['users', 'courses', 'filter_users'],
            'enrolled students' => ['enrolledstudents', 'courses', 'filter_enrolledstudents'],
            'subcategories' => ['subcategories', 'categories', 'filter_subcategories'],
            'year numeric' => ['yearnumeric', 'categories', 'filter_yearnumeric'],
            'courses' => ['courses', 'courses', 'filter_courses'],
            'course user' => ['user', 'courses', 'filter_user'],
            'course modules' => ['coursemodules', 'courses', 'filter_coursemodules'],
        ];
    }

    /**
     * Course categories filter keeps the courses in the category and in its subcategories.
     */
    public function test_course_categories(): void {
        $parent = $this->getDataGenerator()->create_category();
        $child = $this->getDataGenerator()->create_category(['parent' => $parent->id]);
        $other = $this->getDataGenerator()->create_category();
        $c1 = $this->getDataGenerator()->create_course(['category' => $parent->id]);
        $c2 = $this->getDataGenerator()->create_course(['category' => $child->id]);
        $c3 = $this->getDataGenerator()->create_course(['category' => $other->id]);
        $courses = [$c1->id, $c2->id, $c3->id];
        $filter = $this->filter('coursecategories', 'courses');

        $this->assertSame($courses, $filter->execute($courses));

        $this->set_request('filter_coursecategories', $parent->id);
        $this->assertEquals([$c1->id, $c2->id], array_values($filter->execute($courses)));

        $this->set_request('filter_coursecategories', $child->id);
        $this->assertEquals([$c2->id], array_values($filter->execute($courses)));
    }

    /**
     * Course field filter keeps the courses with the selected value, and ignores fields that are not course columns.
     */
    public function test_course_field(): void {
        $c1 = $this->getDataGenerator()->create_course(['shortname' => 'crtest1']);
        $c2 = $this->getDataGenerator()->create_course(['shortname' => 'crtest2']);
        $courses = [$c1->id, $c2->id];
        $filter = $this->filter('fcoursefield', 'courses');
        $data = (object) ['field' => 'shortname'];

        $this->assertSame($courses, $filter->execute($courses, $data));

        $this->set_request('filter_fcoursefield_shortname', base64_encode('crtest2'));
        $this->assertEquals([$c2->id], $filter->execute($courses, $data));

        $this->set_request('filter_fcoursefield_shortname', base64_encode('nomatch'));
        $this->assertSame([], $filter->execute($courses, $data));

        // Imported reports skip the form: a field that is not a course column is ignored, not put in the SQL.
        $field = 'id = 0 OR 1';
        $this->set_request('filter_fcoursefield_' . $field, base64_encode('x'));
        $this->assertSame($courses, $filter->execute($courses, (object) ['field' => $field]));
    }

    /**
     * User field search filter keeps the users with the selected value, in a user column or a custom profile field.
     */
    public function test_user_field_search(): void {
        global $DB;

        $fieldid = $DB->insert_record('user_info_field', (object) ['shortname' => 'dept', 'name' => 'Department',
            'datatype' => 'text', 'categoryid' => 1]);
        $u1 = $this->getDataGenerator()->create_user(['city' => 'Madrid']);
        $u2 = $this->getDataGenerator()->create_user(['city' => 'Barcelona']);
        $DB->insert_record('user_info_data', (object) ['userid' => $u2->id, 'fieldid' => $fieldid, 'data' => 'Sales']);
        $users = [$u1->id, $u2->id];
        $filter = $this->filter('fsearchuserfield', 'users');

        $this->assertSame($users, $filter->execute($users, (object) ['field' => 'city']));

        $this->set_request('filter_fuserfield_city', base64_encode('madrid'));
        $this->assertEquals([$u1->id], $filter->execute($users, (object) ['field' => 'city']));

        $this->set_request('filter_fuserfield_city', base64_encode('Paris'));
        $this->assertSame([], $filter->execute($users, (object) ['field' => 'city']));

        $this->set_request('filter_fuserfield_profile_dept', base64_encode('sales'));
        $this->assertEquals([$u2->id], $filter->execute($users, (object) ['field' => 'profile_dept']));

        // Credentials and fields that are not user columns are never searched.
        $this->set_request('filter_fuserfield_password', base64_encode('x'));
        $this->assertSame($users, $filter->execute($users, (object) ['field' => 'password']));
    }

    /**
     * Semester and Hebrew year filters keep the selected value only when it is one of the elements.
     */
    public function test_semester_and_hebrew_year(): void {
        $filter = $this->filter('semester', 'categories');

        $this->assertSame([1, 2], $filter->execute([1, 2]));

        $this->set_request('filter_semester', 'Spring');
        $this->assertSame([], $filter->execute([1, 2]));
        $this->assertSame(['Spring'], $filter->execute(['Spring', 'Autumn']));

        $filter = $this->filter('yearhebrew', 'categories');
        $this->set_request('filter_yearhebrew', 'תשפ"ז');
        $this->assertSame([], $filter->execute([1, 2]));
    }

    /**
     * User field filter keeps the users whose user column contains the value, and only uses fields that are user
     * columns, as imported reports skip the form.
     */
    public function test_user_field(): void {
        global $DB;

        $u1 = $this->getDataGenerator()->create_user(['city' => 'Madrid']);
        $u2 = $this->getDataGenerator()->create_user(['city' => 'Barcelona']);
        $users = [$u1->id, $u2->id];
        $filter = $this->filter('fuserfield', 'users');

        $this->set_request('filter_fuserfield_city', base64_encode('madr'));
        $this->assertEquals([$u1->id], $filter->execute($users, (object) ['field' => 'city']));

        // An SQL expression as the field: a true condition would keep both users, a false one neither.
        $field = "(CASE WHEN (SELECT COUNT(1) FROM {user}) > 0 THEN username ELSE 'zz' END)";
        $this->set_request('filter_fuserfield_' . $field, base64_encode('a'));
        $this->assertSame($users, $filter->execute($users, (object) ['field' => $field]));

        // Credentials are never searched, even though they are user columns.
        $DB->set_field('user', 'password', 'secret', ['id' => $u1->id]);
        $this->set_request('filter_fuserfield_password', base64_encode('secret'));
        $this->assertSame($users, $filter->execute($users, (object) ['field' => 'password']));
    }

    /**
     * A filter value sent in the request cannot show a course the report conditions left out.
     */
    public function test_filters_cannot_widen_the_report(): void {
        global $USER;
        $this->setAdminUser();

        $mine = $this->getDataGenerator()->create_course(['shortname' => 'mine']);
        $other = $this->getDataGenerator()->create_course(['shortname' => 'other', 'visible' => 0]);
        $this->getDataGenerator()->enrol_user($USER->id, $mine->id);

        $components = [
            'conditions' => ['elements' => [['pluginname' => 'currentusercourses', 'formdata' => new \stdClass()]]],
            'filters' => ['elements' => [['pluginname' => 'courses', 'formdata' => new \stdClass()]]],
            'columns' => ['elements' => [['pluginname' => 'coursefield',
                'formdata' => (object) ['column' => 'shortname', 'columname' => 'Short name']]]],
        ];
        $report = new \report_courses((object) ['id' => 0, 'courseid' => SITEID, 'ownerid' => $USER->id, 'type' => 'courses',
            'name' => 'My courses', 'summary' => '', 'remote' => 0, 'components' => cr_serialize($components)]);

        $shortnames = function() use ($report): array {
            $report->create_report();
            return array_column($report->finalreport->table->data, 0);
        };

        $this->set_request('filter_courses', $mine->id);
        $this->assertSame(['mine'], $shortnames());

        $this->set_request('filter_courses', $other->id);
        $this->assertSame([], $shortnames());
    }
}
