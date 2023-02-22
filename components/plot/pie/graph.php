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
 * Configurable Reports
 * A Moodle block for creating customizable reports
 * @package blocks
 * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @date: 2009
 */

require_once("../../../../../config.php");
require_once($CFG->dirroot."/blocks/configurable_reports/locallib.php");

require_login();

error_reporting(0);
ini_set('display_erros', false);

$id = required_param('id', PARAM_ALPHANUM);
$reportid = required_param('reportid', PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);

if (!$report = $DB->get_record('block_configurable_reports', array('id' => $reportid))) {
    print_error('reportdoesnotexists');
}

if (!$courseid || !$report->global) {
    $courseid = $report->courseid;
}

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error("No such course id");
}

// Force user login in course (SITE or Course).
if ($course->id == SITEID) {
    require_login();
    $context = context_system::instance();
} else {
    require_login($course->id);
    $context = context_course::instance($course->id);
}

require_once($CFG->dirroot.'/blocks/configurable_reports/report.class.php');
require_once($CFG->dirroot.'/blocks/configurable_reports/reports/'.$report->type.'/report.class.php');

$reportclassname = 'report_'.$report->type;
$reportclass = new $reportclassname($report);

if (!$reportclass->check_permissions($USER->id, $context)) {
    print_error("No permissions");
} else {

    $components = cr_unserialize($report->components);
    $graphs = $components['plot']['elements'];

    if (!empty($graphs)) {
        $series = array();
        foreach ($graphs as $g) {
            require_once($CFG->dirroot.'/blocks/configurable_reports/components/plot/'.$g['pluginname'].'/plugin.class.php');
            if ($g['id'] == $id) {
                $classname = 'plugin_'.$g['pluginname'];
                $class = new $classname($report);
                $series = $class->get_series($g['formdata']);
                $colors = $class->get_color_palette($g['formdata']);
                break;
            }
        }

        if ($g['id'] == $id) {

            // Standard inclusions.
            include($CFG->dirroot."/blocks/configurable_reports/lib/pChart/pData.class");
            include($CFG->dirroot."/blocks/configurable_reports/lib/pChart/pChart.class");

            // Dataset definition.
            $dataset = new pData;

            $dataset->AddPoint($series[1], "Serie1");
            // Invert/Reverse Hebrew labels so it can be rendered using PHP imagettftext().
            foreach ($series[0] as $key => $value) {
                $invertedlabels[$key] = strip_tags((preg_match("/[\xE0-\xFA]/", iconv("UTF-8", "ISO-8859-8", $value))) ? $reportclass->utf8_strrev($value) : $value);
            }
            $dataset->AddPoint($invertedlabels /* $series[0] */, "Serie2");
            $dataset->AddAllSeries();
            $dataset->SetAbsciseLabelSerie("Serie2");

            // Initialise the graph.
            $test = new pChart(450, 200 + (count($series[0]) * 10));
            $test->drawFilledRoundedRectangle(7, 7, 293, 193, 5, 240, 240, 240);
            $test->drawRoundedRectangle(5, 5, 295, 195, 5, 230, 230, 230);
            $test->createColorGradientPalette(195, 204, 56, 223, 110, 41, 5);

            // Custom colors.
            if ($colors) {
                foreach ($colors as $index => $color) {
                    if (!empty($color)) {
                        $test->Palette[$index] = array("R" => $color[0], "G" => $color[1], "B" => $color[2]);
                    }
                }
            }

            // Draw the pie chart.
            $test->setFontProperties($CFG->dirroot."/blocks/configurable_reports/lib/Fonts/tahoma.ttf", 8);
            $test->AntialiasQuality = 0;
            $test->drawPieGraph($dataset->GetData(), $dataset->GetDataDescription(), 150, 90, 110, PIE_PERCENTAGE, true, 50, 20, 5);
            $test->drawPieLegend(300, 15, $dataset->GetData(), $dataset->GetDataDescription(), 250, 250, 250);

            ob_clean(); // Hack to clear output and send only IMAGE data to browser.
            $test->Stroke();
        }
    }
}
