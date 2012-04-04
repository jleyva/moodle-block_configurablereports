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

require_once($CFG->dirroot.'/blocks/configurable_reports/components/conditions/plugin.class.php');

class plugin_cuserfield extends conditions_plugin{
		
	function summary($instance){
	    if(! ($data = $instance->configdata)){
	        return '';
	    }
		global $DB;
		
		if(strpos($data->field,'profile_') === 0){
		    $params = array('shortname' => str_replace('profile_','',$data->field));
			$name = $DB->get_field('user_info_field', 'name', $params);
			return $name .' '.$data->operator.' '.$data->value;
		}
		
		return get_string($data->field).' '.$data->operator.' '.$data->value;
	}
	
	function has_form(){
	    return true;
	}
	
	function execute($instance){
	    if(! ($data = $instance->configdata)){
	        return '';
	    }
		global $DB;
		
		list($sql, $params) = $this->operator_sql($data, $params);
		
		$userids = array();
		if (strpos($data->field, 'profile_') === 0) {
		    $search = array('shortname' => str_replace('profile_','', $data->field));
			if ($fieldid = $DB->get_field('user_info_field', 'id', $search)) {
			    $sql .= " AND fieldid = :fieldid";
			    $params['fieldid'] = $fieldid;

				$userids = $DB->get_fieldset_select('user_info_data', 'userid', $sql, $params);
			}
		} else if ($users = $DB->get_records_select('user', $sql, $params)) {
            $userids =  array_keys($users);
		}
				
		return $userids;
	}
	
}

?>