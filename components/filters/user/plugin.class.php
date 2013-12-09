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

class plugin_user extends plugin_base{

	function init(){
		$this->form = false;
		$this->unique = true;
		$this->fullname = get_string('filteruser', 'block_configurable_reports');
		$this->reporttypes = array('courses','sql');
	}

	function summary($data){
		return get_string('filteruser_summary', 'block_configurable_reports');
	}

	function execute($finalelements, $data){

		$filter_user = optional_param('filter_user', 0, PARAM_INT);
		if(!$filter_user)
			return $finalelements;

		if($this->report->type != 'sql'){
				return array($filter_user);
		} else {
			if (preg_match("/%%FILTER_COURSEUSER:([^%]+)%%/i", $finalelements, $output)) {
				$replace = ' AND '.$output[1].' = '.$filter_user;
				return str_replace('%%FILTER_COURSEUSER:'.$output[1].'%%', $replace, $finalelements);
			}
		}
		return $finalelements;
	}

	function print_filter(&$mform){
		global $remoteDB, $COURSE;

		//$filter_user = optional_param('filter_user',0,PARAM_INT);

		$reportclassname = 'report_'.$this->report->type;
		$reportclass = new $reportclassname($this->report);

		if($this->report->type != 'sql'){
			$components = cr_unserialize($this->report->components);
			$conditions = $components['conditions'];

			$userlist = $reportclass->elements_by_conditions($conditions);
		} else {
            $coursecontext = context_course::instance($COURSE->id);
            $userlist = array_keys(get_users_by_capability($coursecontext, 'moodle/user:viewdetails'));
		}

		$useroptions = array();
		$useroptions[0] = get_string('filter_all', 'block_configurable_reports');

		if(!empty($userlist)){
			list($usql, $params) = $remoteDB->get_in_or_equal($userlist);
			$users = $remoteDB->get_records_select('user',"id $usql",$params);

			foreach($users as $c){
				$useroptions[$c->id] = format_string($c->lastname.' '.$c->firstname);
			}
		}

		$mform->addElement('select', 'filter_user', get_string('user'), $useroptions);
		$mform->setType('filter_user', PARAM_INT);

	}

}

