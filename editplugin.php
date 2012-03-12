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

$id = required_param('id', PARAM_INT);          // Plugin id

$moveup = optional_param('moveup', 0, PARAM_INT);
$movedown = optional_param('movedown', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);

if (! ($instance = $DB->get_record('block_configurable_reports_plugin', array('id' => $id)))) {
    print_error('instancedoesnotexist');
}
if (! ($reportclass = report_base::get($instance->reportid))) {
    print_error('reportdoesnotexists');
}
$report = $reportclass->config;
$courseid = $report->courseid;
if (isset($courseid)) {
    if (! ($course = $DB->get_record("course", array( "id" =>  $courseid)))) {
        print_error('invalidcourseid');
    }

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

$baseurl = new moodle_url('/blocks/configurable_reports/editplugin.php');
$returnparams = array('id' => $report->id, 'comp' => $instance->component);
$returnurl = new moodle_url('/blocks/configurable_reports/editcomp.php', $returnparams);
$PAGE->set_url($baseurl, array('id' => $id));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

$compclass = $reportclass->get_component($instance->component);
if (!isset($compclass)) {
    print_error('badcomponent');
}
$pluginclass = $compclass->get_plugin($instance->plugin);
if ( !($instance = $pluginclass->get_instance($instance->id))){
    print_error('badinstance');
}

if ($delete && confirm_sesskey()) {
    $pluginclass->delete_instance($id);    //TODO
    
    redirect($returnurl);
}
if (($moveup || $movedown) && confirm_sesskey()){
    $pluginclass->move_instance($id);      //TODO
    
    redirect($returnurl);
}

$title = $reportclass->get_name().' '.$compclass->get_name();
$PAGE->set_title($title);
$PAGE->set_heading($title);
navigation_node::override_active_url($returnurl);
$PAGE->navbar->add($pluginclass->get_name());

$editform = $pluginclass->get_form($PAGE->url, array('id' => $id));		
$editform->set_data($instance);
	
if ($editform->is_cancelled()) {
	redirect($returnurl);
	
} else if ($data = $editform->get_data()) {
    $editform->save_data($data, $id);
    
    $logcourse = isset($courseid) ? $courseid : $SITE->id;
	add_to_log($logcourse, 'configurable_reports', 'edit', '', $report->name);

	redirect($returnurl);
}

/* Display page */

echo $OUTPUT->header();

cr_print_tabs($reportclass, $instance->component);

$editform->display();

echo $OUTPUT->footer();

?>