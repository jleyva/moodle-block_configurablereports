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

class plugin_fcoursefield extends plugin_base {

    public function init() {
        $this->form = true;
        $this->unique = true;
        $this->fullname = get_string('fcoursefield', 'block_configurable_reports');
        $this->reporttypes = array('courses');
    }

    public function summary($data) {
        return $data->field;
    }

    public function execute($finalelements, $data) {
        global $remotedb;
        $filterfcoursefield = optional_param('filter_fcoursefield_'.$data->field, 0, PARAM_RAW);
        if ($filterfcoursefield) {
            // Function addslashes is done in clean param.
            $filter = clean_param(base64_decode($filterfcoursefield), PARAM_CLEAN);
            list($usql, $params) = $remotedb->get_in_or_equal($finalelements);
            $sql = "$data->field = ? AND id $usql";
            $params = array_merge(array($filter), $params);
            if ($elements = $remotedb->get_records_select('course', $sql, $params)) {
                $finalelements = array_keys($elements);
            }
        }
        return $finalelements;
    }

    public function print_filter(&$mform, $data) {
        global $remotedb, $CFG;

        $columns = $remotedb->get_columns('course');
        $filteroptions = array();
        $filteroptions[''] = get_string('filter_all', 'block_configurable_reports');

        $coursecolumns = array();
        foreach ($columns as $c) {
            $coursecolumns[$c->name] = $c->name;
        }

        if (!isset($coursecolumns[$data->field])) {
            print_error('nosuchcolumn');
        }

        $reportclassname = 'report_'.$this->report->type;
        $reportclass = new $reportclassname($this->report);

        $components = cr_unserialize($this->report->components);
        $conditions = $components['conditions'];
        $courselist = $reportclass->elements_by_conditions($conditions);

        if (!empty($courselist)) {
            $sql = 'SELECT DISTINCT('.$data->field.') as ufield FROM {course} WHERE '.$data->field.' <> "" ORDER BY ufield ASC';
            if ($rs = $remotedb->get_recordset_sql($sql, null)) {
                foreach ($rs as $u) {
                    $filteroptions[base64_encode($u->ufield)] = $u->ufield;
                }
                $rs->close();
            }
        }

        $mform->addElement('select', 'filter_fcoursefield_'.$data->field, get_string($data->field), $filteroptions);
        $mform->setType('filter_courses', PARAM_BASE64);
    }
}
