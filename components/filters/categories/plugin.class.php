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

class plugin_categories extends plugin_base {

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filtercategories', 'block_configurable_reports');
        $this->reporttypes = array('categories', 'sql');
    }

    public function summary($data) {
        return get_string('filtercategories_summary', 'block_configurable_reports');
    }

    public function execute($finalelements, $data) {

        $filtercategories = optional_param('filter_categories', 0, PARAM_INT);
        if (!$filtercategories) {
            return $finalelements;
        }

        if ($this->report->type != 'sql') {
            return array($filtercategories);
        } else {
            if (preg_match("/%%FILTER_CATEGORIES:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND '.$output[1].' = '.$filtercategories;
                return str_replace('%%FILTER_CATEGORIES:'.$output[1].'%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }

    public function print_filter(&$mform) {
        global $remotedb, $CFG;

        $filtercategories = optional_param('filter_categories', 0, PARAM_INT);

        $reportclassname = 'report_'.$this->report->type;
        $reportclass = new $reportclassname($this->report);

        if ($this->report->type != 'sql') {
            $components = cr_unserialize($this->report->components);
            $conditions = $components['conditions'];

            $categorieslist = $reportclass->elements_by_conditions($conditions);
        } else {
            $categorieslist = array_keys($remotedb->get_records('course_categories'));
        }

        $courseoptions = array();
        $courseoptions[0] = get_string('filter_all', 'block_configurable_reports');

        if (!empty($categorieslist)) {
            list($usql, $params) = $remotedb->get_in_or_equal($categorieslist);
            $categories = $remotedb->get_records_select('course_categories', "id $usql", $params);

            foreach ($categories as $c) {
                $courseoptions[$c->id] = format_string($c->name);
            }
        }

        $mform->addElement('select', 'filter_categories', get_string('category'), $courseoptions);
        $mform->setType('filter_categories', PARAM_INT);
    }
}
