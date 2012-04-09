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

$type = required_param('type', PARAM_ALPHANUMEXT);
$courseid = optional_param('courseid', null, PARAM_INT);

$params = array('type' => $type);
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
// TODO: Capability check

$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$baseurl = new moodle_url('/blocks/configurable_reports/addreport.php', $params);
$editurl = new moodle_url('/blocks/configurable_reports/editcomp.php');
$PAGE->set_url($baseurl);

$title = get_string('newreport','block_configurable_reports');
$PAGE->navbar->add($title);
$PAGE->set_title($title);
$PAGE->set_heading($title);

$newreport = new stdClass();
$newreport->id = null;
$newreport->type = $type;
$newreport->courseid = $courseid;
$reportclass = report_base::get($newreport);

$editform = new report_edit_form($PAGE->url, array('reportclass' => $reportclass));
if($editform->is_cancelled()){
    redirect(new moodle_url('/blocks/configurable_reports/managereport.php'));
    
} else if ($data = $editform->get_data()) {
    $data->ownerid = $USER->id;
    $data->courseid = $courseid;
    $data->visible = 1;
    $data->jsordering = isset($data->jsordering) ? 1 : 0;

    $newid = $DB->insert_record('block_configurable_reports_report', $data);
    $logcourse = isset($courseid) ? $courseid : $SITE->id;
    add_to_log($logcourse, 'configurable_reports', 'report created', $baseurl, $data->name);
    
    $reportclass = report_base::get($newid);
    
    foreach($reportclass->get_form_components() as $compclass){
        $compclass->save_report_formdata($data);
    }
    
    $complist = $reportclass->component_classes();
    reset($complist);
    redirect($editurl->out(false, array('id' => $newid, 'comp' => key($complist))));
}

/* Display page */
echo $OUTPUT->header();

echo $OUTPUT->heading($reportclass->get_typename());

$editform->display();

echo $OUTPUT->footer();
