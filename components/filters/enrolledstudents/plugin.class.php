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

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_enrolledstudents extends plugin_base{

	function init(){
		$this->form = false;
		$this->unique = true;
		$this->fullname = get_string('filterenrolledstudents', 'block_configurable_reports');
		$this->reporttypes = array('courses','sql');
	}

	function summary($data){
		return get_string('filterenrolledstudents_summary', 'block_configurable_reports');
	}

	function execute($finalelements, $data){

		$filter_enrolledstudents = optional_param('filter_enrolledstudents', 0, PARAM_INT);
		if(!$filter_enrolledstudents)
			return $finalelements;

		if($this->report->type != 'sql'){
				return array($filter_enrolledstudents);
		} else {
			if (preg_match("/%%FILTER_COURSEENROLLEDSTUDENTS:([^%]+)%%/i", $finalelements, $output)) {
				$replace = ' AND '.$output[1].' = '.$filter_enrolledstudents;
				return str_replace('%%FILTER_COURSEENROLLEDSTUDENTS:'.$output[1].'%%', $replace, $finalelements);
			}
		}
		return $finalelements;
	}

	function print_filter(&$mform){
		global $remoteDB, $COURSE;

		//$filter_enrolledstudents = optional_param('filter_enrolledstudents',0,PARAM_INT);

		$reportclassname = 'report_'.$this->report->type;
		$reportclass = new $reportclassname($this->report);

		if($this->report->type != 'sql'){
			$components = cr_unserialize($this->report->components);
			$conditions = $components['conditions'];

			$enrolledstudentslist = $reportclass->elements_by_conditions($conditions);
		} else {
            //$coursecontext = context_course::instance($COURSE->id);
            //$enrolledstudentslist = array_keys(get_users_by_capability($coursecontext, 'moodle/user:viewdetails'));
//            $sql = "SELECT DISTINCT u.id
//                      FROM {user} u
//                      JOIN {user_enrolments} ue ON (ue.userid = u.id)
//                      JOIN {enrol} e ON (e.id = ue.enrolid)
//                     WHERE e.roleid = 5 AND e.courseid = {$COURSE->id}";

            $sql = "SELECT ra.userid
                        FROM {role_assignments} AS ra
                        JOIN {context} AS context ON ra.contextid = context.id AND context.contextlevel = 50
                        WHERE ra.roleid=5 AND context.instanceid = {$COURSE->id}";

            //echo $sql;die;
            $studentlist = $remoteDB->get_records_sql($sql);
            //print_object($enrolledstudentslist);die;
            foreach($studentlist as $student) {
                $enrolledstudentslist[] = $student->userid;
            }

		}

		$enrolledstudentsoptions = array();
		$enrolledstudentsoptions[0] = get_string('filter_all', 'block_configurable_reports');

		if(!empty($enrolledstudentslist)){
			list($usql, $params) = $remoteDB->get_in_or_equal($enrolledstudentslist);
			$enrolledstudents = $remoteDB->get_records_select('user',"id $usql",$params);

			foreach($enrolledstudents as $c){
				$enrolledstudentsoptions[$c->id] = format_string($c->lastname.' '.$c->firstname);
			}
		}

		$mform->addElement('select', 'filter_enrolledstudents', get_string('student', 'block_configurable_reports'), $enrolledstudentsoptions);
		$mform->setType('filter_enrolledstudents', PARAM_INT);

	}

}

