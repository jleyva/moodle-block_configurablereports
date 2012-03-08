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
require_once($CFG->dirroot.'/blocks/configurable_reports/report.class.php');
require_once($CFG->dirroot.'/blocks/configurable_reports/editreport_form.php');

$type = required_param('type', null, PARAM_ALPHANUMEXT);
$courseid = optional_param('courseid', null, PARAM_INT);

$params = array('type' => $type);
if (isset($courseid)) {
    if (! ($course = $DB->get_record("course", array( "id" =>  $courseid)))) {
    	print_error('invalidcourseid');
    }
    $params['courseid'] = $courseid;
}

// Force user login in course (SITE or Course)
if ($course->id == SITEID) {
	require_login();
	$context = context_system::instance();
} else {
	require_login($course->id);		
	$context = context_course::instance($course->id);
}
// Capability check
if($report->ownerid != $USER->id){
    require_capability('block/configurable_reports:managereports', $context);
}else{
    require_capability('block/configurable_reports:manageownreports', $context);
}

$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$baseurl = new moodle_url('/blocks/configurable_reports/addreport.php', $params);
$editurl = new moodle_url('/blocks/configurable_reports/editcomp.php');
$PAGE->set_url($baseurl);
$title = get_string('newreport','block_configurable_reports');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$editform = new report_edit_form($PAGE->url, $params);
if($editform->is_cancelled()){
    redirect(new moodle_url('/blocks/configurable_reports/managereport.php'));
    
} else if ($data = $editform->get_data()) {
    $data->ownerid = $USER->id;
    $data->courseid = $course->id;
    $data->visible = 1;
    $data->jsordering = isset($data->jsordering) ? 1 : 0;
    
    $methods = array();
	foreach($data as $elname => $value){
		if(strpos($elname, 'export_') !== false){
			$methods[] = str_replace('export_', '', $elname);
		}
	}
	$data->export = implode(',', $methods);

    $newid = $DB->insert_record('block_configurable_reports_report', $data);
    add_to_log($course->id, 'configurable_reports', 'report created', $baseurl, $data->name);
    
    $reportclass = report_base::get($newid);
    $complist = $reportclass->get_component_list();
    redirect($editurl->out(false, array('id' => $newid, 'comp' => $complist[0])));
}

/* Display page */
echo $OUTPUT->header();

$editform->display();

echo $OUTPUT->footer();
