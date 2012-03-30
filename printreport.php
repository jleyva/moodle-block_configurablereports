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

if (! ($report = $DB->get_record('block_configurable_reports_report', array('id' => $id)))) {
    print_error('reportdoesnotexists', 'block_configurable_reports');
}
$courseid = $report->courseid;
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
	
$reportclass = report_base::get($report);
if (!$reportclass->check_permissions($context)){
	print_error("badpermissions",'block_configurable_reports');
}

$PAGE->set_context($context);
$PAGE->set_url('/blocks/configurable_reports/viewreport.php', array('id'=>$id));
$reportname = format_string($report->name);
$PAGE->set_title($reportname);

$reportclass->create_report();

$logcourse = isset($courseid) ? $courseid : $SITE->id;
add_to_log($logcourse, 'configurable_reports', 'print', '/block/configurable_reports/viewreport.php?id='.$id, $report->name);

/* Display page */

echo '<html><body>';

$reportclass->print_report_page($context);

echo '</body></html>';

?>