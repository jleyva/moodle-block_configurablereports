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

/**
 * Configurable Reports
 * A Moodle block for creating customizable reports
 * @package blocks
 * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @date: 2009
 */
 
 /**
 * COHORT FILTER
 * A filter for configurable reports
 * @author: François Parlant <https://www.linkedin.com/in/francois-parlant/>
 * @date: 2020
 */ 
 
 /* example of report query
 ***********
 * Display the students from a cohort and all the courses they are enrolled in
 ***********
SELECT
u.firstname AS Firstname,
u.lastname AS Lastname,
u.email AS Email,
c.fullname AS Course

FROM prefix_course AS c 
JOIN prefix_enrol AS en ON en.courseid = c.id
JOIN prefix_user_enrolments AS ue ON ue.enrolid = en.id
JOIN prefix_user AS u ON ue.userid = u.id
WHERE u.id in (SELECT u.id
FROM prefix_cohort AS h
JOIN prefix_cohort_members AS hm ON h.id = hm.cohortid
JOIN prefix_user AS u ON hm.userid = u.id
WHERE 1=1
%%FILTER_COHORTS:h.id%%
ORDER BY u.firstname)
 
 */
 

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_cohorts extends plugin_base{

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filtercohorts', 'block_configurable_reports');
        $this->reporttypes = array('courses', 'sql');
    }

    public function summary($data) {
        return get_string('filtercohorts_summary', 'block_configurable_reports');
    }

    public function execute($finalelements, $data) {
		
        $filtercohorts = optional_param('filter_cohorts', 0, PARAM_INT);
        if (!$filtercohorts) {
            return $finalelements;
        }

        if ($this->report->type != 'sql') {
            return array($filtercohorts);
        } else {
            if (preg_match("/%%FILTER_COHORTS:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND '.$output[1].' = '.$filtercohorts;
                return str_replace('%%FILTER_COHORTS:'.$output[1].'%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }

    public function print_filter(&$mform) {
        global $remotedb, $COURSE, $PAGE, $CFG;

        $reportclassname = 'report_'.$this->report->type;
        $reportclass = new $reportclassname($this->report);

        if ($this->report->type != 'sql') {
            $components = cr_unserialize($this->report->components);
            $conditions = $components['conditions'];

            $cohortslist = $reportclass->elements_by_conditions($conditions);
        } else {
            $sql = 'SELECT  h.id, h.name
                      FROM {cohort} h
                      ';
            $studentlist = $remotedb->get_records_sql($sql);
            foreach ($studentlist as $student) {
                $cohortslist[] = $student->userid;
            }
			
        }

        $cohortsoptions = array();
        $cohortsoptions[0] = get_string('filter_all', 'block_configurable_reports');

        if (!empty($cohortslist)) {
            
            $cohorts = $remotedb->get_records_sql($sql);

            foreach ($cohorts as $c) {
                $cohortsoptions[$c->id] = $c->name;
            }
        }

        $elestr = get_string('cohorts', 'block_configurable_reports');
        $mform->addElement('select', 'filter_cohorts', $elestr, $cohortsoptions);
        $mform->setType('filter_cohorts', PARAM_INT);
    }
}
