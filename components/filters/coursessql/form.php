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
 * Form for coursessql filter
 *
 * @copyright  2025 think-modular
 * @package    block_configurable_reports
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Class coursessql_form
 *
 * @copyright  2025 think-modular
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 */
class coursessql_form extends moodleform {

    /**
     * Form definition
     */
    public function definition(): void {
        $mform =& $this->_form;

        $mform->addElement('header', 'crformheader', get_string('coursessql', 'block_configurable_reports'), '');

        $mform->addElement('textarea', 'sql', get_string('coursessql_sql', 'block_configurable_reports'));
        $mform->setType('sql', PARAM_RAW);
        $mform->addHelpButton('sql', 'coursessql_sql', 'block_configurable_reports');
        $mform->setDefault('sql', 'SELECT * FROM {course} WHERE category = 1');

        // Buttons.
        $this->add_action_buttons(true, get_string('add', 'block_configurable_reports'));
    }

}
