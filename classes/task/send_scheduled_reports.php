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

namespace block_configurable_reports\task;

/**
 * Scheduled task that emails configurable reports to their predefined recipients.
 *
 * @package    block_configurable_reports
 * @copyright  2027 Configurable Reports contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_scheduled_reports extends \core\task\scheduled_task {

    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('sendscheduledreports', 'block_configurable_reports');
    }

    /**
     * Find every report and every recurring combined-email bundle configured for automatic
     * emailing, and send whichever ones are due.
     */
    public function execute(): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/blocks/configurable_reports/locallib.php');

        $reports = $DB->get_records_select('block_configurable_reports', 'emailschedule > 0');

        foreach ($reports as $report) {
            if (trim((string) $report->emailto) === '') {
                continue;
            }

            if (!cr_report_email_is_due($report)) {
                continue;
            }

            mtrace("Sending scheduled report '{$report->name}' (id {$report->id})...");

            try {
                $sent = cr_send_scheduled_report_email($report);
                mtrace($sent ? '...OK' : '...skipped (no valid recipients)');
            } catch (\Throwable $e) {
                mtrace('...FAILED: ' . $e->getMessage());
            }
        }

        $bundles = $DB->get_records_select('block_configurable_reports_bundles', 'emailschedule > 0');

        foreach ($bundles as $bundle) {
            if (trim((string) $bundle->emailto) === '' || trim((string) $bundle->reportids) === '') {
                continue;
            }

            if (!cr_report_email_is_due($bundle)) {
                continue;
            }

            mtrace("Sending scheduled report bundle '{$bundle->name}' (id {$bundle->id})...");

            try {
                $sent = cr_send_scheduled_bundle_email($bundle);
                mtrace($sent ? '...OK' : '...skipped (no valid reports/recipients)');
            } catch (\Throwable $e) {
                mtrace('...FAILED: ' . $e->getMessage());
            }
        }
    }

}
