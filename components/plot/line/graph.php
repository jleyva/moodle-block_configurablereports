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
 * @copyright  2020 Juan Leyva <juan@moodle.com>
 * @package    block_configurable_reports
 * @author     Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../../../../config.php");
require_once($CFG->dirroot . "/blocks/configurable_reports/locallib.php");

require_login();

// error_reporting(E_ALL);
// ini_set('display_erros', true);

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
    throw new moodle_exception('No such course id');
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
            break;
        }
    }

    if ($g['id'] == $id) {
        $min = optional_param('min', 0, PARAM_INT);
        $max = optional_param('max', 0, PARAM_INT);
        $abcise = optional_param('abcise', -1, PARAM_INT);

        $abciselabel = [];
        if ($abcise != -1) {
            $abciselabel = $series[$abcise]['serie'];
            unset($series[$abcise]);
        }

        require_once($CFG->dirroot . "/blocks/configurable_reports/lib/pChart2/class/pDraw.php");
        require_once($CFG->dirroot . "/blocks/configurable_reports/lib/pChart2/class/pData.php");
        require_once($CFG->dirroot . "/blocks/configurable_reports/lib/pChart2/class/pColor.php");
        require_once($CFG->dirroot . "/blocks/configurable_reports/lib/pChart2/class/pException.php");
        require_once($CFG->dirroot . "/blocks/configurable_reports/lib/pChart2/class/pCharts.php");

        // Dataset definition.
        $dataset = new \pChart\pData();
        $lastid = 0;
        $added = [];

        foreach ($series as $key => $val) {
            try {
                $dataset->addPoints($val['serie'], "Serie$key");
                $added[] = $key;
                $lastid = $key;
            } catch (Throwable $e) {
                continue;
            }
        }

        if (!empty($abciselabel)) {
            $nk = $lastid + 1;
            $dataset->addPoints($abciselabel, "Serie$nk");
            $dataset->setAbscissa("Serie$nk");
        }

        // Name the series that made it in, so the legend reads as the report's own column names.
        foreach ($added as $key) {
            $name = (string) $series[$key]['name'];
            $ishebrew = preg_match("/[\xE0-\xFA]/", iconv("UTF-8", "ISO-8859-8", $name));
            $dataset->setSerieDescription("Serie$key", ($ishebrew === 1) ? $reportclass->utf8_strrev($name) : $name);
        }

        $fontpath = $CFG->dirroot . "/blocks/configurable_reports/lib/pChart2/fonts";
        $width = 700;
        $height = 230;

        $mypicture = new \pChart\pDraw($width, $height);
        $mypicture->myData = $dataset;
        $mypicture->drawFilledRectangle(0, 0, $width, $height, ["Color" => new \pChart\pColor(255, 255, 255)]);
        $mypicture->setFontProperties([
            "FontName" => "$fontpath/Cairo-Regular.ttf",
            "FontSize" => 8,
            "Color" => new \pChart\pColor(0, 0, 0),
        ]);
        $mypicture->setGraphArea(70, 30, $width - 20, $height - 30);

        if (!empty($added)) {
            $scalesettings = [
                "DrawSubTicks" => true,
                "GridColor" => new \pChart\pColor(230, 230, 230, 50),
                "AxisColor" => new \pChart\pColor(150, 150, 150),
                "TickColor" => new \pChart\pColor(150, 150, 150),
            ];
            if ($min || $max) {
                $scalesettings["Mode"] = SCALE_MODE_MANUAL;
                $scalesettings["ManualScale"] = [0 => ["Min" => $min, "Max" => $max]];
            }
            $mypicture->drawScale($scalesettings);

            // The zero line, as the old chart drew it.
            $mypicture->drawThreshold([0], ["Color" => new \pChart\pColor(143, 55, 72), "Ticks" => 0]);

            $pcharts = new \pChart\pCharts($mypicture);
            $pcharts->drawLineChart();
            $pcharts->drawPlotChart(["PlotSize" => 2, "PlotBorder" => true, "BorderSize" => 1]);

            $mypicture->drawLegend(75, 35, ["Style" => LEGEND_BOX, "Mode" => LEGEND_HORIZONTAL]);
        }

        $mypicture->autoOutput();
    }
}
