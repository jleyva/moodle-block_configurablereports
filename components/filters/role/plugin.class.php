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

class plugin_role extends plugin_base{

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filterrole', 'block_configurable_reports');
        $this->reporttypes = array('categories', 'sql');
    }

    public function summary($data) {
        return get_string('filterrole_summary', 'block_configurable_reports');
    }

    public function execute($finalelements, $data) {

        $filterrole = optional_param('filter_role', 0, PARAM_INT);
        if (!$filterrole) {
            return $finalelements;
        }

        if ($this->report->type != 'sql') {
            return array($filterrole);
        } else {
            if (preg_match("/%%FILTER_ROLE:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND '.$output[1].' = '.$filterrole.' ';
                return str_replace('%%FILTER_ROLE:'.$output[1].'%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }

    public function print_filter(&$mform) {
        global $remotedb, $CFG;

        $filterrole = optional_param('filter_role', 0, PARAM_INT);

        $reportclassname = 'report_'.$this->report->type;
        $reportclass = new $reportclassname($this->report);

        $systemroles = $remotedb->get_records('role');
        $roles = array();
        foreach ($systemroles as $role) {
            $roles[$role->id] = $role->shortname;
        }

        if ($this->report->type != 'sql') {
            $components = cr_unserialize($this->report->components);
            $conditions = $components['conditions'];

            $rolelist = $reportclass->elements_by_conditions($conditions);
        } else {
            $rolelist = $roles;
        }

        $roleoptions = array();
        $roleoptions[0] = get_string('filter_all', 'block_configurable_reports');

        if (!empty($rolelist)) {
            // Todo: check that keys of role array items are available.
            foreach ($rolelist as $key => $role) {
                $roleoptions[$key] = $role;
            }
        }

        $mform->addElement('select', 'filter_role', get_string('filterrole', 'block_configurable_reports'), $roleoptions);
        $mform->setType('filter_role', PARAM_INT);
    }
}
