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
 * Configurable Reports a Moodle block for creating customizable reports
 *
 * @copyright  2020 Juan Leyva <juan@moodle.com>
 * @package    block_configurable_reports
 * @author     Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/formslib.php');

/**
 * Class coursecategory_form
 *
 * @package   block_configurable_reports
 * @author    Juan leyva <http://www.twitter.com/jleyvadelgado>
 */
class coursecategory_form extends moodleform {

    /**
     * Form definition
     */
    public function definition(): void {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $mform =& $this->_form;

        $mform->addElement('header', 'crformheader', get_string('coursefield', 'block_configurable_reports'), '');

        $options = [get_string('top')];
        $parents = [];
        cr_make_categories_list($options, $parents);
        $mform->addElement('select', 'categoryid', get_string('category'), $options);

        // Buttons.
        $this->add_action_buttons(true, get_string('add'));

    }

}
