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

class plugin_yearnumeric extends plugin_base {

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filteryearnumeric', 'block_configurable_reports');
        $this->reporttypes = array('categories', 'sql');
    }

    public function summary($data) {
        return get_string('filteryearnumeric_summary', 'block_configurable_reports');
    }

    public function execute($finalelements, $data) {

        $filteryearnumeric = optional_param('filter_yearnumeric', 0, PARAM_INT);
        if (!$filteryearnumeric) {
            return $finalelements;
        }

        if ($this->report->type != 'sql') {
            return array($filteryearnumeric);
        } else {
            if (preg_match("/%%FILTER_YEARNUMERIC:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND '.$output[1].' LIKE \'%'.$filteryearnumeric.'%\'';
                return str_replace('%%FILTER_YEARNUMERIC:'.$output[1].'%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }

    public function print_filter(&$mform) {
        global $remotedb, $CFG;

        $filteryearnumeric = optional_param('filter_yearnumeric', 0, PARAM_INT);

        $reportclassname = 'report_'.$this->report->type;
        $reportclass = new $reportclassname($this->report);

        $calendartype = \core_calendar\type_factory::get_calendar_instance();
        for($year = $calendartype->get_min_year(); $year < $calendartype->get_max_year(); $year++) {
            $yearnumeric[$year] = $year;
        }

        if ($this->report->type != 'sql') {
            $components = cr_unserialize($this->report->components);
            $conditions = $components['conditions'];

            $yearnumericlist = $reportclass->elements_by_conditions($conditions);
        } else {
            $yearnumericlist = array_keys($yearnumeric);
        }

        $yearnumericoptions = array();
        $yearnumericoptions[0] = get_string('filter_all', 'block_configurable_reports');

        if (!empty($yearnumericlist)) {
            // Todo: check that keys of yearnumeric array items are available.
            foreach ($yearnumeric as $key => $year) {
                $yearnumericoptions[$key] = $year;
            }
        }

        $elestr = get_string('filteryearnumeric', 'block_configurable_reports');
        $mform->addElement('select', 'filter_yearnumeric', $elestr, $yearnumericoptions);
        $mform->setType('filter_yearnumeric', PARAM_INT);
    }
}
