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

require_once($CFG->dirroot.'/blocks/configurable_reports/components/columns/plugin.class.php');

abstract class plugin_reportcolumn extends columns_plugin{
    private $reportcache;
        
    function execute($instance, $row, $starttime=0, $endtime=0){
        if(! ($data = $instance->configdata)){
            return '';
        }
    
        $reportclass = report_base::get($data->reportid);
    
        if(!isset($this->reportcache[$row->id])){
            // Delete conditions - TODO
            // Add new condition
            // User report -> New condition "User courses"
            // Course report -> New condition "Course users"
            $this->set_report_data($reportclass, $instance, $row);
    
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
    
    function fix_condition_expr($condition, $count){
        switch($count){
            case 0: return '';
            case 1: return '';
            case 2: return 'c1 and c2';
            default: return $condition." and c$count";
        }
    }
    
    function get_report_columns($reportid){
        $columns = array();
    
        $compclass = $this->report->get_component('columns');
        foreach($compclass->get_plugins() as $plugclass){
            foreach($plugclass->get_instances() as $column){
                $columns[] = $plugclass->summary($column);
            }
        }
    
        return $columns;
    }
            
	function get_user_reports(){
		global $USER;
		
		$reportconfig = $this->report->config;
		if (isset($reportconfig->courseid)) {
		    $context = context_course::instance($reportconfig->courseid);
		} else {
		    $context = context_system::instance();
		}
		
		$reports = cr_get_my_reports($USER->id, $context);
		foreach($reports as $key => $report){
			if ($this->is_report_supported($report)) {
			    unset($reports[$key]);
			}
		}
		
		return $reports;
	}
	
	abstract function is_report_supported(report_base $report);
	
	abstract function set_report_data(report_base &$report, $instance, $row);
	
}

?>