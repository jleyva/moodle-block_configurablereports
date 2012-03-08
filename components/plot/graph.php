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

require_once("../../../../config.php");
require_once($CFG->dirroot."/blocks/configurable_reports/locallib.php");
require_once($CFG->dirroot."/blocks/configurable_reports/lib/pChart/pData.class");
require_once($CFG->dirroot."/blocks/configurable_reports/lib/pChart/pChart.class");

require_login(); 

error_reporting(0);
ini_set('display_erros', false);
 
$id = required_param('id', PARAM_ALPHANUM);
$reportid = required_param('reportid', PARAM_INT);

if (! ($report = $DB->get_record('block_configurable_reports_report', array('id' => $id)))) {
    print_error('reportdoesnotexists', 'block_configurable_reports');
}
if (! ($course = $DB->get_record("course", array( "id" =>  $report->courseid)))) {
    print_error('invalidcourseid');
}
$courseid = $course->id;

// Force user login in course (SITE or Course)
if ($courseid == SITEID) {
    require_login();
    $context = context_system::instance();
} else {
    require_login($courseid);
    $context = context_course::instance($courseid);
}

$reportclass = report_base::get($report);
if (!$reportclass->check_permissions($USER->id, $context)){
	print_error("No permissions");
}

$compclass = $reportclass->get_component('plot');

$series = array();
foreach($compclass->get_plugins() as $plugin => $pluginclass){
    if ($plot = $pluginclass->get_instance($id)) {
        $series = $pluginclass->get_series($plot);
        break;
    }
}

$pluginclass->graph($series);

?>