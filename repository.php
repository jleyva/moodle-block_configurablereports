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

$action = required_param('action', PARAM_ALPHA);

if (!$userandrepo = get_config('block_configurable_reports','crrepository')) {
  echo json_encode(array());
  die;
}

$c = new curl();
if ($action == 'listreports') {
  if ($res = $c->get("https://api.github.com/repos/$userandrepo/contents/")) {
    $data = json_decode($res);
    if (!is_array($data)) {
      echo json_encode(array());
      die;
    }
    foreach ($data as $key => $d) {
      if ($d->type != 'dir') {
        unset($data[$key]);
      }
    }
    echo json_encode($data);
    die;
  }
} else if ($action == 'listcategory') {
  $category = required_param('category', PARAM_RAW);
  if ($res = $c->get("https://api.github.com/repos/$userandrepo/contents/$category")) {
    $data = json_decode($res);
    foreach ($data as $key => $d) {
      if ($d->type != 'file') {
        unset($data[$key]);
      }
    }
    echo json_encode($data);
    die;
  }
}
echo json_encode(array());
