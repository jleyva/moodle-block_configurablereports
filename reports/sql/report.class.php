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
 * Configurable Reports a Moodle block for creating customizable reports
 *
 * @copyright  2020 Juan Leyva <juan@moodle.com>
 * @package    block_configurable_reports
 * @author     Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
defined('BLOCK_CONFIGURABLE_REPORTS_MAX_RECORDS') || define('BLOCK_CONFIGURABLE_REPORTS_MAX_RECORDS', 5000);

/**
 * Class report_sql
 *
 * @package   block_configurable_reports
 * @author    Juan leyva <http://www.twitter.com/jleyvadelgado>
 */
class report_sql extends report_base {

    /**
     * @var bool
     */
    private bool $forexport = false;

    /**
     * @var int The row limit the last query ran under, or 0 if it ran unlimited.
     */
    private int $reportlimit = 0;

    /**
     * set_forexport
     *
     * @param bool $isforexport
     * @return void
     */
    public function set_forexport(bool $isforexport): void {
        $this->forexport = $isforexport;
    }

    /**
     * Check the query against the keywords allowed by the SQL security setting.
     *
     * @param string $sql
     * @return string|null The error string identifier, or null if the query is allowed.
     */
    public static function get_query_security_error(string $sql): ?string {
        global $CFG;

        if (get_config('block_configurable_reports', 'sqlsecurity')) {
            if (preg_match('/\b(ALTER|CREATE|DELETE|DROP|GRANT|INSERT|INTO|TRUNCATE|UPDATE|SET|VACUUM|REINDEX|DISCARD|LOCK)\b/i', $sql)) {
                return 'notallowedwords';
            }
            if (strpos($sql, ';') !== false) {
                return 'nosemicolon';
            }

            return null;
        }

        if (preg_match('/\b(ALTER|DELETE|DROP|GRANT|TRUNCATE|UPDATE|SET|VACUUM|REINDEX|DISCARD|LOCK)\b/i', $sql)) {
            return 'notallowedwords';
        }
        // Only allow INSERT|INTO|CREATE in low security when SQL execution is enabled in the server.
        if (preg_match('/\b(INSERT|INTO|CREATE)\b/i', $sql) && empty($CFG->block_configurable_reports_enable_sql_execution)) {
            return 'notallowedwords';
        }

        return null;
    }

    /**
     * Check whether the query names a table by its real name instead of through the prefix_ placeholder.
     *
     * Only the prefix followed by the name of a table that exists counts. Treating the prefix followed by any
     * word as a table reads "month" as one wherever the prefix is a single letter, which is what sites on
     * Oracle use: its thirty character limit on identifiers leaves little room for a longer one.
     *
     * @param string $sql
     * @return bool
     */
    public static function uses_explicit_prefix(string $sql): bool {
        global $CFG, $DB;

        if ($CFG->prefix === '') {
            return false;
        }

        if (!preg_match_all('/\b' . preg_quote($CFG->prefix, '/') . '(\w+)\b/i', $sql, $matches)) {
            return false;
        }

        $tables = $DB->get_tables();
        foreach ($matches[1] as $name) {
            if (isset($tables[strtolower($name)])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cast the %%FILTER_VAR%% value, so numbers are bound as numbers (needed in arithmetic on PostgreSQL).
     *
     * @param string $value
     * @return int|float|string
     */
    protected static function cast_filter_var(string $value) {
        $value = trim($value);
        if (preg_match('/^-?\d{1,18}$/', $value)) {
            return (int) $value;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }

        return $value;
    }

    /**
     * is_forexport
     *
     * @return bool
     */
    public function is_forexport(): bool {
        return $this->forexport;
    }

    /**
     * Init
     *
     * @return void
     */
    public function init(): void {
        $this->components = [
            'customsql',
            'filters',
            'template',
            'permissions',
            'calcs',
            'plot',
        ];
    }

    /**
     * prepare_sql
     *
     * @param string $sql
     * @return array
     */
    public function prepare_sql(string $sql): array {
        global $USER, $CFG, $COURSE;

        $components = cr_unserialize($this->config->components);
        $filters = $components['filters']['elements'] ?? [];

        $params = [];

        if (!empty($filters)) {;
            foreach ($filters as $f) {
                $classname = cr_require_component_plugin('filters', $f['pluginname']);
                $class = new $classname($this->config);
                [$filtersql, $filterparams] = $class->execute_for_sql_report($sql, $f['formdata']);
                $sql = $filtersql;
                $params = array_merge($params, $filterparams);
            }
        }

        // Enable debug mode from SQL query.
        $this->config->debug = strpos($sql, '%%DEBUG%%') !== false;

        // %%FILTER_VAR%% is replaced with the filter_var URL parameter, always bound as a value, so it can be used
        // anywhere a value is valid: "WHERE cm.id = %%FILTER_VAR%%", "<= %%FILTER_VAR%% * 86400"...
        $filtervar = trim(optional_param('filter_var', '', PARAM_RAW));
        $i = 0;
        $sql = preg_replace_callback('/%%FILTER_VAR%%/', function(array $match) use (&$i, &$params, $filtervar, $sql): string {
            if ($filtervar === '') {
                // Without a value, a placeholder used as a value is NULL, so the report shows no rows instead of
                // failing. Anywhere else it is removed, as in reports that add a whole condition with it.
                $before = substr($sql, 0, $match[0][1]);
                return preg_match('/(?:[=<>!(,+\-*\/|]|\b(?:LIKE|IN|BETWEEN|THEN|ELSE|WHEN))\s*$/i', $before) ? 'NULL' : '';
            }
            $name = 'filtervar' . ++$i;
            $params[$name] = self::cast_filter_var($filtervar);
            return ':' . $name;
        }, $sql, -1, $count, PREG_OFFSET_CAPTURE);

        // See http://en.wikipedia.org/wiki/Year_2038_problem.
        $sql = str_replace([
            '%%USERID%%',
            '%%COURSEID%%',
            '%%CATEGORYID%%',
            '%%STARTTIME%%',
            '%%ENDTIME%%',
            '%%WWWROOT%%',
        ],
            [$USER->id, $COURSE->id, $COURSE->category, '0', '2145938400', $CFG->wwwroot],
            $sql);

        $sql = preg_replace('/%{2}[^%]+%{2}/i', '', $sql);
        $sql = str_replace('?', '[[QUESTIONMARK]]', $sql);
        return [$sql, $params];
    }

    /**
     * execute_query
     *
     * @param string $sql
     * @param array $params
     * @return mixed
     */
    public function execute_query($sql, $params = []) {
        global $DB, $CFG;

        // Imported reports do not go through the form validation, so check the query every time it runs.
        if ($error = self::get_query_security_error($sql)) {
            throw new moodle_exception($error, 'block_configurable_reports');
        }

        $sql = preg_replace('/\bprefix_(?=\w+)/i', $CFG->prefix, $sql);

        $reportlimit = get_config('block_configurable_reports', 'reportlimit');
        if (empty($reportlimit) || $reportlimit == '0') {
            $reportlimit = BLOCK_CONFIGURABLE_REPORTS_MAX_RECORDS;
        }

        $starttime = microtime(true);

        if (preg_match('/\b(INSERT|INTO|CREATE)\b/i', $sql) && !empty($CFG->block_configurable_reports_enable_sql_execution)) {
            // Run special (dangerous) queries directly.
            $this->reportlimit = 0;
            $results = $DB->execute($sql, $params);
        } else {
            // Ask for one row more than we will show: if it comes back, the limit cut the result set short.
            $this->reportlimit = $reportlimit;
            $results = $DB->get_recordset_sql($sql, $params, 0, $reportlimit + 1);
        }

        // Update the execution time in the DB.
        $updaterecord = $DB->get_record('block_configurable_reports', ['id' => $this->config->id]);
        $updaterecord->lastexecutiontime = round((microtime(true) - $starttime) * 1000);
        $this->config->lastexecutiontime = $updaterecord->lastexecutiontime;

        $DB->update_record('block_configurable_reports', $updaterecord);

        return $results;
    }

    /**
     * create_report
     *
     * @return bool
     */
    public function create_report(): bool {
        $components = cr_unserialize($this->config->components);

        $calcs = $components['calcs']['elements'] ?? [];

        $tablehead = [];
        $finalcalcs = [];
        $finaltable = [];

        $config = $components['customsql']['config'] ?? new stdClass;
        $totalrecords = 0;

        $sql = '';
        $params = [];
        if (isset($config->querysql)) {
            [$sql, $params] = $this->prepare_sql($config->querysql);

            try {
                $rs = $this->execute_query($sql, $params);
            } catch (dml_read_exception $e) {
                // Databases like PostgreSQL refuse comparing a number with text, so a filter_var value of the
                // wrong type is an error there. Say so instead of showing a database error.
                if (trim(optional_param('filter_var', '', PARAM_RAW)) !== '' && strpos($config->querysql, '%%FILTER_VAR%%') !== false) {
                    throw new moodle_exception('invalidfiltervar', 'block_configurable_reports', '', null, $e->getMessage());
                }
                throw $e;
            }

            if ($rs) {
                $cellcontext = ($this->config->courseid == SITEID) ? context_system::instance() :
                    context_course::instance($this->config->courseid, IGNORE_MISSING);
                foreach ($rs as $row) {
                    if (empty($finaltable)) {
                        foreach ($row as $colname => $value) {
                            $tablehead[] = $colname;
                        }
                    }
                    $arrayrow = array_values((array) $row);
                    foreach ($arrayrow as $ii => $cell) {
                        // A NULL column reaches the export path unformatted, and str_replace() will not take null.
                        $cell ??= '';
                        if (!$this->is_forexport()) {
                            $cell = format_text($cell, FORMAT_HTML, ['para' => false, 'context' => $cellcontext ?: context_system::instance()]);
                        }
                        $arrayrow[$ii] = str_replace('[[QUESTIONMARK]]', '?', $cell);
                    }
                    $totalrecords++;
                    $finaltable[] = $arrayrow;
                }
            }
        }

        // The query asked for one row past the limit, so an extra row here means the rest were never fetched.
        $this->truncated = $this->reportlimit && $totalrecords > $this->reportlimit;
        if ($this->truncated) {
            array_pop($finaltable);
            $totalrecords--;
            $this->rowlimit = $this->reportlimit;
        }

        $this->sql = $sql;
        $this->totalrecords = $totalrecords;

        // Calcs.

        $finalcalcs = $this->get_calcs($finaltable, $tablehead);

        $table = new stdClass;
        $table->id = 'reporttable';
        $table->data = $finaltable;
        $table->head = $tablehead;

        $calcs = new html_table();
        $calcs->id = 'calcstable';
        $calcs->data = [$finalcalcs];
        $calcs->head = $tablehead;

        if (!$this->finalreport) {
            $this->finalreport = new stdClass;
        }
        $this->finalreport->name = $this->config->name;
        $this->finalreport->table = $table;
        $this->finalreport->calcs = $calcs;

        return true;
    }
}
