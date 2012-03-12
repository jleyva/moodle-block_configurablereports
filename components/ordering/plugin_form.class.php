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
    die('Direct access to this script is forbidden.');
}

require_once($CFG->dirroot.'/blocks/configurable_reports/components/plugin_form.class.php');

abstract class ordering_plugin_form extends plugin_form {
    
    function common_form_elements() {
        $mform =& $this->_form;
    
        $plugclass = $this->_customdata['plugclass'];
        $columns = $plugclass->get_columns();
        $mform->addElement('select', 'column', get_string('column','block_configurable_reports'), $columns);
    
        $dirs = array('asc'=>'ASC', 'desc'=>'DESC');
        $mform->addElement('select', 'direction', get_string('direction','block_configurable_reports'), $dirs);
    }
}