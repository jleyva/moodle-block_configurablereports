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
 * Unit tests for fetching the rows a report ended up with
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
require_once($CFG->dirroot . '/blocks/configurable_reports/reports/users/report.class.php');

/**
 * A users report that reaches the second path after two ids rather than thirty thousand.
 *
 * @package    block_configurable_reports
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testable_report_users extends \report_users {

    /** @var int Lowered so a test can reach the path a large site takes. */
    const MAX_BOUND_IDS = 2;

}

/**
 * Tests for report_base::get_rows_by_ids()
 *
 * @package    block_configurable_reports
 * @covers     \report_base::get_rows_by_ids
 */
#[CoversClass(\report_users::class)]
final class get_rows_test extends advanced_testcase {

    /** @var int[] Ids of the test users, in the order they were made */
    private array $ids = [];

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        foreach (['Carol', 'Alice', 'Bob'] as $firstname) {
            $this->ids[] = (int) $this->getDataGenerator()->create_user(['firstname' => $firstname])->id;
        }
    }

    /**
     * A report's own configuration, which report_base needs to construct.
     *
     * @return \stdClass
     */
    private function config(): \stdClass {
        global $SITE, $USER;

        return (object) [
            'id' => 0,
            'courseid' => $SITE->id,
            'ownerid' => $USER->id,
            'type' => 'users',
            'components' => '',
            'remote' => 0,
            'global' => 1,
        ];
    }

    /**
     * CONTRIB-8780: a site with more users than the database will bind parameters for still has to
     * get the same rows, in the same order, as one small enough for a single IN clause.
     */
    public function test_both_paths_return_the_same_rows_in_the_same_order(): void {
        $bound = (new \report_users($this->config()))->get_rows($this->ids, 'firstname ASC');
        $scanned = (new testable_report_users($this->config()))->get_rows($this->ids, 'firstname ASC');

        $this->assertSame(['Alice', 'Bob', 'Carol'], array_column($bound, 'firstname'));
        $this->assertSame(array_keys($bound), array_keys($scanned));
        $this->assertSame(array_column($bound, 'firstname'), array_column($scanned, 'firstname'));
    }

    /**
     * The second path reads the whole table, so it has to keep only the rows it was asked for:
     * the site administrator and the guest are users too.
     */
    public function test_the_second_path_returns_only_the_elements_asked_for(): void {
        $scanned = (new testable_report_users($this->config()))->get_rows($this->ids, 'firstname ASC');

        $this->assertSame([], array_diff(array_keys($scanned), $this->ids));
        $this->assertCount(count($this->ids), $scanned);
    }

    /**
     * No elements is no query and no rows, on either path.
     */
    public function test_no_elements_returns_nothing(): void {
        $this->assertSame([], (new \report_users($this->config()))->get_rows([], 'firstname ASC'));
        $this->assertSame([], (new testable_report_users($this->config()))->get_rows([], 'firstname ASC'));
    }

}
