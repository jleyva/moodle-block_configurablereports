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

require_once($CFG->dirroot.'/blocks/configurable_reports/components/columns/plugin.class.php');

class plugin_userfield extends columns_plugin{

	function execute($user, $courseid, $instance, $row, $starttime=0, $endtime=0){
	    if(! ($data = $instance->configdata)){
	        return '';
	    }
    	global $DB;
		
		if(strpos($data->column,'profile_') === 0){
		    $tables = '{user_info_data} d ,{user_info_field} f';
		    $columns = 'd.*, f.shortname, f.datatype';
		    $sql = "SELECT $columns FROM $tables WHERE f.id = d.fieldid AND d.userid = ?";
			if($profiledata = $DB->get_records_sql($sql, array($user->id))){
				foreach($profiledata as $p){
					if($p->datatype == 'checkbox'){
						$p->data = ($p->data)? get_string('yes') : get_string('no');
					}
					if($p->datatype == 'datetime'){
						$p->data = userdate($p->data);
					}
					$row->{'profile_'.$p->shortname} = $p->data;
				}
			}			
		}
		
		$column = $row->{$data->column};
		
		if(isset($column)){
			switch($data->column){
				case 'firstaccess':
				case 'lastaccess':
				case 'currentlogin':
				case 'timemodified':
				case 'lastlogin': 	
				    $column = ($column)? userdate($column): '--';
					break;
				case 'confirmed':
				case 'policyagreed':
				case 'maildigest':
				case 'ajax':
				case 'autosubscribe':
				case 'trackforums':
				case 'screenreader':
				case 'emailstop':
					$column = ($column)? get_string('yes') : get_string('no');
					break;
			}
		}
		
		return isset($column) ? $column : '';
	}
	
}

?>