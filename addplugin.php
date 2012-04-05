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

$id   = required_param('id', PARAM_INT);        // Report id
$comp = required_param('comp', PARAM_ALPHA);    // Component name
$plug = required_param('plug', PARAM_ALPHA);    // Plugin name

if (! ($report = $DB->get_record('block_configurable_reports_report', array('id' => $id)))) {
    print_error('reportdoesnotexists');
}
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

$baseurl = new moodle_url('/blocks/configurable_reports/addplugin.php');
$returnurl = new moodle_url('/blocks/configurable_reports/editcomp.php', array('id' => $id, 'comp' => $comp));
$PAGE->set_url($baseurl, array('id' => $id, 'comp' => $comp, 'plug' => $plug));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

$reportclass = report_base::get($report);
$compclass = $reportclass->get_component($comp);
if (!isset($compclass)) {
    print_error('badcomponent');
}
$pluginclass = $compclass->get_plugin($plug);

$title = format_string($reportclass->config->name).' '.$compclass->get_type();
$PAGE->set_title($title);
$PAGE->set_heading($title);

navigation_node::override_active_url($returnurl);
$PAGE->navbar->add($pluginclass->get_type());

if ($pluginclass->has_form()) {
    $editform = $pluginclass->get_form($PAGE->url);
		
	if ($editform->is_cancelled()) {
		redirect($returnurl);
		
	} else if ($data = $editform->get_data()) {	
	    $logcourse = isset($courseid) ? $courseid : $SITE->id;
		add_to_log($logcourse, 'configurable_reports', 'edit', '', $report->name);
		
		$editform->save_data($data);
		
		redirect($returnurl);
	}
} else {
    $pluginclass->add_instance();
    
	redirect($returnurl);
}

/* Display page */

echo $OUTPUT->header();

echo $OUTPUT->heading($reportclass->get_typename());

cr_print_tabs($reportclass, $comp);

$editform->display();

echo $OUTPUT->footer();

?>