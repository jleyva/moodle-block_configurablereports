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

$id = required_param('id', PARAM_INT);
$comp = required_param('comp', PARAM_ALPHA);

$moveup = optional_param('moveup', 0, PARAM_INT);
$movedown = optional_param('movedown', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);

// Figure out new plugin or editing
$params = array('id' => $id, 'comp' => $comp);
if ($pid = optional_param('pid', null, PARAM_INT)) {
    $params['pid'] = $pid;
} else if ($plug = optional_param('plug', null, PARAM_ALPHA)){
    $params['plug'] = $plug;
} else {
    redirect(new moodle_url('/blocks/configurable_reports/editcomp.php', $params));
}

if (! ($report = $DB->get_record('block_configurable_reports_report', array('id' => $id)))) {
    print_error('reportdoesnotexists');
}
if (! ($course = $DB->get_record("course", array( "id" => $report->courseid)))) {
    print_error('invalidcourseid');
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

$baseurl = new moodle_url('/blocks/configurable_reports/editplugin.php');
$returnurl = new moodle_url('/blocks/configurable_reports/editcomp.php', array('id' => $id, 'comp' => $comp));
$PAGE->set_url($baseurl, $params);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

$reportclass = report_base::get($report);
$compclass = $reportclass->get_component($comp);
if (!isset($compclass)) {
    print_error('badcomponent');
}
$compconfig = $reportclass->get_component_config($comp);
$pluginclass = $compclass->get_plugin($plug);

if ($delete && confirm_sesskey()) {
    
}
if (($moveup || $movedown) && confirm_sesskey()){

    $DB->update_record('block_configurable_reports_report', $report);
    
    redirect($returnurl);
}

$title = $reportclass->get_name().' '.$compclass->get_name();
$PAGE->set_title($title);
$PAGE->set_heading($title);
navigation_node::override_active_url($returnurl);
$PAGE->navbar->add($pluginclass->get_name());

if($pluginclass->form){
    $customdata = compact('comp','cid','id','pluginclass','compclass','report','reportclass');
    $editform = $compclass->get_form($PAGE->url, $customdata);
	if(!empty($cdata)){		
		$editform->set_data($cdata['formdata']);
	}
		
	if ($editform->is_cancelled()) {
		redirect($returnurl);
		
	} else if ($data = $editform->get_data()) {	
		add_to_log($report->courseid, 'configurable_reports', 'edit', '', $report->name);
		if (!empty($cdata)) {
			// cr_serialize() will add slashes
			
			$cdata['formdata'] = $data;
			$cdata['summary'] = $pluginclass->summary($data);
			
			if($elements)
				foreach($elements as $key=>$e){
					if ($e['id'] == $cid){
						$elements[$key] = $cdata;
						break;
					}
				}		
			
			$allelements = cr_unserialize($report->components);
			$allelements[$comp]['elements'] = $elements;
			
			$report->components = cr_serialize($allelements);
			$DB->update_record('block_configurable_reports_report', $report);
			redirect($returnurl);
		} else {
			$allelements = cr_unserialize($report->components);
			
			$uniqueid = random_string(15);
			while(strpos($report->components,$uniqueid) !== false){
				$uniqueid = random_string(15);
			}
			
			$cdata = array('id' => $uniqueid, 'formdata' => $data, 'pluginname' => $pname, 'pluginfullname' => $pluginclass->fullname, 'summary' => $pluginclass->summary($data));
			
			$allelements[$comp]['elements'][] = $cdata;
			$report->components = cr_serialize($allelements, false);
			
			$DB->update_record('block_configurable_reports_report',$report);
			redirect(new moodle_url('/blocks/configurable_reports/editcomp.php', array('id' => $id, 'comp' => $comp)));
		}
	}
} else {			
	$uniqueid = random_string(15);
	while(strpos($report->components,$uniqueid) !== false){
		$uniqueid = random_string(15);
	}
	
	$cdata = array('id' => $uniqueid, 'formdata' => new stdclass, 'pluginname' => $pname, 'pluginfullname' => $pluginclass->fullname, 'summary' => $pluginclass->summary(new stdclass));
	
	$allelements = cr_unserialize($report->components);
	$allelements[$comp]['elements'][] = $cdata;
	$report->components = cr_serialize($allelements);
	$DB->update_record('block_configurable_reports_report', $report);
	redirect($returnurl);
}

/* Display page */

echo $OUTPUT->header();

$currenttab = $comp;
include('tabs.php');

if ($pluginclass->form) {
	$editform->display();
}

echo $OUTPUT->footer();

?>