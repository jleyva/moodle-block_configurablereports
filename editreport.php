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
require_once($CFG->dirroot.'/blocks/configurable_reports/reports/report.class.php');
require_once($CFG->dirroot.'/blocks/configurable_reports/reports/report_form.class.php');

$id = required_param('id', PARAM_INT);

$delete = optional_param('delete', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$show = optional_param('show', 0, PARAM_BOOL);
$hide = optional_param('hide', 0, PARAM_BOOL);
$duplicate = optional_param('duplicate', 0, PARAM_BOOL);

if (! ($report = $DB->get_record('block_configurable_reports_report', array('id' => $id)))) {
    print_error('reportdoesnotexists', 'block_configurable_reports');
}
$courseid = $report->courseid;

$params = array();
if (isset($courseid)) {
    if (! ($course = $DB->get_record("course", array( "id" =>  $courseid)))) {
        print_error("nosuchcourseid", 'block_configurable_reports');
    }
    $params['courseid'] = $courseid;

    require_login($courseid);
    $context = context_course::instance($courseid);
} else {
    require_login();
    $context = context_system::instance();
}
// Capability check
if($report->ownerid != $USER->id){
    require_capability('block/configurable_reports:managereports', $context);
}else{
    require_capability('block/configurable_reports:manageownreports', $context);
}

$baseurl = new moodle_url('/blocks/configurable_reports/editreport.php');
$manageurl = new moodle_url('/blocks/configurable_reports/managereport.php', $params);
$editurl = new moodle_url('/blocks/configurable_reports/editcomp.php', array('id' => $id));
$PAGE->set_url($baseurl, array('id'=>$id));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

$reportclass = report_base::get($report);

$title = format_string($report->name);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add(get_string('managereports','block_configurable_reports'), $manageurl);
$PAGE->navbar->add($title);

// Common actions
if(($show || $hide) && confirm_sesskey()){
	$DB->set_field('block_configurable_reports_report', 'visible', $show, array('id' => $report->id));
	$action = ($show) ? 'shown' : 'hidden';	
	add_to_log($report->courseid, 'configurable_reports', 'report '.$action, '/block/configurable_reports/editreport.php?id='.$report->id, $report->id);	
	
	redirect($manageurl);
}
if ($duplicate && confirm_sesskey()) {
	$newreport = new stdclass();
	$newreport = $report;
	unset($newreport->id);
	$newreport->name = get_string('copyasnoun').' '.$newreport->name;
	$newreport->summary = $newreport->summary;
	$newreportid = $DB->insert_record('block_configurable_reports_report', $newreport);
	add_to_log($newreport->courseid, 'configurable_reports', 'report duplicated', '/block/configurable_reports/editreport.php?id='.$newreportid, $id);
	
	redirect($manageurl);
}
if ($delete && confirm_sesskey()){
	if (!$confirm) {
		echo $OUTPUT->header();		
		$message = get_string('confirmdeletereport', 'block_configurable_reports');
		$confirmurl = $baseurl;
		$confirmurl->params(array('id'=>$report->id, 'delete'=>$delete, 'sesskey'=>sesskey(), 'confirm'=>1));
		$buttoncontinue = new single_button($confirmurl, get_string('yes'));
		$buttoncancel   = new single_button($manageurl, get_string('no'));
		echo $OUTPUT->confirm($message, $buttoncontinue, $buttoncancel);
		echo $OUTPUT->footer();
		exit;
	} else if ($DB->delete_records('block_configurable_reports_report', array('id'=>$report->id))) {
		add_to_log($report->courseid, 'configurable_reports', 'report deleted', '/block/configurable_reports/editreport.php?id='.$report->id, $report->id);
	
	    redirect($manageurl);
	}
}

$reportparams = array('id' => $id, 'type' => $report->type, 'courseid' => $courseid);
$editform = new report_edit_form($PAGE->url, $reportparams);
$export = explode(',', $report->export);
if (!empty($export)) {
	foreach($export as $e){
		$report->{'export_'.$e} = 1;
	}
}
$editform->set_data($report);
	
if ($editform->is_cancelled()) {
	redirect($manageurl);
	
} else if ($data = $editform->get_data()) {
    $methods = array();
	foreach($data as $elname => $value){
		if(strpos($elname, 'export_') !== false){
			$methods[] = str_replace('export_', '', $elname);
		}
	}
	$data->export = implode(',', $methods);
	
	$DB->update_record('block_configurable_reports_report', $data);
	$logcourse = isset($courseid) ? $courseid : $SITE->id;
	add_to_log($logcourse, 'configurable_reports', 'edit', '/block/configurable_reports/editreport.php?id='.$id, $report->name);
	
    $complist = $reportclass->component_classes();
    redirect($editurl->out(false, array('comp' => key($complist))));
}

/* Display page */
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('report_'.$report->type, 'block_configurable_reports'));

cr_print_tabs($reportclass, 'report'); 

$editform->display();

echo $OUTPUT->footer();

?>