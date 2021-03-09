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
 * COMPETENCY TEMPLATE FILTER
 * A filter for configurable reports
 * @author: François Parlant <https://www.linkedin.com/in/francois-parlant/>
 * @date: 2020
 */ 


 /* example of report query
 ***********
 * Display the courses in which the competencies of a template are used
 ***********

 
 */


require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_competencytemplates extends plugin_base{

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filtercompetencytemplates', 'block_configurable_reports');
        $this->reporttypes = array('courses', 'sql');
    }

    public function summary($data) {
        return get_string('filtercompetencytemplates_summary', 'block_configurable_reports');
    }

    public function execute($finalelements, $data) {
		
        $filtercompetencytemplates = optional_param('filter_competencytemplates', 0, PARAM_INT);
        if (!$filtercompetencytemplates) {
            return $finalelements;
        }

        if ($this->report->type != 'sql') {
            return array($filtercompetencytemplates);
        } else {
            if (preg_match("/%%FILTER_COMPETENCYTEMPLATES:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND '.$output[1].' = '.$filtercompetencytemplates;
                return str_replace('%%FILTER_COMPETENCYTEMPLATES:'.$output[1].'%%', $replace, $finalelements);
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

            $competencytemplateslist = $reportclass->elements_by_conditions($conditions);
        } else {
            $sql = 'SELECT  ct.id, ct.shortname
                      FROM {competency_template} ct
                      ';
            $studentlist = $remotedb->get_records_sql($sql);
            foreach ($studentlist as $student) {
                $competencytemplateslist[] = $student->userid;
            }
			
        }

        $competencytemplatesoptions = array();
        $competencytemplatesoptions[0] = get_string('filter_all', 'block_configurable_reports');

        if (!empty($competencytemplateslist)) {

            $competencytemplates = $remotedb->get_records_sql($sql);

            foreach ($competencytemplates as $c) {
                $competencytemplatesoptions[$c->id] = $c->shortname;
            }
        }

        $elestr = get_string('competencytemplates', 'block_configurable_reports');
        $mform->addElement('select', 'filter_competencytemplates', $elestr, $competencytemplatesoptions);
        $mform->setType('filter_competencytemplates', PARAM_INT);
    }
}
