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

function export_report($report){
    global $DB, $CFG;
    require_once($CFG->libdir . '/csvlib.class.php');

    $table = $report->table;
    $matrix = array();
    $filename = 'weka_arff';
    
$csvexport = new csv_export_writer();
$csvexport->set_filename($filename,'.arff');
$csvexport->csvenclosure=' ';

$mark = array('@relation WekaObjective');    
$csvexport->add_data($mark);

    if (!empty($table->head)) {
        $countcols = count($table->head);
        $keys=array_keys($table->head);
        $lastkey = end($keys);
        foreach ($table->head as $key => $heading) {
                //$matrix[0][$key] = ''; // str_replace("\n",' ','@attribute '.htmlspecialchars_decode(strip_tags(nl2br($heading))).' numeric').'\n';
$mark = array('@attribute '.$heading.' numeric');    
$csvexport->add_data($mark);
        }
    }

/*$mark = array('@attribute');    
$csvexport->add_data($mark);*/
    
    if (!empty($table->data)) {
        foreach ($table->data as $rkey => $row) {
            foreach ($row as $key => $item) {
                $matrix[$rkey + 1][$key] = str_replace("\n",' ',htmlspecialchars_decode(strip_tags(nl2br($item))));
            }
        }
    }

/*    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);*/

$mark = array('@data');    
$csvexport->add_data($mark);
    foreach($matrix as $ri=>$col){
        $csvexport->add_data($col);
        //break; // continue;
    }
    $csvexport->download_file();
    exit;
}
