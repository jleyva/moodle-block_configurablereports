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

class plugin_coursecategories extends plugin_base {

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filtercoursecategories', 'block_configurable_reports');
        $this->reporttypes = array('courses');
    }

    public function summary($data) {
        return get_string('filtercoursecategories_summary', 'block_configurable_reports');
    }

    public function execute($finalelements, $data) {
        global $remotedb, $CFG;
        require_once($CFG->dirroot . "/course/lib.php");

        $category = optional_param('filter_coursecategories', 0, PARAM_INT);
        if (!$category) {
            return $finalelements;
        }

        $displaylist = array();
        $parents = array();
        cr_make_categories_list($displaylist, $parents);

        $coursecache = array();
        foreach ($finalelements as $key => $course) {
            if (empty($coursecache[$course])) {
                $coursecache[$course] = $remotedb->get_record('course', array('id' => $course));
            }
            $course = $coursecache[$course];
            if ($category != $course->category and (empty($parents[$course->id]) || !in_array($category, $parents[$course->id]))) {
                unset($finalelements[$key]);
            }
        }

        return $finalelements;
    }

    public function print_filter(&$mform) {
        global $remotedb, $CFG;
        require_once($CFG->dirroot . "/course/lib.php");

        $filtercategories = optional_param('filter_coursecategories', 0, PARAM_INT);

        $displaylist = array();
        $notused = array();
        cr_make_categories_list($displaylist, $notused);

        $displaylist[0] = get_string("all");
        $mform->addElement('select', 'filter_coursecategories', get_string('category'), $displaylist, $filtercategories);
        $mform->setDefault('filter_coursecategories', 0);
        $mform->setType('filter_coursecategories', PARAM_INT);
    }
}
