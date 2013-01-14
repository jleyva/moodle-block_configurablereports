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
 
 class component_base {
	
	var $plugins = false;
	var $ordering = false;
	var $form = false;
	var $help = '';

	function __construct($report) {
		global $DB, $CFG;
		
		if(is_numeric($report))
			$this->config = $DB->get_record('block_configurable_reports',array('id' => $report));
		else
			$this->config = $report;
		$this->init();
	}
	
	function add_form_elements(&$mform,$fullform){
		return false;
	}
	
 }

