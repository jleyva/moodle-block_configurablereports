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

    require_once("../../config.php");
	require_once($CFG->dirroot."/blocks/configurable_reports/locallib.php");
	
	$id = required_param('id', PARAM_INT);
	$download = optional_param('download',false,PARAM_BOOL);
	$format = optional_param('format','',PARAM_ALPHA);
	
	if(! $report = $DB->get_record('block_configurable_reports_report',array('id' => $id)))
		print_error('reportdoesnotexists','block_configurable_reports');

	$courseid = $report->courseid;
	
	if (! $course = $DB->get_record("course",array( "id" =>  $courseid)) ) {
		print_error("No such course id");
	}

	// Force user login in course (SITE or Course)
    if ($course->id == SITEID)
		require_login();
	else
		require_login($course);

	
	if ($course->id == SITEID)
		$context = get_context_instance(CONTEXT_SYSTEM);
	else
		$context = get_context_instance(CONTEXT_COURSE, $course->id);
		
	require_once($CFG->dirroot.'/blocks/configurable_reports/report.class.php');
	require_once($CFG->dirroot.'/blocks/configurable_reports/reports/'.$report->type.'/report.class.php');
	
	$reportclassname = 'report_'.$report->type;	
	$reportclass = new $reportclassname($report);

	if (!$reportclass->check_permissions($USER->id, $context)){
		print_error("badpermissions",'block_configurable_reports');
	}

	$PAGE->set_context($context);
	$PAGE->set_pagelayout('report');
	$PAGE->set_url('/blocks/configurable_reports/viewreport.php', array('id'=>$id));
	
	$reportclass->create_report();
		
	$download = ($download && $format && strpos($report->export,$format.',') !== false)? true : false;
	
	$action = ($download)? 'download' : 'view';
	add_to_log($report->courseid, 'configurable_reports', $action, '/block/configurable_reports/viewreport.php?id='.$id, $report->name);
	
	// No download, build navigation header etc..
	if(!$download){
		$reportname = format_string($report->name);
		$navlinks = array();

		if(has_capability('block/configurable_reports:managereports', $context) || (has_capability('block/configurable_reports:manageownreports', $context)) && $report->ownerid == $USER->id )
			$navlinks[] = array('name' => get_string('managereports','block_configurable_reports'), 'link' => $CFG->wwwroot.'/blocks/configurable_reports/managereport.php?courseid='.$report->courseid, 'type' => 'title');
		
		$navlinks[] = array('name' => $reportname, 'link' => null, 'type' => 'title');
			
		$navigation = build_navigation($navlinks);

		$PAGE->set_title($reportname);
		$PAGE->set_heading( $reportname);
		$PAGE->set_cacheable( true);
		echo $OUTPUT->header();
		
		if(has_capability('block/configurable_reports:managereports', $context) || (has_capability('block/configurable_reports:manageownreports', $context)) && $report->ownerid == $USER->id ){
			$currenttab = 'viewreport';
			include('tabs.php');
		}
		
		// Print the report HTML	
		$reportclass->print_report_page($context);
	}
	else{
		$exportplugin = $CFG->dirroot.'/blocks/configurable_reports/export/'.$format.'/export.php';
		if(file_exists($exportplugin)){
			require_once($exportplugin);
			export_report($reportclass->finalreport);
		}
		die;
	}
	
	
	// Never reached if download = true
    echo $OUTPUT->footer();

?>