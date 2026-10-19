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
     * Check whether viewers can filter on a column using %%FILTER_VAR%%.
     *
     * @param string $field The left side of the clause, a column optionally wrapped in a function.
     * @param string[] $allowedcolumns Columns declared by the report author, empty if none.
     * @return bool
     */
    protected function is_filter_var_column_allowed(string $field, array $allowedcolumns): bool {
        preg_match('/([a-z_][a-z0-9_]*(?:\.[a-z_][a-z0-9_]*)?)\s*\)?\s*$/i', $field, $matches);
        $column = strtolower($matches[1] ?? '');
        $name = substr(strrchr('.' . $column, '.'), 1);

        // Never allow filtering on credentials, even if the author did not declare the columns.
        if ($name === '' || in_array($name, ['password', 'secret', 'sesskey', 'sid', 'token', 'privatetoken'], true)) {
            return false;
        }

        if (empty($allowedcolumns)) {
            return true;
        }

        return in_array($column, $allowedcolumns, true);
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

        // Pass special custom undefined variable as filter.
        // E.g. "AND id = 20"
        // PARAM_RAW like 5.2: PARAM_TEXT strips "<..." as if it were an HTML tag, breaking "<", "<=" and "<>".
        // The value is bound as a parameter below.
        $filtervar = optional_param('filter_var', '', PARAM_RAW);
        // Authors can limit the columns viewers may filter on with "%%FILTER_VAR:u.city,u.department%%".
        if (preg_match('/%%FILTER_VAR(?::([^%]*))?%%/', $sql, $placeholder)) {
            $allowedcolumns = isset($placeholder[1]) ? array_filter(array_map('trim', explode(',', strtolower($placeholder[1])))) : [];
            // Make sure the filtervar is a valid sql clause, otherwise remove from sql.
            $clause = '';
            // The left side comes from the URL and is inserted as SQL, so it must be a plain column
            // ("id", "u.city"), optionally wrapped in one whitelisted function ("LOWER(u.city)").
            // Only the value is free text, and it is always bound as a parameter.
            $identifier = '[a-z_][a-z0-9_]*(?:\.[a-z_][a-z0-9_]*)?';
            $field = "(?:(?:LOWER|UPPER|TRIM|LENGTH)\s*\(\s*{$identifier}\s*\)|{$identifier})";
            // Multi-character operators first ("<=" must not match as "<"), and word operators need
            // word boundaries ("AND institution = x" must not match "in").
            if (
                !empty($filtervar)
                && preg_match(
                    '/^\s*(?<conj>AND|OR)\s+(?<field>' . $field . ')\s*' .
                    '(?<op><=|>=|<>|!=|=|<|>|\bNOT\s+LIKE\b|\bLIKE\b|\bNOT\s+IN\b|\bIN\b|\bBETWEEN\b)\s*' .
                    '(?<param>\S(?:.*\S)?)\s*$/is',
                    $filtervar,
                    $output,
                )
                && $this->is_filter_var_column_allowed($output['field'], $allowedcolumns)
            ) {
                global $DB;
                $output['clause'] = strtoupper($output['conj']) . ' ' . $output['field'];
                $operator = strtoupper(preg_replace('/\s+/', ' ', $output['op']));
                // If a value has surrounding speechmarks, remove them.
                $unquote = function(?string $value): string {
                    $value = trim((string) $value);
                    return preg_match('/^["\'](.*)["\']$/s', $value, $matches) ? $matches[1] : $value;
                };
                if (in_array($operator, ['IN', 'NOT IN'], true) && preg_match('/^\((.*)\)$/s', $output['param'], $list)) {
                    // "AND id IN (2,3)": one bound param per value.
                    $values = array_map($unquote, str_getcsv($list[1], ',', "'", '\\'));
                    [$insql, $inparams] = $DB->get_in_or_equal($values, SQL_PARAMS_NAMED, 'filtervar', $operator === 'IN');
                    $clause = $output['clause'] . ' ' . $insql;
                    $params = array_merge($params, $inparams);
                } else if ($operator === 'BETWEEN' && preg_match('/^(.+?)\s+AND\s+(.+)$/is', $output['param'], $range)) {
                    $clause = $output['clause'] . ' BETWEEN :filtervarfrom AND :filtervarto';
                    $params['filtervarfrom'] = $unquote($range[1]);
                    $params['filtervarto'] = $unquote($range[2]);
                } else {
                    // Add clause to sql and param to params array.
                    $clause = $output['clause'] . " {$operator} :filtervar";
                    $params['filtervar'] = $unquote($output['param']);
                }
            }
            $sql = preg_replace('/%%FILTER_VAR(?::[^%]*)?%%/', $clause, $sql);
        }

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
            $results = $DB->execute($sql, $params);
        } else {
            $results = $DB->get_recordset_sql($sql, $params, 0, $reportlimit);
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

            if ($rs = $this->execute_query($sql, $params)) {
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
