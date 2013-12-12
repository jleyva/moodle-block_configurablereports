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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

$top = array();

$top[] = new tabobject('viewreport', new moodle_url('/blocks/configurable_reports/viewreport.php',
        array('id' => $report->id, 'courseid'=>$COURSE->id)),
                get_string('viewreport','block_configurable_reports'));

foreach($reportclass->components as $comptab){
	$top[] = new tabobject($comptab, new moodle_url('/blocks/configurable_reports/editcomp.php',
            array('id' => $report->id, 'comp' => $comptab, 'courseid'=>$COURSE->id)),
                get_string($comptab,'block_configurable_reports'));
}

$top[] = new tabobject('report', new moodle_url('/blocks/configurable_reports/editreport.php',
        array('id' => $report->id, 'courseid'=>$COURSE->id)),
                get_string('report','block_configurable_reports'));

$top[] = new tabobject('managereports', new moodle_url('/blocks/configurable_reports/managereport.php', array('courseid' => $course->id)),
                get_string('managereports','block_configurable_reports'));

$tabs = array($top);

print_tabs($tabs, $currenttab);


