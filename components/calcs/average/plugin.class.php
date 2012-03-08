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

require_once($CFG->dirroot.'/blocks/configurable_reports/components/calcs/plugin.class.php');

class plugin_average extends calcs_plugin{
	
	function init(){
		$this->fullname = get_string('average','block_configurable_reports');
	}
	
	function execute($rows){
		$result = 0;
		$els = 0;
		
		foreach($rows as $r){
			$result += is_numeric($r) ? $r : 0;
			$els++;
		}
		
		if ($els == 0) {
			$els = 1;
		}
		
		return round($result/$els,2);
	}
	
}

?>