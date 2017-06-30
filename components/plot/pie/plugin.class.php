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

class plugin_pie extends plugin_base{

    public function init() {
        $this->fullname = get_string('pie', 'block_configurable_reports');
        $this->form = true;
        $this->ordering = true;
        $this->reporttypes = array('courses', 'sql', 'users', 'timeline', 'categories');
    }

    public function summary($data) {
        return get_string('piesummary', 'block_configurable_reports');
    }

    // Data -> Plugin configuration data.
    public function execute($id, $data, $finalreport) {
        global $DB, $CFG;

        $series = array();
        if ($finalreport) {
            foreach ($finalreport as $r) {
                if ($data->areaname == $data->areavalue) {
                    $hash = md5(strtolower($r[$data->areaname]));
                    if (isset($series[0][$hash])) {
                        $series[1][$hash] += 1;
                    } else {
                        $series[0][$hash] = str_replace(',', '', $r[$data->areaname]);
                        $series[1][$hash] = 1;
                    }

                } else if (!isset($data->group) || ! $data->group) {
                    $series[0][] = str_replace(',', '', $r[$data->areaname]);
                    $series[1][] = (isset($r[$data->areavalue]) && is_numeric($r[$data->areavalue])) ? $r[$data->areavalue] : 0;
                } else {
                    $hash = md5(strtolower($r[$data->areaname]));
                    if (isset($series[0][$hash])) {
                        $series[1][$hash] += (isset($r[$data->areavalue]) && is_numeric($r[$data->areavalue])) ? $r[$data->areavalue] : 0;
                    } else {
                        $series[0][$hash] = str_replace(',', '', $r[$data->areaname]);
                        $series[1][$hash] = (isset($r[$data->areavalue]) && is_numeric($r[$data->areavalue])) ? $r[$data->areavalue] : 0;
                    }
                }
            }
        }

        $serie0 = base64_encode(strip_tags(implode(',', $series[0])));
        $serie1 = base64_encode(implode(',', $series[1]));

        return $CFG->wwwroot.'/blocks/configurable_reports/components/plot/pie/graph.php?reportid='.$this->report->id.'&id='.$id.'&serie0='.$serie0.'&serie1='.$serie1;
    }

    public function get_series($data) {
        $serie0 = required_param('serie0', PARAM_RAW);
        $serie1 = required_param('serie1', PARAM_RAW);

        return array(explode(',', base64_decode($serie0)), explode(',', base64_decode($serie1)));
    }
}
