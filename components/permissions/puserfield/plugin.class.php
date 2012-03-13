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

require_once($CFG->dirroot.'/blocks/configurable_reports/components/plugin.class.php');

class plugin_puserfield extends plugin_base{
    
	function summary($instance){
		global $DB;
		
		$data = $instance->configdata;
		if(strpos($data->field,'profile_') === 0){
			$name = $DB->get_field('user_info_field','name',array('shortname' => str_replace('profile_','', $data->field)));
			return $name .' = '.$data->value;
		}
		
		return $data->field.' = '.$data->value;
	}
	
	function has_form(){
	    return true;
	}
	
	function execute($userid, $context, $instance){
		global $DB;
		
		if (! ($user = $DB->get_record('user',array('id' => $userid)))) {
			return false;
		}
		
		$data = $instance->configdata;
		if (strpos($data->field,'profile_') === 0) {
		    $tables = '{user_info_data} d ,{user_info_field} f';
		    $columns = 'd.*, f.shortname, f.datatype';
		    $sql = "SELECT $columns FROM $tables WHERE f.id = d.fieldid AND d.userid = ?";
		    $profiledata = $DB->get_records_sql($sql, array($userid));
			foreach($profiledata as $p){				
				$user->{'profile_'.$p->shortname} = $p->data;
			}
		}
		
		return isset($user->{$data->field}) && ($user->{$data->field} == $data->value);
	}
	
}

?>