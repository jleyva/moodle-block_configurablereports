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
 * Unit tests for the scheduled report email helpers.
 *
 * @package    block_configurable_reports
 * @copyright  2027 Configurable Reports contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_configurable_reports;

use advanced_testcase;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/configurable_reports/locallib.php');

/**
 * Tests for the report scheduled-email helper functions in locallib.php.
 *
 * @package    block_configurable_reports
 */
class scheduled_report_email_test extends advanced_testcase {

    /**
     * cr_parse_email_recipients() should split, trim, de-duplicate and drop invalid addresses.
     */
    public function test_parse_email_recipients(): void {
        $input = "a@example.com\nB@Example.com, c@example.com; not-an-email\n\nd@example.com";

        $result = cr_parse_email_recipients($input);

        $this->assertCount(3, $result);
        $this->assertContains('a@example.com', $result);
        $this->assertContains('c@example.com', $result);
        $this->assertContains('d@example.com', $result);
    }

    /**
     * cr_parse_email_recipients() with no valid addresses returns an empty array.
     */
    public function test_parse_email_recipients_empty(): void {
        $this->assertSame([], cr_parse_email_recipients(''));
        $this->assertSame([], cr_parse_email_recipients("not-an-email\nalso not one"));
    }

    /**
     * cr_get_invalid_email_recipients() should report back only the malformed entries.
     */
    public function test_get_invalid_email_recipients(): void {
        $input = "a@example.com\nnot-an-email\nb@example.com";

        $invalid = cr_get_invalid_email_recipients($input);

        $this->assertSame(['not-an-email'], $invalid);
    }

    /**
     * A report with emailschedule disabled is never due.
     */
    public function test_email_not_due_when_disabled(): void {
        $report = new stdClass();
        $report->emailschedule = 0;
        $report->lastemailtime = 0;

        $this->assertFalse(cr_report_email_is_due($report));
    }

    /**
     * A report that has never been emailed is due immediately, for any enabled schedule.
     */
    public function test_email_due_when_never_sent(): void {
        $report = new stdClass();
        $report->emailschedule = 1;
        $report->lastemailtime = 0;

        $this->assertTrue(cr_report_email_is_due($report));
    }

    /**
     * Daily schedule is not due again until a full day has passed.
     */
    public function test_daily_schedule_due_dates(): void {
        $report = new stdClass();
        $report->emailschedule = 1;
        $report->lastemailtime = time() - HOURSECS;

        $this->assertFalse(cr_report_email_is_due($report));

        $report->lastemailtime = time() - DAYSECS - 1;

        $this->assertTrue(cr_report_email_is_due($report));
    }

    /**
     * Weekly schedule is not due again until a full week has passed.
     */
    public function test_weekly_schedule_due_dates(): void {
        $report = new stdClass();
        $report->emailschedule = 2;
        $report->lastemailtime = time() - DAYSECS;

        $this->assertFalse(cr_report_email_is_due($report));

        $report->lastemailtime = time() - WEEKSECS - 1;

        $this->assertTrue(cr_report_email_is_due($report));
    }

    /**
     * Monthly schedule is not due again until a calendar month has passed.
     */
    public function test_monthly_schedule_due_dates(): void {
        $report = new stdClass();
        $report->emailschedule = 3;
        $report->lastemailtime = time() - WEEKSECS;

        $this->assertFalse(cr_report_email_is_due($report));

        $report->lastemailtime = strtotime('-2 months');

        $this->assertTrue(cr_report_email_is_due($report));
    }
}
