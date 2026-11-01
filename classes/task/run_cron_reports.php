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

use core\task\scheduled_task;
use report_sql;

/**
 * Run the SQL reports flagged as "Auto run daily", once a day after the configured time.
 *
 * @package    block_configurable_reports
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class run_cron_reports extends scheduled_task {

    /**
     * Get the task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('taskruncronreports', 'block_configurable_reports');
    }

    /**
     * Run the reports if the configured time of the day has passed and they did not run yet today.
     */
    public function execute(): void {
        global $CFG, $DB;

        $now = time();
        $date = usergetdate($now);
        $hour = (int) get_config('block_configurable_reports', 'cron_hour');
        $minute = (int) get_config('block_configurable_reports', 'cron_minute');
        $runtime = make_timestamp($date['year'], $date['mon'], $date['mday'], $hour, $minute);

        $lastrun = (int) get_config('block_configurable_reports', 'lastcronrun');
        if ($now < $runtime || $lastrun >= $runtime) {
            return;
        }
        set_config('lastcronrun', $now, 'block_configurable_reports');

        require_once($CFG->dirroot . '/blocks/configurable_reports/locallib.php');
        require_once($CFG->dirroot . '/blocks/configurable_reports/report.class.php');
        require_once($CFG->dirroot . '/blocks/configurable_reports/reports/sql/report.class.php');

        $reports = $DB->get_records('block_configurable_reports', ['type' => 'sql', 'cron' => 1]);
        foreach ($reports as $report) {
            $reportclass = new report_sql($report);
            $starttime = microtime(true);
            mtrace("Executing query '{$report->name}'");

            $components = cr_unserialize($reportclass->config->components);
            $config = $components['customsql']['config'] ?? (object) [];
            if (empty($config->querysql)) {
                continue;
            }
            [$sql, $params] = $reportclass->prepare_sql($config->querysql);

            foreach (array_filter(array_map('trim', explode(';', $sql))) as $query) {
                mtrace('  ' . substr($query, 0, 60));
                try {
                    $results = $reportclass->execute_query($query, $params);
                    if ($results instanceof \moodle_recordset) {
                        $results->close();
                    }
                    mtrace('  ...OK time=' . round((microtime(true) - $starttime) * 1000) . 'mSec');
                } catch (\moodle_exception $e) {
                    mtrace('  ...Error: ' . $e->getMessage());
                }
            }
        }
    }
}
