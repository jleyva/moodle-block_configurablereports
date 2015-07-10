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

class plugin_semester extends plugin_base {

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filtersemester', 'block_configurable_reports');
        $this->reporttypes = array('categories', 'sql');
    }

    public function summary($data) {
        return get_string('filtersemester_summary', 'block_configurable_reports');
    }

    public function execute($finalelements, $data) {

        $filtersemester = optional_param('filter_semester', '', PARAM_RAW);
        if (!$filtersemester) {
            return $finalelements;
        }

        if ($this->report->type != 'sql') {
            return array($filtersemester);
        } else {
            if (preg_match("/%%FILTER_SEMESTER:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND '.$output[1].' LIKE \'%'.$filtersemester.'%\'';
                return str_replace('%%FILTER_SEMESTER:'.$output[1].'%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }

    public function print_filter(&$mform) {
        global $remotedb, $CFG;

        $filtersemester = optional_param('filter_semester', '', PARAM_RAW);

        $reportclassname = 'report_'.$this->report->type;
        $reportclass = new $reportclassname($this->report);
        foreach (explode(',', get_string('filtersemester_list', 'block_configurable_reports')) as $value) {
            $semester[$value] = $value;
        }

        if ($this->report->type != 'sql') {
            $components = cr_unserialize($this->report->components);
            $conditions = $components['conditions'];

            $semesterlist = $reportclass->elements_by_conditions($conditions);
        } else {
            $semesterlist = array_keys($semester);
        }

        $semesteroptions = array();
        $semesteroptions[0] = get_string('filter_all', 'block_configurable_reports');

        if (!empty($semesterlist)) {
            // Todo: check that keys of semester array items are available.
            foreach ($semester as $key => $year) {
                $semesteroptions[$key] = $year;
            }
        }

        $elestr = get_string('filtersemester', 'block_configurable_reports');
        $mform->addElement('select', 'filter_semester', $elestr, $semesteroptions);
        $mform->setType('filter_semester', PARAM_RAW);
    }
}
