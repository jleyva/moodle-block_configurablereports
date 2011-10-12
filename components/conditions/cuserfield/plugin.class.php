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

class plugin_cuserfield extends plugin_base{
	
	function init(){
		$this->fullname = get_string('cuserfield','block_configurable_reports');
		$this->reporttypes = array('users');
		$this->form = true;
	}
		
	function summary($data){
		if(strpos($data->field,'profile_') === 0){
			$name = get_field('user_info_field','name','shortname',str_replace('profile_','',$data->field));
			return $name .' '.$data->operator.' '.$data->value;
		}
		return get_string($data->field).' '.$data->operator.' '.$data->value;
		
	}
	
	// data -> Plugin configuration data
	function execute($data,$user,$courseid){
	
		$data->value = addslashes($data->value);
		$ilike = sql_ilike();
		
		if(strpos($data->field,'profile_') === 0){
			
			if($fieldid = get_field('user_info_field','id','shortname',str_replace('profile_','',$data->field))){
			
				switch($data->operator){
					case 'LIKE % %': 	$sql = "fieldid = $fieldid AND data $ilike '%$data->value%'";
										break;
					default:	$sql = "fieldid = $fieldid AND data $data->operator '$data->value'";
				}

				if($infodata = get_records_select('user_info_data',$sql)){
					$finalusersid = array();
					foreach($infodata as $d){
						$finalusersid[] = $d->userid;
					}
					return $finalusersid;
				}
			}
		}	
		else{						
			switch($data->operator){
				case 'LIKE % %': 	$sql = "$data->field $ilike '%$data->value%'";
									break;
				default:	$sql = "$data->field $data->operator '$data->value'";
			}

			$users = get_records_select('user',$sql);			
			if($users)
				return array_keys($users);
		}
				
		return array();
	}
	
}

?>