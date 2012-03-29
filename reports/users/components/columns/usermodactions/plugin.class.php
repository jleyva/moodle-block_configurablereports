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

	function execute($user, $courseid, $instance, $row, $starttime=0, $endtime=0){
	    if(! ($data = $instance->configdata)){
	        return '';
	    }
		global $DB;
		
		$sql = "SELECT COUNT('x') AS numviews
              FROM {course_modules} cm                   
                   JOIN {log} l     ON l.cmid = cm.id
             WHERE cm.id = $data->cmid AND l.userid = $user->id AND l.action LIKE 'view%'
          GROUP BY cm.id";
		if($views = $DB->get_record_sql($sql))
			return $views->numviews;
		return 0;
	}
	
}

?>