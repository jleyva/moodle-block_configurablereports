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

/** Configurable Reports
  * A Moodle block for creating customizable reports
  * @package blocks
  * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
  * @date: 2009
  */

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_semester extends plugin_base{

	function init(){
		$this->form = false;
		$this->unique = true;
		$this->fullname = get_string('filtersemester','block_configurable_reports');
		$this->reporttypes = array('categories','sql');
	}

	function summary($data){
		return get_string('filtersemester_summary','block_configurable_reports');
	}

	function execute($finalelements, $data){

		$filter_semester = optional_param('filter_semester','',PARAM_RAW);
		if(!$filter_semester)
			return $finalelements;

		if ($this->report->type != 'sql') {
            return array($filter_semester);
		} else {
			if (preg_match("/%%FILTER_SEMESTER:([^%]+)%%/i",$finalelements, $output)) {
				$replace = ' AND '.$output[1].' LIKE \'%'.$filter_semester.'%\'';
				return str_replace('%%FILTER_SEMESTER:'.$output[1].'%%', $replace, $finalelements);
			}
		}
		return $finalelements;
	}

	function print_filter(&$mform){
		global $remoteDB, $CFG;

		$filter_semester = optional_param('filter_semester','',PARAM_RAW);

		$reportclassname = 'report_'.$this->report->type;
		$reportclass = new $reportclassname($this->report);
        //$semester = array('סמסטר א'=>'סמסטר א', 'סמסטר ב'=>'סמסטר ב', 'סמסטר ג'=>'סמסטר ג', 'סמינריון'=>'סמינריון');
        foreach(explode(',',get_string('filtersemester_list', 'block_configurable_reports')) as $value) {
            $semester[$value] = $value;
        }

		if($this->report->type != 'sql'){
			$components = cr_unserialize($this->report->components);
			$conditions = $components['conditions'];

			$semesterlist = $reportclass->elements_by_conditions($conditions);
		} else {
			$semesterlist = array_keys($semester);
		}

        $semesteroptions = array();
        $semesteroptions[0] = get_string('filter_all', 'block_configurable_reports');

		if(!empty($semesterlist)){
            // todo: check that keys of semester array items are available
			foreach($semester as $key => $year){
				$semesteroptions[$key] = $year;
			}
		}

		$mform->addElement('select', 'filter_semester', get_string('filtersemester','block_configurable_reports'), $semesteroptions);
		$mform->setType('filter_semester', PARAM_RAW);

	}

}

