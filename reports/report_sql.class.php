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

abstract class report_sql_base extends report_base{
    
    function create_report(){
        $table = new html_table();
        $table->id = 'reporttable';
        $table->summary = get_string('report');
        $table->width = '80%';
        $table->tablealign = 'center';
    
        $compclass = $this->get_component('customsql');
        if (isset($compclass) && isset($compclass->config->querysql)) {
            $sql = $this->prepare_sql($compclass->config->querysql);
            $rs = $this->execute_query($sql);
            foreach ($rs as $row) {
                if(empty($table->data)){
                    foreach($row as $colname => $value){
                        $table->head[] = str_replace('_', ' ', $colname);
                    }
                }
                $table->data[] = array_values((array) $row);
            }
        }
    
        $this->finalreport->table = $table;
    }
    
    /**
     * Execute compiled SQL query to retrieve dataset.
     * @param string $sql          SQL statement
     * @param int $limitnum        Record limit
     * @return moodle_recordset    Report recordset
     */
    abstract function execute_query($sql, $limitnum);
    
    abstract function prepare_sql($sql);
    
}