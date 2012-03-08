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
 * @author: Nick Koeppen
 */

require_once($CFG->dirroot.'/blocks/configurable_reports/components/plugin.class.php');

abstract class calcs_plugin extends plugin_base{
	
	function has_form(){
	    return true;
	}
	
	function summary($data){
		global $DB, $CFG;
		
		if($this->report->type != 'sql'){
			$components = cr_unserialize($this->report->components);		
			if(!is_array($components) || empty($components['columns']['elements']))
				print_error('nocolumns');
					
			$columns = $components['columns']['elements'];
			$i = 0;
			foreach($columns as $c){
				if($i == $data->column)
					return $c['summary'];
				$i++;
			}
		} else {
			$reportclass = report_base::get($this->report);
			
			$components = cr_unserialize($this->report->components);
			$config = (isset($components['customsql']['config']))? $components['customsql']['config'] : new stdclass;	
			
			if(isset($config->querysql)){
				
				$sql =$config->querysql;
				$sql = $reportclass->prepare_sql($sql);
				if($rs = $reportclass->execute_query($sql)){
					foreach($rs as $row){
						$i = 0;
						foreach($row as $colname=>$value){
							if($i == $data->column)
								return str_replace('_', ' ', $colname);
							$i++;
						}
						break;
					}
					$rs->close();
				}
			}				
		}
		
		return '';
	}
}

?>