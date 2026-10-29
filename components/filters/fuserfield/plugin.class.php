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
require_once($CFG->dirroot . '/blocks/configurable_reports/filter.class.php');

/**
 * Class plugin_fuserfield
 *
 * @package   block_configurable_reports
 * @author    Juan leyva <http://www.twitter.com/jleyvadelgado>
 */
class plugin_fuserfield extends filter_base {

    /**
     * Init
     *
     * @return void
     */
    public function init(): void {
        $this->form = true;
        $this->unique = false;
        $this->fullname = get_string('fuserfield', 'block_configurable_reports');
        $this->reporttypes = ['users', 'sql'];
    }

    /**
     * Summary
     *
     * @param object $data
     * @return string
     */
    public function summary(object $data): string {
        return $data->field;
    }

    /**
     * Execute
     *
     * @param string $finalelements
     * @param object $data
     * @return array|int[]|mixed|string|string[]
     */
    public function execute($finalelements, $data) {
        return $this->execute_users($finalelements, $data);
    }

    /**
     * Read the value the viewer picked from the filter.
     *
     * The options are base64 encoded so that any field value can serve as an option value, but PARAM_BASE64 is
     * meant for PEM data and rejects a single line of more than 64 characters, which is any value over 48 bytes.
     * Decode strictly instead, which is the check that actually matters here.
     *
     * @param string $field The user field the filter is on.
     * @return string The value picked, or '' when nothing valid was submitted.
     */
    private function get_submitted_value(string $field): string {
        $submitted = optional_param('filter_fuserfield_' . $field, '', PARAM_RAW);
        $decoded = $submitted === '' ? false : base64_decode($submitted, true);

        return $decoded === false ? '' : $decoded;
    }

    #[\Override]
    function execute_for_sql_report(string $sql, ?\stdClass $data = null): array {
        global $remotedb;

        $filter = $this->get_submitted_value($data->field);

        if (strpos($data->field, 'profile_') === 0) {
            // Custom profile fields are stored as text in user_info_data.
            $shortname = substr($data->field, strlen('profile_'));
            if (!$remotedb->record_exists('user_info_field', ['shortname' => $shortname])) {
                throw new moodle_exception('nosuchcolumn');
            }
            $metatype = 'X';
        } else {
            $columns = $remotedb->get_columns('user');
            $column = $columns[$data->field] ?? null;
            if (!$column) {
                throw new moodle_exception('nosuchcolumn');
            }
            // Use Moodle's DB-independent meta type: the native type differs per database
            // (e.g. tinyint/longtext on MySQL are not "int"/"text").
            $metatype = $column->__get('meta_type');
        }

        // The expression the match uses, so that an empty filter removes exactly what a filled one replaces.
        $filtermatch = 'FILTER_USERS(_' . preg_quote($data->field, '/') . ')?';

        if ($filter !== '') {
            switch ($metatype) {
                case 'I':
                case 'R':
                    if (!is_numeric($filter)) {
                        throw new moodle_exception('invalidfiltervalue');
                    }
                    $filter = clean_param($filter, PARAM_INT);
                    break;
                case 'N':
                case 'F':
                    if (!is_numeric($filter)) {
                        throw new moodle_exception('invalidfiltervalue');
                    }
                    $filter = clean_param($filter, PARAM_FLOAT);
                    break;
                case 'C':
                case 'X':
                    if (!is_string($filter)) {
                        throw new moodle_exception('invalidfiltervalue');
                    }
                    break;
                default:
                    throw new moodle_exception('invalidfiltervalue');
            }
        } else {
            $sql = preg_replace("/%%{$filtermatch}:[^%]+%%/i", '', $sql);
            return [$sql, []];
        }

        return $this->sql_replace($filter, $filtermatch, $sql);
    }

    /**
     * execute_users
     *
     * @param string $finalelements
     * @param object $data
     * @return array|int[]|mixed|string[]
     */
    private function execute_users($finalelements, $data) {
        global $remotedb;

        $filter = $this->get_submitted_value($data->field);
        if ($filter !== '') {

            if (strpos($data->field, 'profile_') === 0) {
                $conditions = ['shortname' => str_replace('profile_', '', $data->field)];
                if ($fieldid = $remotedb->get_field('user_info_field', 'id', $conditions)) {
                    // sql_like() returns a string, not [sql, params]. "Contains" like 5.2.
                    $likesql = $remotedb->sql_like('data', ':data', false);
                    [$insql, $inparams] = $remotedb->get_in_or_equal($finalelements, SQL_PARAMS_NAMED);
                    $sql = "fieldid = :fieldid AND {$likesql} AND userid $insql";
                    $params = array_merge(['fieldid' => $fieldid, 'data' => '%' . $filter . '%'], $inparams);

                    if ($infodata = $remotedb->get_records_select('user_info_data', $sql, $params)) {
                        $finalusersid = [];
                        foreach ($infodata as $d) {
                            $finalusersid[] = $d->userid;
                        }

                        return $finalusersid;
                    }
                }
            } else {
                [$insql, $inparams] = $remotedb->get_in_or_equal($finalelements, SQL_PARAMS_NAMED);
                // sql_like() returns a string, not [sql, params]. "Contains" like 5.2.
                $likesql = $remotedb->sql_like($data->field, ':data', false);
                $sql = "{$likesql} AND id {$insql}";
                $params = array_merge(['data' => '%' . $filter . '%'], $inparams);
                if ($elements = $remotedb->get_records_select('user', $sql, $params)) {
                    $finalelements = array_keys($elements);
                }
            }
        }

        return $finalelements;
    }

    /**
     * Print filter
     *
     * @param MoodleQuickForm $mform
     * @param bool|object $formdata
     * @return void
     */
    public function print_filter(MoodleQuickForm $mform, $formdata = false): void {

        global $remotedb;

        $columns = $remotedb->get_columns('user');
        $filteroptions = [];
        $filteroptions[''] = get_string('filter_all', 'block_configurable_reports');

        $usercolumns = [];
        foreach ($columns as $c) {
            $usercolumns[$c->name] = $c->name;
        }

        if ($profile = $remotedb->get_records('user_info_field')) {
            foreach ($profile as $p) {
                $usercolumns['profile_' . $p->shortname] = $p->name;
            }
        }

        foreach (cr_get_sensitive_user_columns() as $column) {
            unset($usercolumns[$column]);
        }

        if (!isset($usercolumns[$formdata->field])) {
            throw new moodle_exception('nosuchcolumn');
        }

        // A SQL report covers every user, so there is no list to narrow the values by. Asking for one
        // loads the whole user table and then binds a parameter per row, which hangs a large site and
        // can pass the database's own limit on parameters, so only the other report types build it.
        $userlist = null;
        if ($this->report->type !== 'sql') {
            $reportclassname = 'report_' . $this->report->type;
            $reportclass = new $reportclassname($this->report);
            $components = cr_unserialize($this->report->components);
            $userlist = $reportclass->elements_by_conditions($components['conditions'] ?? null);
        }
        $excludedeleted = $this->report->type === 'sql' && !empty($formdata->excludedeletedusers);

        if ($userlist === null || !empty($userlist)) {
            if (strpos($formdata->field, 'profile_') === 0) {
                $conditions = ['shortname' => str_replace('profile_', '', $formdata->field)];
                if ($field = $remotedb->get_record('user_info_field', $conditions)) {
                    $selectname = format_string($field->name);
                    $sql = "SELECT DISTINCT(data) as data FROM {user_info_data} WHERE fieldid = ?";
                    $params = [$field->id];
                    $order = " ORDER BY data ASC";
                    if ($userlist !== null) {
                        [$usql, $userparams] = $remotedb->get_in_or_equal($userlist);
                        $sql .= " AND userid $usql";
                        $params = array_merge($params, $userparams);
                    } else if ($excludedeleted) {
                        $sql .= " AND userid IN (SELECT id FROM {user} WHERE deleted = 0)";
                    }

                    if ($infodata = $remotedb->get_records_sql($sql . $order, $params)) {
                        foreach ($infodata as $d) {
                            $filteroptions[base64_encode(format_string($d->data))] = format_string($d->data);
                        }
                    }
                }
            } else {
                $selectname = cr_get_field_name($formdata->field);

                $sql = "SELECT DISTINCT({$formdata->field}) as ufield FROM {user}";
                $params = [];
                if ($userlist !== null) {
                    [$usql, $params] = $remotedb->get_in_or_equal($userlist);
                    $sql .= " WHERE id $usql";
                } else if ($excludedeleted) {
                    $sql .= " WHERE deleted = 0";
                }
                $sql .= " ORDER BY ufield ASC";

                if ($rs = $remotedb->get_recordset_sql($sql, $params)) {
                    foreach ($rs as $u) {
                        $filteroptions[base64_encode($u->ufield)] = s($u->ufield);
                    }
                    $rs->close();
                }
            }
        }

        $mform->addElement('select', 'filter_fuserfield_' . $formdata->field, $selectname, $filteroptions);
        $mform->setType('filter_fuserfield_' . $formdata->field, PARAM_RAW);
    }

    /**
     * sql_replace
     *
     * @param string $filtersearchtext
     * @param string $filterstrmatch
     * @param string $finalelements
     * @return array|mixed|string|string[]
     */
    private function sql_replace(string $filtersearchtext, $filterstrmatch, $finalelements) {
        global $DB;

        // Keep the SQL unchanged when the placeholder is not in the query (5.2 behaviour).
        $sql = $finalelements;
        $params = [];
        if (preg_match("/%%$filterstrmatch:([^%]+)%%/i", $finalelements, $output)) {
            $idoperator = $output[count($output) - 1];
            [$field, $operator] = array_pad(preg_split('/:/', $idoperator), 2, '');
            // Field may be "alias.column", which is not a valid named param. Include the placeholder so
            // two filters using the same column (e.g. "uid.data" for profile fields) don't share a param.
            $paramname = 'fuserfield_' . strtolower(preg_replace('/[^a-z0-9_]/i', '_', trim($output[0], '%')));

            if (empty($operator)) {
                $operator = '~';
            } else if (!in_array($operator, ['=', '<', '>', '<=', '>=', '~', 'in'], true)) {
                throw new moodle_exception('nosuchoperator');
            }

            if ($operator === '~') {
                // "Contains", like 5.2 (LIKE '%text%').
                $replace = ' AND ' . $DB->sql_like($field, ":{$paramname}", false, false);
                $params[$paramname] = '%' . $filtersearchtext . '%';
            } else if ($operator === 'in') {
                [$insql, $inparams] = $DB->get_in_or_equal(explode(',', $filtersearchtext), SQL_PARAMS_NAMED);
                $replace = " AND {$field} {$insql}";
                $params = array_merge($params, $inparams);
            } else {
                $replace = " AND {$field} {$operator} :{$paramname}";
                $params[$paramname] = $filtersearchtext;
            }
            $sql = str_replace($output[0], $replace, $finalelements);
        }

        return [$sql, $params];
    }

}
