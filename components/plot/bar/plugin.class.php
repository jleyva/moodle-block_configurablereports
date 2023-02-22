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

class plugin_bar extends plugin_base {

    public function init() {
        $this->fullname = "Bar chart";
        $this->form = true;
        $this->ordering = true;
        $this->reporttypes = array('courses', 'sql', 'users', 'timeline', 'categories');
    }

    public function summary($data) {
        return "Bar chart summary";
    }

    // Data -> Plugin configuration data.
    public function execute($id, $data, $finalreport) {
        global $DB, $CFG;
        $series = array();
        if ($finalreport) {
            list($labelidx, $labelname) = explode(",", $data->label_field);
            $series[$labelname] = array();
            if (!is_array($data->value_fields)) {
                $data->value_fields = array($data->value_fields);
            }
            foreach ($finalreport as $r) {
                $series[$labelname][] = $r[$labelidx];
                foreach ($data->value_fields as $valuefields) {
                    list($idx, $name) = explode(",", $valuefields);
                    $value = $r[$idx];

                    if ($idx == $labelidx) {
                        error_log("moodle:configurable_reports:bar:  refusing to chart label field");
                        continue;
                    }

                    if (!is_numeric($value)) {
                        // Can't just skip. That would throw off the indexes if a column has bad values in some but not all rows.
                        error_log("moodle:configurable_reports:bar:  substituting 0 for non-numeric value '$value'");
                        $value = 0;
                    }

                    if (!array_key_exists($name, $series)) {
                        $series[$name] = array();
                    }
                    $series[$name][] = $value;
                }
            }
        }

        $graphdata = urlencode(json_encode($series));

        return $CFG->wwwroot.'/blocks/configurable_reports/components/plot/bar/graph.php?reportid='.$this->report->id.'&courseid='.$this->report->courseid.'&id='.$id.'&graphdata='.$graphdata;
    }

    public function get_series($data) {
        $graphdataraw = required_param('graphdata', PARAM_RAW);
        $graphdata = json_decode(urldecode($graphdataraw));
        return (array)$graphdata;
    }
}
