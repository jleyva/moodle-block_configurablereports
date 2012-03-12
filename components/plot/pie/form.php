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
 * @author Nick Koeppen
 */ 

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/blocks/configurable_reports/components/plot/plugin_form.class.php');

class pie_form extends plot_plugin_form {
    function definition() {
        global $DB, $USER, $CFG;

        $mform =& $this->_form;

        $plugclass = $this->_customdata['plugclass'];
        $reportclass = report_base::get($plugclass->report);
        $options = $reportclass->get_column_options();
		
		$mform->addElement('header', '', get_string('pie','block_configurable_reports'), '');

		$mform->addElement('select', 'areaname', get_string('pieareaname','block_configurable_reports'), $options);
		$mform->addElement('select', 'areavalue', get_string('pieareavalue','block_configurable_reports'), $options);
		$mform->addElement('checkbox', 'group', get_string('groupvalues','block_configurable_reports'));
				
        $this->add_action_buttons();
    }

}

?>