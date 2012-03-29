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

require_once($CFG->dirroot.'/blocks/configurable_reports/components/filters/plugin.class.php');

class plugin_fcoursefield extends filters_plugin{
	
	function get_fullname($instance){
		return get_string('fcoursefield','block_configurable_reports');
	}
	
	function summary($instance){
		return $instance->configdata->field;
	}
	
	function has_form(){
	    return true;
	}
	
	function execute($finalelements, $instance){
	    global $DB;
	    
	    if (! ($data = $instance->configdata) || 
		    ! ($filter = optional_param('filter_fcoursefield_'.$data->field, 0, PARAM_RAW))) {
	        return $finalelements;
	    }
	    
		$filter = clean_param(base64_decode($filter), PARAM_CLEAN);
		list($usql, $params) = $DB->get_in_or_equal($finalelements);			
		$where = "$data->field = ? AND id $usql";
		$params = array_merge(array($filter), $params);
		return $DB->get_fieldset_select('course', 'id', $where, $params);
	}
	
	function print_filter(&$mform, $instance){
		global $DB;
		
		if (! ($data = $instance->configdata)) {
		    return false;
		}
		
		$columns = $DB->get_columns('course');
		$filteroptions = array();
		$filteroptions[''] = get_string('choose');
		
		$coursecolumns = array();
		foreach($columns as $c){
			$coursecolumns[$c->name] = $c->name;
		}
			
		if(!isset($coursecolumns[$data->field]))
			print_error('nosuchcolumn');
			
		$courselist = $this->report->get_elements_by_conditions();	
		if(empty($courselist)){
		    return false;
		}
		
	    $where = "$data->field != :val";
	    $sql = "SELECT DISTINCT($data->field) as ufield FROM {course} WHERE $where ORDER BY ufield ASC";
		if($rs = $DB->get_recordset_sql($sql, array('val' => ''))){
			foreach($rs as $u){
				$filteroptions[base64_encode($u->ufield)] = $u->ufield;
			}
			$rs->close();
		}
		
		$mform->addElement('select', 'filter_fcoursefield_'.$data->field, get_string($data->field), $filteroptions);
		$mform->setType('filter_courses', PARAM_INT);
	}	
}

?>