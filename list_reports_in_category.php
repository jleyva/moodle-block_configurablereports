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

define('AJAX_SCRIPT', true);
require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir . '/filelib.php');

$category = required_param('category', PARAM_RAW);

if (!$userandrepo = get_config('block_configurable_reports','sharedsqlrepository')) {
  echo json_encode(array());
  die;
}

$c = new curl();
$res = $c->get("https://api.github.com/repos/$userandrepo/contents/".$category);

$res = json_decode($res);

$reportlist = array();
foreach ($res as $item) {
    //echo "[ $item->type , $item->path ]";
    $report = new stdClass();
    $report->name = str_replace($category.'/','',$item->path);
    $report->fullname = $item->path;
    if ($item->type == 'file') {
        $reportlist[] = $report;
    }
}

echo json_encode($reportlist);
