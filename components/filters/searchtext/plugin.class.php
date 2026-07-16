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
 * Class plugin_searchtext
 *
 * @package   block_configurable_reports
 * @author    Juan leyva <http://www.twitter.com/jleyvadelgado>
 */
class plugin_searchtext extends filter_base {

    /**
     * Init
     *
     * @return void
     */
    public function init(): void {
        $this->form = true;
        $this->unique = false;
        $this->fullname = get_string('filter_searchtext', 'block_configurable_reports');
        $this->reporttypes = ['searchtext', 'sql'];
    }

    /**
     * Summary
     *
     * @param object $data
     * @return string
     */
    public function summary(object $data): string {
        return empty($data->idnumber) ? get_string('filter_searchtext_summary', 'block_configurable_reports') : $data->idnumber;
    }

    /**
     * Execute
     *
     * @param string $finalelements
     * @param object $data
     * @return string|array
     */
    public function execute($finalelements, $data) {
        // For backwards compatibility and filters without idnumber, includes old method of matching without idnumber.
        if (!empty($data->idnumber)) {
            $filtersearchtext = optional_param('filter_searchtext_' . $data->idnumber, '', PARAM_RAW);
        } else {
            $filtersearchtext = optional_param('filter_searchtext', '', PARAM_RAW);
        }

        return [$filtersearchtext];
    }

    #[\Override]
    function execute_for_sql_report(string $sql, ?\stdClass $data = null): array {
        // For backwards compatibility and filters without idnumber, includes old method of matching without idnumber.
        if (!empty($data->idnumber)) {
            $filtersearchtext = optional_param('filter_searchtext_' . $data->idnumber, '', PARAM_RAW);
        } else {
            $filtersearchtext = optional_param('filter_searchtext', '', PARAM_RAW);
        }

        if ($filtersearchtext) {
            if (!empty($data->idnumber)) {
                $filtermatch = "FILTER_SEARCHTEXT_{$data->idnumber}";
            } else {
                $filtermatch = "FILTER_SEARCHTEXT";
            }

            return $this->sql_replace($filtersearchtext, $filtermatch, $sql);
        }

        // If nothing, remove this SQL component.
        $sql = preg_replace('/%%FILTER_SEARCHTEXT_[^%]+%%/i', '', $sql);

        return [$sql, []];
    }

    /**
     * Print filter
     *
     * @param MoodleQuickForm $mform
     * @param bool|object $formdata
     * @return void
     */
    public function print_filter(MoodleQuickForm $mform, $formdata = false): void {

        // For backwards compatibility and filters without idnumber, includes old method of matching without idnumber.
        if (!empty($formdata->idnumber)) {
            $filtername = 'filter_searchtext_' . $formdata->idnumber;
        } else {
            $filtername = 'filter_searchtext';
        }
        if (isset($formdata->label)) {
            $filterlabel = $formdata->label;
        } else {
            $filterlabel = get_string('filter', 'block_configurable_reports');
        }
        $filtersearchtext = optional_param($filtername, '', PARAM_RAW);
        $mform->addElement('text', $filtername, $filterlabel);
        $mform->setType($filtername, PARAM_RAW);
        $mform->setDefault($filtername, $filtersearchtext);
    }

    /**
     * sql_replace
     *
     * @param string $filtersearchtext
     * @param string $filterstrmatch
     * @param string $finalelements
     * @return array
     */
    private function sql_replace($filtersearchtext, $filterstrmatch, $finalelements) {
        global $DB;

        // TODO Check if this is a duplicate of the same function in plugin_fuserfield.
        $sql = '';
        $params = [];
        if (preg_match("/%%$filterstrmatch:([^%]+)%%/i", $finalelements, $output)) {
            [$field, $operator] = preg_split('/:/', $output[1]);

            if (!in_array($operator, ['=', '<', '>', '<=', '>=', '~', 'in'], true)) {
                throw new moodle_exception('nosuchoperator');
            }

            if ($operator === '~') {
                $replace = ' AND ' . $DB->sql_like($field, ":{$field}");
                $params[$field] = $filtersearchtext;
            } else if ($operator === 'in') {
                $processeditems = [];
                // Accept comma-separated values, allowing for '\,' as a literal comma.
                foreach (preg_split("/(?<!\\\\),/", $filtersearchtext) as $key => $searchitem) {
                    $paramkey = "{$field}" . $key;
                    $processeditems[] = 'AND ' . $DB->sql_like($field, ":{$paramkey}");
                    $params[$paramkey] = $searchitem;
                }
                // Despite the name, by not actually using in() we can support wildcards, and maybe be more portable as well.
                $replace = " AND (" . implode(" OR ", $processeditems) . ")";
            } else {
                $replace = " AND {$field} {$operator} :{$field}";
                $params[$field] = $filtersearchtext;
            }
            $sql = str_replace("%%$filterstrmatch:" . $output[1] . '%%', $replace, $finalelements);
        }

        return [$sql, $params];
    }

}
