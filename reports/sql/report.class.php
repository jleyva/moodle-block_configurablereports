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
 * Configurable Reports
 * A Moodle block for creating customizable reports
 * @package blocks
 * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @date: 2009
 */

defined('BLOCK_CONFIGURABLE_REPORTS_MAX_RECORDS') || define('BLOCK_CONFIGURABLE_REPORTS_MAX_RECORDS', 5000);

if (!class_exists('component_tilereport')) {
    global $CFG;
    require_once($CFG->dirroot.'/blocks/configurable_reports/components/tilereport/component.class.php');
}

class report_sql extends report_base {

    private $forExport = false;

    public function setForExport(bool $isForExport) {
        $this->forExport = $isForExport;
    }

    public function isForExport() {
        return $this->forExport;
    }

    public function init() {
        $this->components = array('customsql', 'filters', 'template', 'permissions', 'calcs', 'plot', 'tilereport');
    }

    public function prepare_sql($sql) {
        global $DB, $USER, $CFG, $COURSE;

        // Enable debug mode from SQL query.
        $this->config->debug = (strpos($sql, '%%DEBUG%%') !== false) ? true : false;

        // Pass special custom undefined variable as filter.
        // Security warning !!! can be used for sql injection.
        // Use %%FILTER_VAR%% in your sql code with caution.
        $filtervar = optional_param('filter_var', '', PARAM_RAW);
        if (!empty($filtervar)) {
            $sql = str_replace('%%FILTER_VAR%%', $filtervar, $sql);
        }

        $sql = str_replace('%%USERID%%', $USER->id, $sql);
        $sql = str_replace('%%COURSEID%%', $COURSE->id, $sql);
        $sql = str_replace('%%CATEGORYID%%', $COURSE->category, $sql);

        // See http://en.wikipedia.org/wiki/Year_2038_problem.
        $sql = str_replace(array('%%STARTTIME%%', '%%ENDTIME%%'), array('0', '2145938400'), $sql);
        $sql = str_replace('%%WWWROOT%%', $CFG->wwwroot, $sql);
        $sql = preg_replace('/%{2}[^%]+%{2}/i', '', $sql);

        $sql = str_replace('?', '[[QUESTIONMARK]]', $sql);

        return $sql;
    }

    public function execute_query($sql, $limittoonerecord = false) {
        global $remotedb, $DB, $CFG;

        $sql = preg_replace('/\bprefix_(?=\w+)/i', $CFG->prefix, $sql);

        if ($limittoonerecord) {
            $reportlimit = 1;
        } else {
            $reportlimit = get_config('block_configurable_reports', 'reportlimit');
            if (empty($reportlimit) or $reportlimit == '0') {
                    $reportlimit = BLOCK_CONFIGURABLE_REPORTS_MAX_RECORDS;
            }
        }

        $starttime = microtime(true);

        if (preg_match('/\b(INSERT|INTO|CREATE)\b/i', $sql) && !empty($CFG->block_configurable_reports_enable_sql_execution)) {
            // Run special (dangerous) queries directly.
            $results = $remotedb->execute($sql);
        } else {
            $results = $remotedb->get_recordset_sql($sql, null, 0, $reportlimit);
        }

        // Update the execution time in the DB.
        $updaterecord = $DB->get_record('block_configurable_reports', array('id' => $this->config->id));
        $updaterecord->lastexecutiontime = round((microtime(true) - $starttime) * 1000);
        $this->config->lastexecutiontime = $updaterecord->lastexecutiontime;

        $DB->update_record('block_configurable_reports', $updaterecord);

        return $results;
    }

    public function create_report($limittoonerecord = false) {
        global $DB, $CFG;

        $components = cr_unserialize($this->config->components);

        $filters = (isset($components['filters']['elements'])) ? $components['filters']['elements'] : array();
        $calcs = (isset($components['calcs']['elements'])) ? $components['calcs']['elements'] : array();

        $tablehead = array();
        $finalcalcs = array();
        $finaltable = array();
        $tablehead = array();

        $components = cr_unserialize($this->config->components);
        $config = (isset($components['customsql']['config'])) ? $components['customsql']['config'] : new \stdclass;
        $totalrecords = 0;

        $reportconfig = cr_get_tilereport_config($this->config);

        $sql = '';
        if (isset($config->querysql)) {
            // Filters.
            $sql = $config->querysql;
            if (!empty($filters)) {
                foreach ($filters as $f) {
                    require_once($CFG->dirroot.'/blocks/configurable_reports/components/filters/'.$f['pluginname'].'/plugin.class.php');
                    $classname = 'plugin_'.$f['pluginname'];
                    $class = new $classname($this->config);
                    $sql = $class->execute($sql, $f['formdata']);
                }
            }

            if (isset($reportconfig->summaryoptions) && $reportconfig->summaryoptions == component_tilereport::SUMMARY_CUSTOM) {
                // Inject our custom summary. Todo: Does the alias need to be unique?
                $displaycolumn      = $reportconfig->displaycolumn;
                $evaluationcolumn   = $reportconfig->evaluationcolumn;
                $evaluation         = $reportconfig->evaluation == component_tilereport::EVALUATION_LOWEST ? 'ASC' : 'DESC';

                // Error handling.
                if (empty($displaycolumn) || empty($evaluationcolumn)) {
                    // These better exist!
                    $reportid   = $this->config->id;
                    $reportname = $this->config->name;

                    $reporturl  = new \moodle_url('/blocks/configurable_reports/editcomp.php', ['id' => $reportid, 'comp' => 'tilereport']);
                    $okstring   = get_string('ok', 'block_configurable_reports');
                    $fixstring  = get_string('fix', 'block_configurable_reports');

                    $a = [
                        'displaycolumn'     => empty($displaycolumn) ? $fixstring : $okstring,
                        'evaluationcolumn'  => empty($evaluationcolumn) ? $fixstring : $okstring,
                        'report'            => \html_writer::link($reporturl, $reportname)
                    ];
                    \core\notification::error(get_string('customsummary:invalidconfig', 'block_configurable_reports', $a));
                } else {
                    $sql = "SELECT `{$displaycolumn}`, `{$evaluationcolumn}` FROM ($sql) AS temptable ORDER BY `{$evaluationcolumn}` {$evaluation}";
                }

            }

            $sql = $this->prepare_sql($sql);

            if ($rs = $this->execute_query($sql, $limittoonerecord)) {
                foreach ($rs as $row) {
                    if (empty($finaltable)) {
                        foreach ($row as $colname => $value) {
                            $tablehead[] = $colname;
                        }
                    }
                    $arrayrow = array_values((array) $row);
                    foreach ($arrayrow as $ii => $cell) {
                        if (!$this->isForExport()) {
                            $cell = format_text($cell, FORMAT_HTML, array('trusted' => true, 'noclean' => true, 'para' => false));
                        }
                        $arrayrow[$ii] = str_replace('[[QUESTIONMARK]]', '?', $cell);
                    }
                    $totalrecords++;
                    $finaltable[] = $arrayrow;
                }
            }
        }
        $this->sql = $sql;

        $this->totalrecords = $totalrecords;

        // Calcs.

        $finalcalcs = $this->get_calcs($finaltable, $tablehead);

        $table = new \stdclass;
        $table->id = 'reporttable';
        $table->data = $finaltable;
        $table->head = $tablehead;

        $calcs = new \html_table();
        $calcs->id = 'calcstable';
        $calcs->data = array($finalcalcs);
        $calcs->head = $tablehead;

        if (!$this->finalreport) {
            $this->finalreport = new \stdClass;
        }
        $this->finalreport->table = $table;
        $this->finalreport->calcs = $calcs;

        return true;
    }

}

