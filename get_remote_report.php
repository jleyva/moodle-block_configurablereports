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

define('AJAX_SCRIPT', true);
require(dirname(__FILE__, 3) . '/config.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/locallib.php');

$context = block_configurable_reports_require_course_login();
if (!block_configurable_reports_can_managesqlreports($context)) {
    throw new moodle_exception('nosqlpermissions', 'block_configurable_reports');
}
$reportname = required_param('reportname', PARAM_PATH);

if (!$userandrepo = get_config('block_configurable_reports', 'sharedsqlrepository')) {
    echo json_encode('');
    die;
}

$github = new \block_configurable_reports\github;
$github->set_repo($userandrepo);
$content = $github->get_file('/master/' . $reportname);

// A report file is a subject, a description and the query, in that order. Anything else is not one:
// the repository answers a request for a file it does not have with a page of its own.
$parts = explode('###', (string) $content);
if (count($parts) < 3) {
    throw new moodle_exception('errorremotereport', 'block_configurable_reports');
}

echo json_encode(trim($parts[2]));
