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

class plugin_date extends plugin_base{

	function init(){
		$this->fullname = get_string('date','block_configurable_reports');
		$this->type = 'undefined';
		$this->form = true;
		$this->reporttypes = array('timeline');
	}
	
	function summary($data){		
		return format_string($data->columname);
	}
	
	function colformat($data){
		$align = (isset($data->align))? $data->align : '';
		$size = (isset($data->size))? $data->size : '';
		$wrap = (isset($data->wrap))? $data->wrap : '';
		return array($align,$size,$wrap);
	}	
	
	// data -> Plugin configuration data
	// row -> Complet course row c->id, c->fullname, etc...
	function execute($data,$row,$user,$courseid,$starttime=0,$endtime=0){
		$date = ($data->date == 'starttime')? $row->starttime: $row->endtime;
		
		$format = (isset($data->dateformat))? $data->dateformat: '';
		$format = ($data->dateformat == 'custom')? $data->customdateformat : $format;
		$format = preg_replace('/[^a-zA-Z%]/i','', $format);
		
		return userdate($date,$format);
	}
	
}

