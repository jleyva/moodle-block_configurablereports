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

class plugin_fsearchuserfield extends plugin_base{
	
	function init(){
		$this->form = true;
		$this->unique = true;
		$this->fullname = get_string('fsearchuserfield','block_configurable_reports');
		$this->reporttypes = array('users', 'sql');
	}
	
	function summary($data){
		return $data->field;
	}
	
	function execute($finalelements,$data){
		if($this->report->type == 'sql') {
			return $this->execute_sql($finalelements, $data);
		}

		return $this->execute_users($finalelements, $data);


	}
	
	private function execute_sql($finalelements, $data) {
		$filter_fuserfield = optional_param('filter_fuserfield_'.$data->field,0,PARAM_RAW);

		if($filter_fuserfield &&
		   preg_match("/%%FILTER_USERS:([^%]+)%%/i",$finalelements, $output)){
			$replace = ' AND '.$output[1].' LIKE '. "'%$filter_fuserfield%'";
			return str_replace('%%FILTER_USERS:'.$output[1].'%%',$replace,$finalelements);
		}

		return $finalelements;
	}

	private function execute_users($finalelements, $data) {
		global $DB, $CFG;
		
		$filter_fuserfield = optional_param('filter_fuserfield_'.$data->field,0,PARAM_RAW);		
		if($filter_fuserfield){
			// addslashes is done in clean param
			$filter = $filter_fuserfield;
			
			if(strpos($data->field,'profile_') === 0){				
				if($fieldid = $DB->get_field('user_info_field','id',array('shortname' => str_replace('profile_','', $data->field)))){
				
					list($usql, $params) = $DB->get_in_or_equal($finalelements);					
					$sql = "fieldid = ? AND data LIKE ? AND userid $usql";
					$params = array_merge(array($fieldid, "%$filter%"),$params);

					if($infodata = $DB->get_records_select('user_info_data',$sql,$params)){
						$finalusersid = array();
						foreach($infodata as $d){
							$finalusersid[] = $d->userid;
						}
						return $finalusersid;
					}
				}
			}			
			else{
				list($usql, $params) = $DB->get_in_or_equal($finalelements);
				$sql = "$data->field LIKE ? AND id $usql";
				$params = array_merge(array("%$filter%"),$params);
				$elements = $DB->get_records_select('user',$sql,$params);
				$finalelements = array_keys($elements);
			}
		}

		return $finalelements;		
	}
	
	function print_filter(&$mform, $data){
		global $DB, $CFG;
		
		$columns = $DB->get_columns('user');
		$filteroptions = array();
		$filteroptions[''] = get_string('filter_all', 'block_configurable_reports');
		
		$usercolumns = array();
		foreach($columns as $c)
			$usercolumns[$c->name] = $c->name;
			
		if($profile = $DB->get_records('user_info_field'))
			foreach($profile as $p)
				$usercolumns['profile_'.$p->shortname] = $p->name;		
			
		if(!isset($usercolumns[$data->field]))
			print_error('nosuchcolumn');
			
		$reportclassname = 'report_'.$this->report->type;	
		$reportclass = new $reportclassname($this->report);

		if($this->report->type == 'sql'){	
			$userlist = array_keys($DB->get_records('user'));
		} else {
			$components = cr_unserialize($this->report->components);
			$conditions = array_key_exists('conditions', $components) ?
				$components['conditions'] :
				null;
			$userlist = $reportclass->elements_by_conditions($conditions);
		}
		$selectname = get_string($data->field);
								
		$mform->addElement('text', 'filter_fuserfield_'.$data->field, $selectname, array('size' => 20));
		$mform->setType('filter_courses', PARAM_INT);
	}	
}

