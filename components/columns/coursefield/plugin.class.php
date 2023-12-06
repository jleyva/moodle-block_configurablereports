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
 *
 * @package block_configurablereports
 * @author   Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @date 2009
 */
defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/blocks/configurable_reports/plugin.class.php');

class plugin_coursefield extends plugin_base {

    /**
     * @return void
     */
    public function init() : void {
        $this->fullname = get_string('coursefield', 'block_configurable_reports');
        $this->type = 'undefined';
        $this->form = true;
        $this->reporttypes = ['courses'];
    }

    /**
     * @param $data
     * @return string
     */
    public function summary($data) {
        return format_string($data->columname);
    }

    /**
     * @param $data
     * @return array
     */
    public function colformat($data) {
        $align = $data->align ?? '';
        $size = $data->size ?? '';
        $wrap = $data->wrap ?? '';

        return [$align, $size, $wrap];
    }

    // Data -> Plugin configuration data.
    // Row -> Complet course row c->id, c->fullname, etc...
    public function execute($data, $row, $user, $courseid, $starttime = 0, $endtime = 0) {

        if (isset($row->{$data->column})) {
            switch ($data->column) {
                case 'enrolstartdate':
                case 'enrolenddate':
                case 'startdate':
                    $row->{$data->column} = ($row->{$data->column}) ? userdate($row->{$data->column}) : '--';
                    break;
                case 'visible':
                case 'enrollable':
                    $row->{$data->column} = ($row->{$data->column}) ? get_string('yes') : get_string('no');
                    break;
            }
        }

        return $row->{$data->column} ?? '';
    }

}
