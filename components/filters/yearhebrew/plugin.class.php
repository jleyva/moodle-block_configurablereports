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

class plugin_yearhebrew extends plugin_base{

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filteryearhebrew', 'block_configurable_reports');
        $this->reporttypes = array('categories', 'sql');
    }

    public function summary($data) {
        return get_string('filteryearhebrew_summary', 'block_configurable_reports');
    }

    public function execute($finalelements, $data) {

        $filteryearhebrew = optional_param('filter_yearhebrew', '', PARAM_RAW);
        if (!$filteryearhebrew) {
            return $finalelements;
        }

        if ($this->report->type != 'sql') {
            return array($filteryearhebrew);
        } else {
            if (preg_match("/%%FILTER_YEARHEBREW:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND '.$output[1].' LIKE \'%'.$filteryearhebrew.'%\'';
                return str_replace('%%FILTER_YEARHEBREW:'.$output[1].'%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }

    public function print_filter(&$mform) {
        global $remotedb, $CFG;

        $filteryearhebrew = optional_param('filter_yearhebrew', 0, PARAM_RAW);

        $reportclassname = 'report_'.$this->report->type;
        $reportclass = new $reportclassname($this->report);
        foreach (explode(',', get_string('filteryearhebrew_list', 'block_configurable_reports')) as $value) {
            $yearhebrew[$value] = $value;
        }

        if ($this->report->type != 'sql') {
            $components = cr_unserialize($this->report->components);
            $conditions = $components['conditions'];

            $yearhebrewlist = $reportclass->elements_by_conditions($conditions);
        } else {
            $yearhebrewlist = array_keys($yearhebrew);
        }

        $yearhebrewoptions = array();
        $yearhebrewoptions[0] = get_string('filter_all', 'block_configurable_reports');

        if (!empty($yearhebrewlist)) {
            // Todo: check that keys of yearhebrew array items are available.
            foreach ($yearhebrew as $key => $year) {
                $yearhebrewoptions[$key] = $year;
            }
        }

        $elestr = get_string('filteryearhebrew', 'block_configurable_reports');
        $mform->addElement('select', 'filter_yearhebrew', $elestr, $yearhebrewoptions);
        $mform->setType('filter_yearhebrew', PARAM_RAW);
    }
}
