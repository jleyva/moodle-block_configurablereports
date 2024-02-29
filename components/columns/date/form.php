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
 * Class date_form
 *
 * @package   block_configurable_reports
 * @author    Juan leyva <http://www.twitter.com/jleyvadelgado>
 */
class date_form extends moodleform {

    /**
     * Form definition
     */
    public function definition(): void {

        $mform =& $this->_form;

        $mform->addElement('header', 'crformheader', get_string('date', 'block_configurable_reports'), '');

        $this->_customdata['compclass']->add_form_elements($mform, $this);

        $params = [
            'starttime' => get_string('starttime', 'block_configurable_reports'),
            'endtime' => get_string('endtime', 'block_configurable_reports'),
        ];
        $mform->addElement('select', 'date', get_string('date', 'block_configurable_reports'), $params);

        $formats = ['' => get_string('default'), 'custom' => get_string('custom', 'block_configurable_reports')];
        foreach (['%A, %d %B %Y', '%d %B %Y', '%d/%B/%Y', '%B %d %Y'] as $f) {
            $formats[$f] = $f;
        }

        $mform->addElement('select', 'dateformat', get_string('dateformat', 'block_configurable_reports'), $formats);
        $mform->addElement('text', 'customdateformat', get_string('customdateformat', 'block_configurable_reports'));
        $mform->setType('customdateformat', PARAM_RAW);
        $mform->disabledIf('customdateformat', 'dateformat', 'neq', 'custom');

        // Buttons.
        $this->add_action_buttons(true, get_string('add'));
    }

}
