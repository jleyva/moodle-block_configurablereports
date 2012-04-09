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
  * A Moodle block for creating Configurable Reports
  * @package blocks
  * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
  * @date: 2009
  */ 

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/blocks/configurable_reports/components/component_form.class.php');

class contexts_form extends component_form {
    
    function definition() {
        $mform =& $this->_form;

        $allowedtypes = $this->_customdata['compclass']->get_allowed_context_types();
        foreach(context_helper::get_all_levels() as $contextlevel => $context){
            $contextname = context_helper::get_level_name($contextlevel);
            $options = array();
            if (!isset($allowedtypes[$contextlevel])) {
                $contextname = html_writer::tag('span', $contextname, array('style' => 'color:#A9A9A9;'));
                $options['disabled'] = 'disabled';
            }
            $element = &$mform->createElement('checkbox', $contextlevel, '', " ".$contextname, $options);
            $elements[$contextlevel] = $element;
        }
        $mform->addElement('group', 'contexts', get_string('context', 'role'), $elements, array('<br>'));
        
        $this->add_action_buttons();
    }
    
}

?>