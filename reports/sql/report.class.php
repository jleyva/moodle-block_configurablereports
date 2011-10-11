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

define('REPORT_CUSTOMSQL_MAX_RECORDS', 5000);

class report_sql extends report_base{
	
	function init(){
		$this->components = array('customsql','filters', 'template','permissions','calcs','plot');
	}	

	function prepare_sql($sql) {
		global $DB, $USER;
		
		$sql = str_replace('%%USERID%%', $USER->id, $sql);
		// See http://en.wikipedia.org/wiki/Year_2038_problem
		$sql = str_replace(array('%%STARTTIME%%','%%ENDTIME%%'),array('0','2145938400'),$sql);
		$sql = preg_replace('/%{2}[^%]+%{2}/i','',$sql);
		return $sql;
	}
	
	function execute_query($sql, $limitnum = REPORT_CUSTOMSQL_MAX_RECORDS) {
		global $DB, $CFG;

		$sql = preg_replace('/\bprefix_(?=\w+)/i', $CFG->prefix, $sql);

		return  $DB->get_recordset_sql($sql, null, 0, $limitnum);
	}
	
	function create_report(){
		global $DB, $CFG;
		
		$components = cr_unserialize($this->config->components);
		
		$filters = (isset($components['filters']['elements']))? $components['filters']['elements'] : array();
		$calcs = (isset($components['calcs']['elements']))? $components['calcs']['elements'] : array();
		
		$tablehead = array();
		$finalcalcs = array();
		$finaltable = array();
		$tablehead = array();
		
		$components = cr_unserialize($this->config->components);
		$config = (isset($components['customsql']['config']))? $components['customsql']['config'] : new stdclass;	
		
		if(isset($config->querysql)){
			// FILTERS
			$sql = $config->querysql;
			if(!empty($filters)){
				foreach($filters as $f){
					require_once($CFG->dirroot.'/blocks/configurable_reports/components/filters/'.$f['pluginname'].'/plugin.class.php');
					$classname = 'plugin_'.$f['pluginname'];
					$class = new $classname($this->config);
					$sql = $class->execute($sql, $f['formdata']);
				}
			}
			
			$sql = $this->prepare_sql($sql);
			if($rs = $this->execute_query($sql))
		        foreach ($rs as $row) {
					if(empty($finaltable)){
						foreach($row as $colname=>$value){
							$tablehead[] = str_replace('_', ' ', $colname);
						}
					}
					$finaltable[] = array_values((array) $row);
				}
		}
		
		// Calcs
		
		$finalcalcs = $this->get_calcs($finaltable,$tablehead);
		
		$table = new stdclass;
		$table->id = 'reporttable';
		$table->data = $finaltable;
		$table->head = $tablehead;		
	
		$calcs = new stdclass;
		$calcs->data = array($finalcalcs);
		$calcs->head = $tablehead;
		
		$this->finalreport->table = $table;
		$this->finalreport->calcs = $calcs;
		
		return true;	
	}
	
}

?>