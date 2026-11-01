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
 * Unit tests for the start and end time filter
 *
 * @package    block_configurable_reports
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_configurable_reports;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/configurable_reports/locallib.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/filter.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/plugin.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/components/filters/startendtime/plugin.class.php');

/**
 * Tests for plugin_startendtime
 *
 * @package    block_configurable_reports
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class startendtime_filter_test extends advanced_testcase {

    /** @var \plugin_startendtime The filter under test */
    private \plugin_startendtime $filter;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $this->filter = new \plugin_startendtime((object) [
            'id' => 1,
            'name' => 'Timed',
            'type' => 'sql',
            'components' => '',
        ]);

        // Both ends have to be submitted before the filter does anything at all.
        $date = ['year' => 2026, 'month' => 10, 'day' => 29, 'hour' => 0, 'minute' => 0];
        $_POST['filter_starttime'] = $date;
        $_POST['filter_endtime'] = $date;
    }

    protected function tearDown(): void {
        unset($_POST['filter_starttime'], $_POST['filter_endtime']);
        parent::tearDown();
    }

    /**
     * A placeholder names the column and the operator to compare it with.
     */
    public function test_the_placeholder_becomes_a_bound_comparison(): void {
        [$sql, $params] = $this->filter->execute_for_sql_report(
            'SELECT id FROM {user} WHERE 1 = 1 %%FILTER_STARTTIME:timecreated:>%% %%FILTER_ENDTIME:timecreated:<%%'
        );

        $this->assertStringContainsString('timecreated > :filterstarttime', $sql);
        $this->assertStringContainsString('timecreated < :filterendtime', $sql);
        $this->assertArrayHasKey('filterstarttime', $params);
        $this->assertArrayHasKey('filterendtime', $params);
    }

    /**
     * Issue #207 reported this for the user field filter, where it is fixed: splitting a placeholder
     * that carries no operator leaves nothing at the second offset, and reading it raises a warning
     * before the filter gets as far as refusing the operator. The report author sees the warning,
     * which says nothing about what they got wrong.
     */
    public function test_a_placeholder_without_an_operator_is_refused_quietly(): void {
        $this->expectException(\moodle_exception::class);

        $this->filter->execute_for_sql_report('SELECT id FROM {user} WHERE 1 = 1 %%FILTER_STARTTIME:timecreated%%');
    }

    /**
     * The same, for the end of the range.
     */
    public function test_an_end_placeholder_without_an_operator_is_refused_quietly(): void {
        $this->expectException(\moodle_exception::class);

        $this->filter->execute_for_sql_report('SELECT id FROM {user} WHERE 1 = 1 %%FILTER_ENDTIME:timecreated%%');
    }

}
