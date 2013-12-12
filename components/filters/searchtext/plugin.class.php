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

class plugin_searchtext extends plugin_base{

    function init(){
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filter_searchtext','block_configurable_reports');
        $this->reporttypes = array('searchtext','sql');
    }

    function summary($data){
        return get_string('filter_searchtext_summary','block_configurable_reports');
    }

    function execute($finalelements, $data){

        $filter_searchtext = optional_param('filter_searchtext','',PARAM_RAW);
        $operators = array('=', '<', '>', '<=', '>=', '~');

        if(!$filter_searchtext)
            return $finalelements;

        if($this->report->type != 'sql'){
            return array($filter_searchtext);
        } else {
            if(preg_match("/%%FILTER_SEARCHTEXT:([^%]+)%%/i", $finalelements, $output)) {
                list($field,$operator) = preg_split('/:/',$output[1]);
                if(!in_array($operator,$operators))
                    print_error('nosuchoperator');
                if ($operator == '~') {
                    $replace = " AND ".$field." LIKE '%".$filter_searchtext."%'";
                } else {
                    $replace = ' AND '.$field.' '.$operator.' '.$filter_searchtext;
                }
                return str_replace('%%FILTER_SEARCHTEXT:'.$output[1].'%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }

    function print_filter(&$mform){

        $filter_searchtext = optional_param('filter_searchtext','',PARAM_RAW);

        $mform->addElement('text', 'filter_searchtext', get_string('filter'));
        $mform->setType('filter_searchtext', PARAM_RAW);
        $mform->setDefault('filter_searchtext', $filter_searchtext);
    }
}