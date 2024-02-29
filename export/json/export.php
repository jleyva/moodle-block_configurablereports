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
 * @author     Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package    block_configurable_reports
 */

/**
 * Export report
 *
 * @param object $report
 * @return void
 */
function export_report($report) {
    $table = $report->table;
    $filename = 'report_' . (time()) . '.json';
    $json = [];
    $headers = $table->head;
    foreach ($table->data as $data) {
        $jsonobject = [];
        foreach ($data as $index => $value) {
            $jsonobject[$headers[$index]] = $value;
        }
        $json[] = $jsonobject;
    }

    $downloadfilename = clean_filename($filename);
    header('Content-disposition: attachment; filename=' . $downloadfilename);
    header('Content-type: application/json');
    echo json_encode($json);
    exit;
}
