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
 * Unit tests for the user field and course field conditions
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
require_once($CFG->dirroot . '/blocks/configurable_reports/plugin.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/components/conditions/cuserfield/plugin.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/components/conditions/ccoursefield/plugin.class.php');

/**
 * Tests for the "contains" operator of plugin_cuserfield and plugin_ccoursefield
 *
 * @package    block_configurable_reports
 * @covers     \plugin_cuserfield
 * @covers     \plugin_ccoursefield
 */
#[CoversClass(\plugin_cuserfield::class)]
#[CoversClass(\plugin_ccoursefield::class)]
final class conditions_contains_test extends advanced_testcase {

    /**
     * User field condition: "contains" on a user column matches substrings, case-insensitively.
     */
    public function test_user_column_contains(): void {
        $this->resetAfterTest();
        $ana = $this->getDataGenerator()->create_user(['institution' => 'Technische Universität']);
        $bob = $this->getDataGenerator()->create_user(['institution' => 'Other']);

        $condition = new \plugin_cuserfield((object) ['id' => 1, 'type' => 'users']);
        $result = $condition->execute((object) ['field' => 'institution', 'operator' => 'LIKE % %', 'value' => 'NISCH'], $ana);

        $this->assertContainsEquals($ana->id, $result);
        $this->assertNotContainsEquals($bob->id, $result);
    }

    /**
     * User field condition: "=" still matches exactly.
     */
    public function test_user_column_equals(): void {
        $this->resetAfterTest();
        $ana = $this->getDataGenerator()->create_user(['institution' => 'Technische']);
        $bob = $this->getDataGenerator()->create_user(['institution' => 'Technische Universität']);

        $condition = new \plugin_cuserfield((object) ['id' => 1, 'type' => 'users']);
        $result = $condition->execute((object) ['field' => 'institution', 'operator' => '=', 'value' => 'Technische'], $ana);

        $this->assertContainsEquals($ana->id, $result);
        $this->assertNotContainsEquals($bob->id, $result);
    }

    /**
     * User field condition: "contains" on a custom profile field matches substrings.
     */
    public function test_profile_field_contains(): void {
        global $DB;
        $this->resetAfterTest();

        $field = $this->getDataGenerator()->create_custom_profile_field(
            ['datatype' => 'text', 'shortname' => 'dept', 'name' => 'Department']);
        $ana = $this->getDataGenerator()->create_user();
        $bob = $this->getDataGenerator()->create_user();
        $DB->insert_record('user_info_data', ['userid' => $ana->id, 'fieldid' => $field->id, 'data' => 'Robotics']);
        $DB->insert_record('user_info_data', ['userid' => $bob->id, 'fieldid' => $field->id, 'data' => 'Sales']);

        $condition = new \plugin_cuserfield((object) ['id' => 1, 'type' => 'users']);
        $result = $condition->execute((object) ['field' => 'profile_dept', 'operator' => 'LIKE % %', 'value' => 'obo'], $ana);

        $this->assertEquals([$ana->id], array_values($result));
    }

    /**
     * Course field condition: "contains" matches substrings.
     */
    public function test_course_field_contains(): void {
        $this->resetAfterTest();
        $c1 = $this->getDataGenerator()->create_course(['fullname' => 'Introduction to Robotics']);
        $c2 = $this->getDataGenerator()->create_course(['fullname' => 'Accounting']);

        $condition = new \plugin_ccoursefield((object) ['id' => 1, 'type' => 'courses']);
        $result = $condition->execute((object) ['field' => 'fullname', 'operator' => 'LIKE % %', 'value' => 'robot']);

        $this->assertContainsEquals($c1->id, $result);
        $this->assertNotContainsEquals($c2->id, $result);
    }
}
