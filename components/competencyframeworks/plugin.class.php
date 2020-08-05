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
 
 /**
 * COMPETENCY FRAMEWORK FILTER
 * A filter for configurable reports
 * @author: François Parlant <https://www.linkedin.com/in/francois-parlant/>
 * @date: 2020
 */ 

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_competencyframeworks extends plugin_base{

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filtercompetencyframeworks', 'block_configurable_reports');
        $this->reporttypes = array('courses', 'sql');
    }

    public function summary($data) {
        return get_string('filtercompetencyframeworks_summary', 'block_configurable_reports');
    }

    public function execute($finalelements, $data) {
		
        $filtercompetencyframeworks = optional_param('filter_competencyframeworks', 0, PARAM_INT);
        if (!$filtercompetencyframeworks) {
            return $finalelements;
        }

        if ($this->report->type != 'sql') {
            return array($filtercompetencyframeworks);
        } else {
            if (preg_match("/%%FILTER_COMPETENCYFRAMEWORKS:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND '.$output[1].' = '.$filtercompetencyframeworks;
                return str_replace('%%FILTER_COMPETENCYFRAMEWORKS:'.$output[1].'%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }

    public function print_filter(&$mform) {
        global $remotedb, $COURSE, $PAGE, $CFG;

        $reportclassname = 'report_'.$this->report->type;
        $reportclass = new $reportclassname($this->report);

        if ($this->report->type != 'sql') {
            $components = cr_unserialize($this->report->components);
            $conditions = $components['conditions'];

            $competencyframeworkslist = $reportclass->elements_by_conditions($conditions);
        } else {
            $sql = 'SELECT  cf.id, cf.shortname
                      FROM {competency_framework} cf
                      ';
            $studentlist = $remotedb->get_records_sql($sql);
            foreach ($studentlist as $student) {
                $competencyframeworkslist[] = $student->userid;
            }
			
        }

        $competencyframeworksoptions = array();
        $competencyframeworksoptions[0] = get_string('filter_all', 'block_configurable_reports');

        if (!empty($competencyframeworkslist)) {
            if (has_capability('moodle/site:viewfullnames', $PAGE->context)) {
               $nameformat = $CFG->alternativefullnameformat;
            } else {
               $nameformat = $CFG->fullnamedisplay;
            }

            if ($nameformat == 'language') {
                $nameformat = get_string('fullnamedisplay');
            }

            //$sort = implode(',', order_in_string(get_all_user_name_fields(), $nameformat));

            //list($usql, $params) = $remotedb->get_in_or_equal($competencyframeworkslist);
            $competencyframeworks = $remotedb->get_records_sql($sql);

            foreach ($competencyframeworks as $c) {
                $competencyframeworksoptions[$c->id] = $c->shortname;
            }
        }

        $elestr = get_string('competencyframeworks', 'tool_lp');
        $mform->addElement('select', 'filter_competencyframeworks', $elestr, $competencyframeworksoptions);
        $mform->setType('filter_competencyframeworks', PARAM_INT);
    }
}
