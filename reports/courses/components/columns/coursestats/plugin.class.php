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

class plugin_coursestats extends columns_plugin{
	
	function execute($instance, $row, $starttime=0, $endtime=0){
	    if(! ($data = $instance->configdata)){
	        return '';
	    }
		global $DB;
		
		$stat = '--';
					
		//TODO: Move to filters and utilize with API
		$filter_starttime = optional_param_array('filter_starttime', 0, PARAM_RAW);
		$filter_endtime = optional_param_array('filter_endtime', 0, PARAM_RAW);
		
		// Do not apply filters in timeline report (filters yet applied)
		if($starttime && $endtime){
			$filter_starttime = 0;
			$filter_endtime = 0;
		}
		
		if($filter_starttime and $filter_endtime){
			$filter_starttime = make_timestamp($filter_starttime['year'],$filter_starttime['month'],$filter_starttime['day']);
			$filter_endtime = make_timestamp($filter_endtime['year'],$filter_endtime['month'],$filter_endtime['day']);
		}
		
		$starttime = ($filter_starttime) ? $filter_starttime : $starttime;
		$endtime = ($filter_endtime) ? $filter_endtime : $endtime;
		
		$extrasql = "";
		$params = array();
		switch($data->stat){
			case 'activityview':
				$total = 'SUM(stat1)';
				$stattype = 'activity';
				list($rolesql, $params) = $DB->get_in_or_equal($data->roles, SQL_PARAMS_NAMED);
				$extrasql = " AND roleid $rolesql";
				break;
			case 'activitypost':
				$total = 'SUM(stat2)';
				$stattype = 'activity';
				list($rolesql, $params) = $DB->get_in_or_equal($data->roles, SQL_PARAMS_NAMED);
				$extrasql = " AND roleid $rolesql";
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
		$sql = "SELECT $total as total FROM {stats_daily} WHERE stattype = :stattype AND courseid = :courseid";
		$params['stattype'] = $stattype;
		$params['courseid'] = $row->id;
		
		if ($starttime and $endtime) {
			$starttime = usergetmidnight($starttime) + 24*60*60;
			$endtime = usergetmidnight($endtime) + 24*60*60;
			$sql .= " AND timeend >= :timestart AND timeend <= :timeend";
			$params = array_merge($params, array('timestart' => $starttime, 'timeend' => $endtime));
		}	
		
		if($res = $DB->get_records_sql($sql.$extrasql, $params)){
			$res = array_shift($res);
			if ($res->total != NULL) {
				return $res->total;
			} else {
				return 0;
			}
		}
		
		return $stat;
	}	
}

?>