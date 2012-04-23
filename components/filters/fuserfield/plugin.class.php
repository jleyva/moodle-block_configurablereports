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

class plugin_fuserfield extends plugin_base{
	
	function init(){
		$this->form = true;
		$this->unique = true;
		$this->fullname = get_string('fuserfield','block_configurable_reports');
		$this->reporttypes = array('users');
	}
	
	function summary($data){
		return $data->field;
	}
	
	function execute($finalelements,$data){
		global $DB, $CFG;
		
		$filter_fuserfield = optional_param('filter_fuserfield_'.$data->field,0,PARAM_RAW);		
		if($filter_fuserfield){
			// addslashes is done in clean param
			$filter = clean_param(base64_decode($filter_fuserfield),PARAM_CLEAN);
			
			if(strpos($data->field,'profile_') === 0){				
				if($fieldid = $DB->get_field('user_info_field','id',array('shortname' => str_replace('profile_','', $data->field)))){
				
					list($usql, $params) = $DB->get_in_or_equal($finalelements);					
					$sql = "fieldid = ? AND data = ? AND userid $usql";
					$params = array_merge(array($fieldid, $filter),$params);
				
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
				$sql = "$data->field = ? AND id $usql";
				$params = array_merge(array($filter),$params);
				if($elements = $DB->get_records_select('user',$sql,$params)){				
					$finalelements = array_keys($elements);				
				}
			}
		}
		return $finalelements;
	}
	
	function print_filter(&$mform, $data){
		global $DB, $CFG;
		
		$columns = $DB->get_columns('user');
		$filteroptions = array();
		$filteroptions[''] = get_string('choose');
		
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
				
		$components = cr_unserialize($this->report->components);		
		$conditions = $components['conditions'];
		$userlist = $reportclass->elements_by_conditions($conditions);
						
		if(!empty($userlist)){
			if(strpos($data->field,'profile_') === 0){	
				if($field = $DB->get_record('user_info_field',array('shortname' => str_replace('profile_','', $data->field)))){
					$selectname = $field->name;
					
					list($usql, $params) = $DB->get_in_or_equal($userlist);					
					$sql = "SELECT DISTINCT(data) as data FROM {user_info_data} WHERE fieldid = ? AND userid $usql";					
					$params = array_merge(array($field->id),$params);
					
					if($infodata = $DB->get_records_sql($sql,$params)){
						$finalusersid = array();
						foreach($infodata as $d){
							$filteroptions[base64_encode($d->data)] = $d->data;
						}						
					}
				}
			}
			else{
				$selectname = get_string($data->field);
				
				list($usql, $params) = $DB->get_in_or_equal($userlist);
				$sql = "SELECT DISTINCT(".$data->field.") as ufield FROM {user} WHERE id $usql ORDER BY ufield ASC";
				if($rs = $DB->get_recordset_sql($sql, $params)){
					foreach($rs as $u){				
						$filteroptions[base64_encode($u->ufield)] = $u->ufield;
					}
					$rs->close();
				}
			}
		}
		
		$mform->addElement('select', 'filter_fuserfield_'.$data->field, $selectname, $filteroptions);
		$mform->setType('filter_courses', PARAM_INT);
		
	}	
}

?>