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

require_once($CFG->dirroot.'/blocks/configurable_reports/components/columns/reportcolumn/plugin.class.php');

class plugin_reportcolumn_course extends plugin_reportcolumn{
	
	function set_report_data(report_base &$report, $instance, $row){
	    global $DB;
	    if(! ($data = $instance->configdata)){
	        return;
	    }

	    $report->currentcourseid = $row->id;
        $report->starttime = $starttime;
        $report->endtime = $endtime;
        
        $components = cr_unserialize($report->config->components);
        	
        $roles = $DB->get_records('role');
        $rolesid = array_keys($roles);
        	
        $formdata = new stdclass;
        $formdata->roles = $rolesid;
        $newplugin = array(
            'pluginname' => 'usersincurrentcourse',
            'fullname' => 'usersincurrentcourse',
            'formdata' => $formdata
        );
        	
        $components['conditions']['elements'][] = $newplugin;
        $components['conditions']['config']->conditionexpr = $this->fix_condition_expr($components['conditions']['config']->conditionexpr, count($components['conditions']['elements']));
        $report->config->components = cr_serialize($components);
	}
	
	function is_report_supported(report_base $report){
	    return $report->get_type() == 'users';
	}
}

?>
