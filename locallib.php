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

  /* TODO: Review need for encoding functions */
  function cr_serialize($var){
      if (!is_object($var) && !is_array($var)) {
          return $var;
      }
      return serialize(urlencode_recursive($var));
  }
  
  function cr_unserialize($var){
      if (!is_string($var)) {
          return $var;
      }
      return urldecode_recursive(unserialize($var));
  }
  
  function urlencode_recursive($var) {
    if (is_object($var)) {
        $new_var = new object();
        $properties = get_object_vars($var);
        foreach($properties as $property => $value) {
            $new_var->$property = urlencode_recursive($value);
        }

    } else if (is_array($var)) {
        $new_var = array();
        foreach($var as $property => $value) {
            $new_var[$property] = urlencode_recursive($value);
        }

    } else if (is_string($var)) {
        $new_var = urlencode($var);

    } else { // nulls, integers, etc.
        $new_var = $var;
    }

    return $new_var;
  }
  
  function urldecode_recursive($var) {
    if (is_object($var)) {
        $new_var = new object();
        $properties = get_object_vars($var);
        foreach($properties as $property => $value) {
            $new_var->$property = urldecode_recursive($value);
        }

    } else if(is_array($var)) {
        $new_var = array();
        foreach($var as $property => $value) {
            $new_var[$property] = urldecode_recursive($value);
        }

    } else if(is_string($var)) {
        $new_var = urldecode($var);

    } else {
        $new_var = $var;
    }

    return $new_var;
}

function cr_get_my_reports($userid, $context){
	global $CFG, $DB;
	
	require_once($CFG->dirroot.'/blocks/configurable_reports/reports/report.class.php');

	$params = array();
	if ($context instanceof context_course) {
	    $params['courseid'] = $context->instanceid;
	}
	if (!has_capability('block/configurable_reports:managereports', $context, $userid)) {		
		$params['ownerid'] = $userid;
	}
	
	$reports = array();
	$dbrecords = $DB->get_records('block_cr_report', $params, 'name ASC');
	foreach($dbrecords as $id => $dbrecord){
	    $reports[$id] = report_base::get($dbrecord);
	}
	
	return $reports;
}

function cr_check_report_permissions($report, $userid, $context){
    global $CFG;
    
    require_once($CFG->dirroot.'/blocks/configurable_reports/reports/report.class.php');
    
    $reportclass = report_base::get($report);
    
    return $reportclass->check_permissions($context, $userid);
}
 
//TODO: Capabilities and displayed type name to report class
function cr_get_report_plugins($courseid = null){
    $context = isset($courseid) ? context_course::instance($courseid) : context_system::instance();
          
    $pluginoptions = array();
    $report = new stdClass();
    $report->id = null;
	foreach(get_list_of_plugins('blocks/configurable_reports/reports') as $p){
	    //TODO: Make more general with specific capabilities (subplugins)
		if ($p == 'sql' && !has_capability('block/configurable_reports:managesqlreports',$context)) {
			continue;
		}
		$report->type = $p;
		$reportclass = report_base::get($report);
		$pluginoptions[$p] = $reportclass->get_typename();
	}
	
    return $pluginoptions;
}

function cr_print_tabs($reportclass, $currenttab){
    $params = array('id' => $reportclass->config->id);
    $editurl = new moodle_url('/blocks/configurable_reports/editreport.php', $params);
    $compurl = new moodle_url('/blocks/configurable_reports/editcomp.php', $params);
    $viewurl = new moodle_url('/blocks/configurable_reports/viewreport.php', $params);
    
    $top = array();
    $top[] = new tabobject('report', $editurl, get_string('report','block_configurable_reports'));
    foreach($reportclass->get_components() as $comp => $compclass){
        $top[] = new tabobject($comp, $compurl->out(true, array('comp' => $comp)), $compclass->get_typename());
    }
    $top[] = new tabobject('viewreport', $viewurl, get_string('viewreport','block_configurable_reports'));
    
    print_tabs(array($top), $currenttab);
}

function cr_get_string($identifier, $component, $a){
    
}
 
?>