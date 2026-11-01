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
 * Unit tests for the plugin's event observers
 *
 * @package    block_configurable_reports
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_configurable_reports;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for observer::course_deleted()
 *
 * @package    block_configurable_reports
 * @covers     \block_configurable_reports\observer
 */
#[CoversClass(observer::class)]
final class observer_test extends advanced_testcase {

    /**
     * Store a report against a course.
     *
     * @param int $courseid
     * @param string $name
     * @return int The report id.
     */
    private function create_report(int $courseid, string $name): int {
        global $DB, $USER;

        return (int) $DB->insert_record('block_configurable_reports', (object) [
            'courseid' => $courseid,
            'ownerid' => $USER->id,
            'visible' => 1,
            'name' => $name,
            'summary' => '',
            'type' => 'courses',
            'components' => '',
            'remote' => 0,
        ]);
    }

    /**
     * Issue #232: reports used to outlive their course, with no way to open or remove them.
     */
    public function test_a_deleted_course_takes_its_reports_with_it(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $doomed = $this->getDataGenerator()->create_course();
        $kept = $this->getDataGenerator()->create_course();

        $goes = $this->create_report($doomed->id, 'Report of the course being deleted');
        $stays = $this->create_report($kept->id, 'Report of the other course');
        $site = $this->create_report(SITEID, 'Site report');

        delete_course($doomed, false);

        $this->assertFalse($DB->record_exists('block_configurable_reports', ['id' => $goes]));
        $this->assertTrue($DB->record_exists('block_configurable_reports', ['id' => $stays]));
        $this->assertTrue($DB->record_exists('block_configurable_reports', ['id' => $site]));
    }

}
