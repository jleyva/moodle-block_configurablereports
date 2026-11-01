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
 * @package    block_configurable_reports
 * @author     Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../../../../config.php");
require_once($CFG->dirroot . "/blocks/configurable_reports/locallib.php");

require_login();


$id = required_param('id', PARAM_ALPHANUM);
$reportid = required_param('reportid', PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);

if (!$report = $DB->get_record('block_configurable_reports', ['id' => $reportid])) {
    throw new moodle_exception('reportdoesnotexists');
}

if (!$courseid || !$report->global) {
    $courseid = $report->courseid;
}

if (!$course = $DB->get_record('course', ['id' => $courseid])) {
    throw new moodle_exception("No such course id");
}

// Force user login in course (SITE or Course).
if ((int) $course->id === SITEID) {
    require_login();
    $context = context_system::instance();
} else {
    require_login($course->id);
    $context = context_course::instance($course->id);
}

require_once($CFG->dirroot . '/blocks/configurable_reports/report.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/reports/' . $report->type . '/report.class.php');

$reportclassname = 'report_' . $report->type;
$reportclass = new $reportclassname($report);

if (!$reportclass->check_permissions($USER->id, $context)) {
    throw new moodle_exception("No permissions");
}

$components = cr_unserialize($report->components);
$graphs = $components['plot']['elements'];

if (!empty($graphs)) {
    $series = [];
    foreach ($graphs as $g) {
        $classname = cr_require_component_plugin('plot', $g['pluginname']);
        if ($g['id'] == $id) {
            $class = new $classname($report);
            $series = $class->get_series($g['formdata']);
            $colors = $class->get_color_palette($g['formdata']);
            break;
        }
    }

    if ($g['id'] == $id) {

        require_once($CFG->dirroot . "/blocks/configurable_reports/lib/pChart2/class/pDraw.php");
        require_once($CFG->dirroot . "/blocks/configurable_reports/lib/pChart2/class/pData.php");
        require_once($CFG->dirroot . "/blocks/configurable_reports/lib/pChart2/class/pColor.php");
        require_once($CFG->dirroot . "/blocks/configurable_reports/lib/pChart2/class/pException.php");
        require_once($CFG->dirroot . "/blocks/configurable_reports/lib/pChart2/class/pPie.php");

        // Invert/Reverse Hebrew labels so it can be rendered using PHP imagettftext().
        $invertedlabels = [];
        foreach ($series[0] as $key => $value) {
            $invertedlabels[$key] = strip_tags(
                (preg_match("/[\xE0-\xFA]/", iconv("UTF-8", "ISO-8859-8", $value))) ? $reportclass->utf8_strrev($value) : $value
            );
        }

        // The values are added first, because getPieParams() plots the first serie that is not the abscissa.
        $dataset = new \pChart\pData();
        $dataset->addPoints($series[1], "Values");
        $dataset->addPoints($invertedlabels, "Labels");
        $dataset->setAbscissa("Labels");

        // Custom colors, one per slice. savePalette() merges, so only the ones set here are replaced.
        if ($colors) {
            $palette = [];
            foreach ($colors as $index => $color) {
                if (!empty($color)) {
                    $palette[$index] = new \pChart\pColor((int) $color[0], (int) $color[1], (int) $color[2]);
                }
            }
            $dataset->savePalette($palette);
        }

        $fontpath = $CFG->dirroot . "/blocks/configurable_reports/lib/pChart2/fonts";
        $width = 450;
        $height = 200 + (count($series[0]) * 10);
        $radius = 90;

        $mypicture = new \pChart\pDraw($width, $height);
        $mypicture->myData = $dataset;
        $mypicture->setFontProperties([
            "FontName" => "$fontpath/Cairo-Regular.ttf",
            "FontSize" => 8,
            "Color" => new \pChart\pColor(0, 0, 0),
        ]);

        $pie = new \pChart\pPie($mypicture);
        $pie->draw2DPie($radius + 20, intdiv($height, 2), [
            "Radius" => $radius,
            // The names are in the legend: labels drawn outside the pie throw when a long one runs off the canvas.
            "DrawLabels" => false,
            "WriteValues" => PIE_VALUE_PERCENTAGE,
            "ValuePosition" => PIE_VALUE_INSIDE,
            "Border" => true,
        ]);
        $pie->drawPieLegend($width - 120, 15, ["Alpha" => 0]);

        $mypicture->autoOutput();
    }

}
