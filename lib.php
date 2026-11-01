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
 * Library with callbacks.
 *
 * @package    block_configurable_reports
 * @category   check
 * @copyright  2020 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add security check.
 *
 * @return array check
 */
function block_configurable_reports_security_checks(): array {
    return [new block_configurable_reports\check\sql_execution()];
}

/**
 * Check that GD was built with freetype support.
 *
 * The graphs label themselves with imagettftext(), which GD only provides when it was built with freetype.
 * Moodle core checks that GD is there, but not that it can draw text, so check that here.
 *
 * @param environment_results $result
 * @return environment_results|null Null when freetype is available, which is what a passing check returns.
 */
function block_configurable_reports_check_freetype(environment_results $result): ?environment_results {
    if (function_exists('imagettftext') && function_exists('imagettfbbox')) {
        return null;
    }

    $result->setStatus(false);
    // An array is passed so that the string is looked up in this plugin: a plain one would be read from admin.
    $result->setFeedbackStr(['freetypenotfound', 'block_configurable_reports']);

    return $result;
}

