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

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_startendtime extends plugin_base {

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('startendtime', 'block_configurable_reports');
        $this->reporttypes = array('sql', 'timeline', 'users', 'courses');
    }

    public function summary($data) {
        return get_string('filterstartendtime_summary', 'block_configurable_reports');
    }

    public function execute($finalelements, $data) {
        global $CFG;

        if ($this->report->type != 'sql') {
            return $finalelements;
        }

        if ($CFG->version < 2011120100) {
            $filterstarttime = optional_param('filter_starttime', 0, PARAM_RAW);
            $filterendtime = optional_param('filter_endtime', 0, PARAM_RAW);
        } else {
            $filterstarttime = optional_param_array('filter_starttime', 0, PARAM_RAW);
            $filterendtime = optional_param_array('filter_endtime', 0, PARAM_RAW);
        }

        if (!$filterstarttime || !$filterendtime) {
            return $finalelements;
        }

        $filterstarttime = make_timestamp($filterstarttime['year'], $filterstarttime['month'], $filterstarttime['day'],
            $filterstarttime['hour'], $filterstarttime['minute']);
        $filterendtime = make_timestamp($filterendtime['year'], $filterendtime['month'], $filterendtime['day'],
            $filterendtime['hour'], $filterendtime['minute']);

        $operators = array('<', '>', '<=', '>=');

        if (preg_match("/%%FILTER_STARTTIME:([^%]+)%%/i", $finalelements, $output)) {
            list($field, $operator) = preg_split('/:/', $output[1]);
            if (!in_array($operator, $operators)) {
                print_error('nosuchoperator');
            }
            $replace = ' AND '.$field.' '.$operator.' '.$filterstarttime;
            $finalelements = str_replace('%%FILTER_STARTTIME:'.$output[1].'%%', $replace, $finalelements);
        }

        if (preg_match("/%%FILTER_ENDTIME:([^%]+)%%/i", $finalelements, $output)) {
            list($field, $operator) = preg_split('/:/', $output[1]);
            if (!in_array($operator, $operators)) {
                print_error('nosuchoperator');
            }
            $replace = ' AND '.$field.' '.$operator.' '.$filterendtime;
            $finalelements = str_replace('%%FILTER_ENDTIME:'.$output[1].'%%', $replace, $finalelements);
        }

        $finalelements = str_replace('%STARTTIME%%', $filterstarttime, $finalelements);
        $finalelements = str_replace('%ENDTIME%%', $filterendtime, $finalelements);

        return $finalelements;
    }

    public function print_filter(&$mform) {
        global $DB, $CFG;
        $mform->addElement('date_time_selector', 'filter_starttime', get_string('starttime', 'block_configurable_reports'));
        $mform->setDefault('filter_starttime', time() - 3600 * 24);
        $mform->addElement('date_time_selector', 'filter_endtime', get_string('endtime', 'block_configurable_reports'));
        $mform->setDefault('filter_endtime', time() + 3600 * 24);
    }
}
