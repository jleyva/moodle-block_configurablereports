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

class plugin_base{

	
	var $fullname = '';
	var $type = '';
	var $report = null;
	var $form = false;
	var $cache = array();
	var $unique = false;
	var $reporttypes = array();
	
	function plugin_base($report){
		global $DB, $CFG;
		
		if(is_numeric($report))
			$this->report = $DB->get_record('block_configurable_reports',array('id' => $report));
		else
			$this->report = $report;
		$this->init();
	}
	
	function __construct($report){
		$this->plugin_base($report);
	}
	
	function summary(){
		return '';
	}
}

?>