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

require_once($CFG->dirroot.'/blocks/configurable_reports/reports/report.class.php');

abstract class report_dataset_base extends report_base{
    
    function create_report(){
        $finalelements = $this->get_elements_by_conditions();
    
        // FILTERS    execute(finalelements, $instance)
        if ($compclass = $this->get_component('filters')) {
            foreach($compclass->get_plugins() as $plugclass){
                foreach($plugclass->get_instances() as $filter){
                    $finalelements = $plugclass->execute($finalelements, $filter);
                }
            }
        }
    
        // ORDERING
        $sqlorder = '';
        if ($compclass = $this->get_component('ordering')) {
            foreach($compclass->get_plugins() as $plugclass){
                foreach($plugclass->get_instances() as $order){
                    $sqlorder = $plugclass->execute($order);
                }
            }
        }
    
        // RETRIEVE DATA ROWS
        $rows = $this->get_rows($finalelements, $sqlorder);
    
        $table = $this->create_table(get_string('report'));
        $table->id = 'reporttable';
        
        // COLUMNS
        $compclass = $this->get_component('columns');
        foreach($rows as $row){
            $tempcols = array();
            foreach($compclass->get_plugins() as $plugclass){
                if(! ($columns = $plugclass->get_instances())){
                    continue;
                }
                foreach($columns as $column){
                    $tempcols[] = $plugclass->execute($column, $row);
                }
            }
            $table->data[] = $tempcols;
        }
    

        $this->finalreport->table = $table;
    }
    
    /**
     * @return
     */
    abstract function get_all_elements();
    
    function get_elements_by_conditions(){
        $elements = array();

        if ($condcomp = $this->get_component('conditions')) {
            $i = 1;
            foreach($condcomp->get_plugins() as $plugclass){
                foreach($plugclass->get_instances() as $condition){
                    $elements[$i] = $plugclass->execute($condition);
                    $i++;
                }
            }
        }
    
        if (empty($elements)) {
            return $this->get_all_elements();
        } else if(count($elements) == 1){
            $finalelements = $elements[1];
        } else {
            $finalelements = $condcomp->evaluate_expression($elements);
        }
    
        return $finalelements;
    }
    
    /**
     * Retrieve the base rows for the report table.
     * @param array  $finalelements    
     * @param string $sqlorder        ORDER BY SQL fragment
     * @return array Rows of the report table
     */
    abstract function get_rows(array $finalelements, $sqlorder);
    
}