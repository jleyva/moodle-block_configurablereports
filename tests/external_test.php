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
 * Tests for the web service that returns report data
 *
 * @package    block_configurable_reports
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_configurable_reports;

use advanced_testcase;
use context_course;
use moodle_exception;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/configurable_reports/locallib.php');

/**
 * Tests for the web service that returns report data
 *
 * @package    block_configurable_reports
 * @covers     \block_configurable_reports\external
 */
#[CoversClass(external::class)]
final class external_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Create a users report listing usernames.
     *
     * @param int $courseid The course the report belongs to.
     * @param int $global Whether the report may run in any course.
     * @param string $type The report type.
     * @return stdClass The report record.
     */
    private function create_users_report(int $courseid, int $global = 0, string $type = 'users'): stdClass {
        global $DB;

        $components = [
            'columns' => [
                'elements' => [
                    [
                        'id' => 'c1',
                        'pluginname' => 'userfield',
                        'pluginfullname' => 'User profile field',
                        'summary' => 'Username',
                        'formdata' => (object) ['column' => 'username', 'columname' => 'Username'],
                    ],
                ],
            ],
        ];

        $report = (object) [
            'courseid' => $courseid,
            'ownerid' => get_admin()->id,
            'visible' => 1,
            'global' => $global,
            'name' => 'People',
            'summary' => '',
            'type' => $type,
            'components' => cr_serialize($components),
            'remote' => 0,
        ];
        $report->id = $DB->insert_record('block_configurable_reports', $report);

        return $DB->get_record('block_configurable_reports', ['id' => $report->id]);
    }

    /**
     * Give a user the manager role in a course.
     *
     * @param int $userid The user.
     * @param int $courseid The course.
     */
    private function make_manager(int $userid, int $courseid): void {
        global $DB;

        $roleid = $DB->get_field('role', 'id', ['shortname' => 'manager'], MUST_EXIST);
        role_assign($roleid, $userid, context_course::instance($courseid)->id);
    }

    /**
     * A caller cannot borrow their permissions in one course to read a report belonging to another.
     */
    public function test_permissions_are_checked_in_the_report_course(): void {
        $reportcourse = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();

        $user = $this->getDataGenerator()->create_user();
        // The caller can enter the report course, but only manages the other one.
        $this->getDataGenerator()->enrol_user($user->id, $reportcourse->id, 'student');
        $this->make_manager($user->id, $othercourse->id);

        $report = $this->create_users_report($reportcourse->id);
        $this->setUser($user);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage(get_string('badpermissions', 'block_configurable_reports'));
        external::get_report_data($report->id, $othercourse->id);
    }

    /**
     * Someone who manages the report's own course can read it.
     */
    public function test_manager_of_the_report_course_can_read_it(): void {
        $reportcourse = $this->getDataGenerator()->create_course();

        $user = $this->getDataGenerator()->create_user(['username' => 'readerone']);
        $this->make_manager($user->id, $reportcourse->id);

        $report = $this->create_users_report($reportcourse->id);
        $this->setUser($user);

        $result = external::get_report_data($report->id, $reportcourse->id);

        $this->assertSame('', $result['warnings']);
        $this->assertStringContainsString('readerone', $result['data']);
    }

    /**
     * The course id the caller passes is only honoured for a global report.
     */
    public function test_a_global_report_runs_in_the_course_the_caller_names(): void {
        $reportcourse = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();

        $user = $this->getDataGenerator()->create_user(['username' => 'readertwo']);
        // The caller manages only the other course, which is enough for a global report.
        $this->make_manager($user->id, $othercourse->id);

        $report = $this->create_users_report($reportcourse->id, 1);
        $this->setUser($user);

        $result = external::get_report_data($report->id, $othercourse->id);

        $this->assertSame('', $result['warnings']);
        $this->assertStringContainsString('readertwo', $result['data']);
    }

    /**
     * Asking for a report that is not there says so instead of failing.
     */
    public function test_a_missing_report_is_reported_as_a_warning(): void {
        $this->setAdminUser();

        $result = external::get_report_data(-1);

        $this->assertSame(get_string('reportdoesnotexists', 'block_configurable_reports'), $result['warnings']);
        $this->assertSame('[]', $result['data']);
    }

    /**
     * The report type names a directory, so a report carrying anything else is refused.
     */
    public function test_a_report_of_an_unknown_type_is_refused(): void {
        $course = $this->getDataGenerator()->create_course();
        $report = $this->create_users_report($course->id, 0, '../../../../config');

        $this->setAdminUser();

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage(get_string('reportdoesnotexists', 'block_configurable_reports'));
        external::get_report_data($report->id, $course->id);
    }
}
