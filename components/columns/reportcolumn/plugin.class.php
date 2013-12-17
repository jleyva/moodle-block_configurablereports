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
  * A Moodle block for creating Configurable Reports
  * @package blocks
  * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
  * @date: 2009
  */

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_reportcolumn extends plugin_base{

	var $reportcache = array();

	function init(){
		$this->fullname = get_string('reportcolumn','block_configurable_reports');
		$this->type = 'undefined';
		$this->form = true;
		$this->reporttypes = array('courses','users','timeline','categories');
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

	function get_user_reports(){
		global $DB, $USER;

		$supported = array('courses'=>array('users'), 'users'=>array('courses'), 'timeline'=>array('users','courses','sql'), 'categories' => array('courses'));

		$reports = cr_get_my_reports($this->report->courseid, $USER->id);
		if($reports){
			foreach($reports as $key=>$val){
				if(!in_array($val->type,$supported[$this->report->type]))
					unset($reports[$key]);
			}
		}
		return $reports;
	}

	function get_current_report($report){
		$components = cr_unserialize($report->components);

		if(!is_array($components) || empty($components['columns']['elements']))
			return false;

		$elements = $components['columns']['elements'];
		foreach($elements as $e){
			if($e['pluginname'] == 'reportcolumn' && $e['formdata']->reportid)
				return $e['formdata']->reportid;
		}
		return 0;
	}

	function get_report_columns($reportid){
		global $DB;

		$columns = array();
		if(! $report = $DB->get_record('block_configurable_reports',array('id' => $reportid)))
			return $columns;

		$components = cr_unserialize($report->components);

		if(!is_array($components) || empty($components['columns']['elements']))
			return $columns;

		$elements = $components['columns']['elements'];
		foreach($elements as $e){
			$columns[] = $e['summary'];
		}

		return $columns;
	}

	function fix_condition_expr($condition,$count){
		switch($count){
			case 0: return '';
			case 1: return '';
			case 2: return 'c1 and c2';
			default: return $condition." and c$count";
		}
	}

	// data -> Plugin configuration data
	// row -> Complet course/user row c->id, c->fullname, etc...
	function execute($data,$row,$user,$courseid,$starttime=0,$endtime=0){
		global $DB, $CFG;

		if(! $report = $DB->get_record('block_configurable_reports',array('id' => $data->reportid)))
			print_error('reportdoesnotexists','block_configurable_reports');

		require_once($CFG->dirroot.'/blocks/configurable_reports/report.class.php');
		require_once($CFG->dirroot.'/blocks/configurable_reports/reports/'.$report->type.'/report.class.php');

		if(!isset($this->reportcache[$row->id])){

			$reportclassname = 'report_'.$report->type;
			$reportclass = new $reportclassname($report);

			// Delete conditions - TODO
			// Add new condition
			// User report -> New condition "User courses"
			// Course report -> New condition "Course users"
			if($this->report->type == 'users'){
				$reportclass->currentuser = $row;
				$reportclass->starttime = $starttime;
				$reportclass->endtime = $endtime;

				if($report->type == 'courses'){
					$components = cr_unserialize($reportclass->config->components);
					$newplugin = array('pluginname'=>'currentusercourses','fullname'=>'currentusercourses','formdata'=>new stdclass);

					$components['conditions']['elements'][] = $newplugin;
					$components['conditions']['config']->conditionexpr = $this->fix_condition_expr($components['conditions']['config']->conditionexpr, count($components['conditions']['elements']));
					$reportclass->config->components = cr_serialize($components);
				}
			}
			else if($this->report->type == 'courses'){
				$reportclass->currentcourseid = $row->id;
				$reportclass->starttime = $starttime;
				$reportclass->endtime = $endtime;

				if($report->type == 'users'){
					$components = cr_unserialize($reportclass->config->components);

					$roles = $DB->get_records('role');
					$rolesid = array_keys($roles);

					$formdata = new stdclass;
					$formdata->roles = $rolesid;
					$newplugin = array('pluginname'=>'usersincurrentcourse','fullname'=>'usersincurrentcourse','formdata'=>$formdata);

					$components['conditions']['elements'][] = $newplugin;
					if (!empty($components['conditions']['config'])) {
						$components['conditions']['config']->conditionexpr = $this->fix_condition_expr($components['conditions']['config']->conditionexpr, count($components['conditions']['elements']));
					}
					$reportclass->config->components = cr_serialize($components);
				}
			}
			else if($this->report->type == 'timeline'){
				$reportclass->starttime = $row->starttime;
				$reportclass->endtime = $row->endtime;
			}
			else if($this->report->type == 'categories'){
				$reportclass->starttime = $starttime;
				$reportclass->endtime = $endtime;

				if($report->type == 'courses'){
					$components = cr_unserialize($reportclass->config->components);

					$formdata = new stdclass;
					$formdata->categoryid = $row->id;
					$newplugin = array('pluginname'=>'coursecategory','fullname'=>'coursecategory','formdata'=>$formdata);

					$components['conditions']['elements'][] = $newplugin;
					$components['conditions']['config']->conditionexpr = $this->fix_condition_expr($components['conditions']['config']->conditionexpr, count($components['conditions']['elements']));
					$reportclass->config->components = cr_serialize($components);
				}
			}

			$reportclass->create_report();
			$this->reportcache[$row->id] = $reportclass->finalreport->table->data;
		}

		if(!empty($this->reportcache[$row->id])){
			$subtable = array();
			foreach($this->reportcache[$row->id] as $r){
				$subtable[] = $r[$data->column];
			}
			return $subtable;
		}

		return '';
	}

}

