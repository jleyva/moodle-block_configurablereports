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

class plugin_courses extends plugin_base{

	function init(){
		$this->form = false;
		$this->unique = true;
		$this->fullname = get_string('filtercourses','block_configurable_reports');
		$this->reporttypes = array('courses','sql');
	}

	function summary($data){
		return get_string('filtercourses_summary','block_configurable_reports');
	}

	function execute($finalelements, $data){

		$filter_courses = optional_param('filter_courses',0,PARAM_INT);
		if(!$filter_courses)
			return $finalelements;

		if($this->report->type != 'sql'){
				return array($filter_courses);
		}
		else{
			if(preg_match("/%%FILTER_COURSES:([^%]+)%%/i",$finalelements, $output)){
				$replace = ' AND '.$output[1].' = '.$filter_courses;
				return str_replace('%%FILTER_COURSES:'.$output[1].'%%',$replace,$finalelements);
			}
		}
		return $finalelements;
	}

	function print_filter(&$mform){
		global $remoteDB, $CFG;

		$filter_courses = optional_param('filter_courses',0,PARAM_INT);

		$reportclassname = 'report_'.$this->report->type;
		$reportclass = new $reportclassname($this->report);

		if($this->report->type != 'sql'){
			$components = cr_unserialize($this->report->components);
			$conditions = $components['conditions'];

			$courselist = $reportclass->elements_by_conditions($conditions);
		}
		else{
			$courselist = array_keys($remoteDB->get_records('course'));
		}

		$courseoptions = array();
		$courseoptions[0] = get_string('filter_all', 'block_configurable_reports');

		if(!empty($courselist)){
			list($usql, $params) = $remoteDB->get_in_or_equal($courselist);
			$courses = $remoteDB->get_records_select('course',"id $usql",$params);

			foreach($courses as $c){
				$courseoptions[$c->id] = format_string($c->fullname);
			}
		}

		$mform->addElement('select', 'filter_courses', get_string('course'), $courseoptions);
		$mform->setType('filter_courses', PARAM_INT);

	}

}

