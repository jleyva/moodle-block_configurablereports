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

class plugin_coursemodules extends plugin_base {

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filtercoursemodules', 'block_configurable_reports');
        $this->reporttypes = array('courses', 'sql');
    }

    public function summary($data) {
        return get_string('filtercoursemodules_summary', 'block_configurable_reports');
    }

    public function execute($finalelements, $data) {
        global $remotedb;

        $filtercoursemoduleid = optional_param('filter_coursemodules', 0, PARAM_INT);
        if (!$filtercoursemoduleid) {
            return $finalelements;
        }

        if ($this->report->type != 'sql') {
            return array($filtercoursemoduleid);
        } else {
            if (preg_match("/%%FILTER_COURSEMODULEID:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND '.$output[1].' = '.$filtercoursemoduleid;
                $finalelements = str_replace('%%FILTER_COURSEMODULEID:'.$output[1].'%%', $replace, $finalelements);
            }
            if (preg_match("/%%FILTER_COURSEMODULEFIELDS:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' '.$output[1].' ';
                $finalelements = str_replace('%%FILTER_COURSEMODULEFIELDS:'.$output[1].'%%', $replace, $finalelements);
            }
            if (preg_match("/%%FILTER_COURSEMODULE:([^%]+)%%/i", $finalelements, $output)) {
                $module = $remotedb->get_record('modules', array('id' => $filtercoursemoduleid));
                $replace = ' JOIN {'.$module->name.'} AS m ON m.id = '.$output[1].' ';
                $finalelements = str_replace('%%FILTER_COURSEMODULE:'.$output[1].'%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }

    public function print_filter(&$mform) {
        global $remotedb;

        $filtercoursemoduleid = optional_param('filter_coursemodules', 0, PARAM_INT);

        $reportclassname = 'report_'.$this->report->type;
        $reportclass = new $reportclassname($this->report);

        if ($this->report->type != 'sql') {
            $components = cr_unserialize($this->report->components);
            $conditions = $components['conditions'];

            $coursemodulelist = $reportclass->elements_by_conditions($conditions);
        } else {
            $coursemodulelist = array_keys($remotedb->get_records('modules'));
        }

        $courseoptions = array();
        $courseoptions[0] = get_string('filter_all', 'block_configurable_reports');

        if (!empty($coursemodulelist)) {
            list($usql, $params) = $remotedb->get_in_or_equal($coursemodulelist);
            $coursemodules = $remotedb->get_records_select('modules', "id $usql", $params);

            foreach ($coursemodules as $c) {
                $courseoptions[$c->id] = format_string(get_string('pluginname', $c->name).' = '.$c->name);
            }
        }

        $elestr = get_string('filtercoursemodules', 'block_configurable_reports');
        $mform->addElement('select', 'filter_coursemodules', $elestr, $courseoptions);
        $mform->setType('filter_coursemodules', PARAM_INT);
    }
}
