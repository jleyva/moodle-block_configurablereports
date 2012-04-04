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

class plugin_usermodactions extends columns_plugin{

	function execute($instance, $row, $starttime=0, $endtime=0){
	    if(! ($data = $instance->configdata)){
	        return '';
	    }
		global $DB, $USER;
		
		$tables = '{course_modules} cm JOIN {log} l ON l.cmid = cm.id';
		$where = 'cm.id = :cmid AND l.userid = :userid AND '.$DB->sql_like('l.action', ':action');
		$params = array('cmid' => $data->cmid, 'userid' => $USER->id, 'action' => '%view%');
		$sql = "SELECT COUNT('x') FROM $tables WHERE $where GROUP BY cm.id";
		
		return $DB->count_records_sql($sql, $params);
	}
	
}

?>