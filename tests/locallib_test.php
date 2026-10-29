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
 * Unit tests for the plugin's library functions
 *
 * @package    block_configurable_reports
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_configurable_reports;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/configurable_reports/locallib.php');

/**
 * Tests for cr_filter_date_to_timestamp()
 *
 * @package    block_configurable_reports
 * @covers     ::cr_filter_date_to_timestamp
 */
#[CoversFunction('cr_filter_date_to_timestamp')]
#[CoversFunction('cr_get_field_name')]
final class locallib_test extends advanced_testcase {

    /**
     * Core names some table columns and not others. The ones it does not name fall back to the column
     * itself, rather than reporting a debugging message and showing a bracketed identifier.
     */
    public function test_a_field_name_falls_back_to_the_column(): void {
        $this->assertSame(get_string('firstname'), cr_get_field_name('firstname'));
        $this->assertSame('id', cr_get_field_name('id'));
        $this->assertSame('sortorder', cr_get_field_name('sortorder'));
        $this->assertDebuggingNotCalled();
    }

    /**
     * A date selector posts day, month and year, and a date/time selector adds hour and minute.
     */
    public function test_the_posted_parts_make_a_timestamp(): void {
        $this->resetAfterTest();
        $this->setTimezone('UTC');

        $this->assertSame(
            make_timestamp(2026, 10, 29),
            cr_filter_date_to_timestamp(['day' => 29, 'month' => 10, 'year' => 2026])
        );

        $this->assertSame(
            make_timestamp(2026, 10, 29, 14, 30),
            cr_filter_date_to_timestamp(['day' => 29, 'month' => 10, 'year' => 2026, 'hour' => 14, 'minute' => 30])
        );
    }

    /**
     * Anything that is not a whole date is no filter at all, rather than a date built from missing parts.
     *
     * @param array $date
     */
    #[DataProvider('incomplete_dates_provider')]
    public function test_an_incomplete_date_is_no_date(array $date): void {
        $this->assertSame(0, cr_filter_date_to_timestamp($date));
    }

    /**
     * Data provider for test_an_incomplete_date_is_no_date.
     *
     * @return array[]
     */
    public static function incomplete_dates_provider(): array {
        return [
            'nothing posted' => [[]],
            'only the selector enabled' => [['enabled' => 1]],
            'no year' => [['day' => 29, 'month' => 10]],
            'no day' => [['month' => 10, 'year' => 2026]],
        ];
    }

}
