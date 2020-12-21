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

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_users extends plugin_base {

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filterusers', 'block_configurable_reports');
        $this->reporttypes = array('courses', 'sql');
    }

    public function summary($data) {
        return get_string('filterusers_summary', 'block_configurable_reports');
    }

    public function execute($finalelements, $data) {

        $filterusers = optional_param('filter_users', 0, PARAM_INT);
        if (!$filterusers) {
            return $finalelements;
        }

        if ($this->report->type != 'sql') {
            return array($filterusers);
        } else {
            if (preg_match("/%%FILTER_SYSTEMUSER:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND '.$output[1].' = '.$filterusers;
                return str_replace('%%FILTER_SYSTEMUSER:'.$output[1].'%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }

    public function print_filter(&$mform) {
        global $remotedb, $PAGE, $CFG;

        $reportclassname = 'report_'.$this->report->type;
        $reportclass = new $reportclassname($this->report);

        if ($this->report->type != 'sql') {
            $components = cr_unserialize($this->report->components);
            $conditions = $components['conditions'];

            $userslist = $reportclass->elements_by_conditions($conditions);
        } else {
            $userslist = array_keys($remotedb->get_records('user'));
        }

        $usersoptions = array();
        $usersoptions[0] = get_string('filter_all', 'block_configurable_reports');

        if (!empty($userslist)) {
	        if (has_capability('moodle/site:viewfullnames', $PAGE->context)) {
		       $nameformat = $CFG->alternativefullnameformat;
	        } else {
		       $nameformat = $CFG->fullnamedisplay;
	        }

	        if ($nameformat == 'language') {
		        $nameformat = get_string('fullnamedisplay');
	        }

            $sort = implode(',', order_in_string(get_all_user_name_fields(), $nameformat));

            list($usql, $params) = $remotedb->get_in_or_equal($userslist);
            $users = $remotedb->get_records_select('user', "id " . $usql, $params, $sort, 'id,' .get_all_user_name_fields(true));

            foreach ($users as $c) {
                $usersoptions[$c->id] = fullname($c);
            }
        }

        $mform->addElement('select', 'filter_users', get_string('users'), $usersoptions);
        $mform->setType('filter_users', PARAM_INT);
    }
}
