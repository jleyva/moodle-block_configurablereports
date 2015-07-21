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

class plugin_userfield extends plugin_base{

    public function init() {
        $this->fullname = get_string('userfield', 'block_configurable_reports');
        $this->type = 'undefined';
        $this->form = true;
        $this->reporttypes = array('users');
    }

    public function summary($data) {
        return format_string($data->columname);
    }

    public function colformat($data) {
        $align = (isset($data->align)) ? $data->align : '';
        $size = (isset($data->size)) ? $data->size : '';
        $wrap = (isset($data->wrap)) ? $data->wrap : '';
        return array($align, $size, $wrap);
    }

    // Data -> Plugin configuration data.
    // Row -> Complet user row c->id, c->fullname, etc...
    public function execute($data, $row, $user, $courseid, $starttime = 0, $endtime = 0) {
        global $DB, $CFG;

        if (strpos($data->column, 'profile_') === 0) {
            $sql = "SELECT d.*, f.shortname, f.datatype
                      FROM {user_info_data} d ,{user_info_field} f
                     WHERE f.id = d.fieldid AND d.userid = ?";
            if ($profiledata = $DB->get_records_sql($sql, array($row->id))) {
                foreach ($profiledata as $p) {
                    if ($p->datatype == 'checkbox') {
                        $p->data = ($p->data) ? get_string('yes') : get_string('no');
                    }
                    if ($p->datatype == 'datetime') {
                        $p->data = userdate($p->data);
                    }
                    $row->{'profile_'.$p->shortname} = $p->data;
                }
            }
        }

        $row->fullname = fullname($row);

        if (isset($row->{$data->column})) {
            switch($data->column){
                case 'firstaccess':
                case 'lastaccess':
                case 'currentlogin':
                case 'timemodified':
                case 'lastlogin':
                    $row->{$data->column} = ($row->{$data->column}) ? userdate($row->{$data->column}) : '--';
                    break;
                case 'confirmed':
                case 'policyagreed':
                case 'maildigest':
                case 'ajax':
                case 'autosubscribe':
                case 'trackforums':
                case 'screenreader':
                case 'emailstop':
                    $row->{$data->column} = ($row->{$data->column}) ? get_string('yes') : get_string('no');
                    break;
            }
        }
        return (isset($row->{$data->column})) ? $row->{$data->column} : '';
    }
}
