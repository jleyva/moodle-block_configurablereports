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

/** Configurable Reports
  * A Moodle block for creating customizable reports
  * @package blocks
  * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
  * @date: 2009
  */

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_usercompletion extends plugin_base{

    function init(){
        $this->fullname = get_string('usercompletion','block_configurable_reports');
        $this->type = 'undefined';
        $this->form = false;
        $this->reporttypes = array('users');
    }

    function summary($data){
        return get_string('usercompletionsummary','block_configurable_reports');
    }

    function colformat($data){
        $align = (isset($data->align))? $data->align : '';
        $size = (isset($data->size))? $data->size : '';
        $wrap = (isset($data->wrap))? $data->wrap : '';
        return array($align,$size,$wrap);
    }

    // data -> Plugin configuration data
    // row -> Complet user row c->id, c->fullname, etc...
    function execute($data,$row,$user,$courseid,$starttime=0,$endtime=0){
        global $DB, $CFG;

        require_once("{$CFG->libdir}/completionlib.php");

        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

        $info = new completion_info($course);

        // Is course complete?
        $coursecomplete = $info->is_course_complete($row->id);

        // Load course completion.
        $params = array(
            'userid' => $row->id,
            'course' => $course->id
        );
        $ccompletion = new completion_completion($params);

        // Has this user completed any criteria?
        $criteriacomplete = $info->count_course_user_data($row->id);

        $content = "";
        if ($coursecomplete) {
            $content .= get_string('complete');
        } else if (!$criteriacomplete && !$ccompletion->timestarted) {
            $content .= html_writer::tag('i', get_string('notyetstarted', 'completion'));
        } else {
            $content .= html_writer::tag('i', get_string('inprogress', 'completion'));
        }
        return $content;
    }
}
