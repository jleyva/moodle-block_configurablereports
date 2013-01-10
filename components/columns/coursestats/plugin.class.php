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

class plugin_coursestats extends plugin_base{

	function init(){
		$this->fullname = get_string('coursestats','block_configurable_reports');
		$this->type = 'undefined';
		$this->form = true;
		$this->reporttypes = array('courses');
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
	// row -> Complet user row c->id, c->fullname, etc...
	function execute($data,$row,$user,$courseid,$starttime=0,$endtime=0){
		global $DB, $CFG;
		
		$stat = '--';
					
		$filter_starttime = optional_param('filter_starttime',0,PARAM_RAW);
		$filter_endtime = optional_param('filter_endtime',0,PARAM_RAW);
		
		// Do not apply filters in timeline report (filters yet applied)
		if($starttime && $endtime){
			$filter_starttime = 0;
			$filter_endtime = 0;
		}
		
		if($filter_starttime and $filter_endtime){
			$filter_starttime = make_timestamp($filter_starttime['year'],$filter_starttime['month'],$filter_starttime['day']);
			$filter_endtime = make_timestamp($filter_endtime['year'],$filter_endtime['month'],$filter_endtime['day']);
		}
		
		$starttime = ($filter_starttime)? $filter_starttime : $starttime;
		$endtime = ($filter_endtime)? $filter_endtime : $endtime;
		
		$extrasql = "";
		
		switch($data->stat){
			case 'activityview':
								$total = 'SUM(stat1)';
								$stattype = 'activity';
								$extrasql = " AND roleid IN (".implode(',',$data->roles).")";
								break;
			case 'activitypost':
								$total = 'SUM(stat2)';
								$stattype = 'activity';
								$extrasql = " AND roleid IN (".implode(',',$data->roles).")";
								break;
			case 'activeenrolments':			
								$total = 'stat2';
								$stattype = 'enrolments';
								$extrasql = " ORDER BY timeend DESC LIMIT 1";
								break;
			case 'totalenrolments':
			default:
								$total = 'stat1';
								$stattype = 'enrolments';
								$extrasql = " ORDER BY timeend DESC LIMIT 1";
		}
		$sql = "SELECT $total as total FROM {stats_daily} WHERE stattype = ? AND courseid = ?";
		$params = array($stattype,$row->id);
		
		if($starttime and $endtime){
			$starttime = usergetmidnight($starttime) + 24*60*60;
			$endtime = usergetmidnight($endtime) + 24*60*60;
			$sql .= " AND timeend >= ? AND timeend <= ?";
			$params = array_merge($params, array($starttime,$endtime));
		}			

		$sql .= $extrasql;		
		
		if($res = $DB->get_records_sql($sql,$params)){
			$res = array_shift($res);
			if($res->total != NULL)
				return $res->total;
			else
				return 0;
		}
		
		return $stat;
	}	
}

