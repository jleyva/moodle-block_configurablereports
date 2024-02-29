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

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/blocks/configurable_reports/plugin.class.php');

/**
 * Class plugin_ccoursefield
 *
 * @package   block_configurable_reports
 * @author    Juan leyva <http://www.twitter.com/jleyvadelgado>
 */
class plugin_ccoursefield extends plugin_base {

    /**
     * Init
     *
     * @return void
     */
    public function init(): void {
        $this->fullname = get_string('ccoursefield', 'block_configurable_reports');
        $this->reporttypes = ['courses'];
        $this->form = true;
    }

    /**
     * Summary
     *
     * @param object $data
     * @return string
     */
    public function summary(object $data): string {
        return get_string($data->field) . ' ' . $data->operator . ' ' . $data->value;

    }

    /**
     * Execute
     *
     * @param object $data
     * @return array|int[]|string[]
     */
    public function execute($data) {
        global $DB;

        // TODO - Use DB -> sql_like().
        $ilike = " LIKE ";

        switch ($data->operator) {
            case 'LIKE % %':
                $sql = "$data->field $ilike ?";
                $params = ["%$data->value%"];
                break;
            default:
                $sql = "$data->field $data->operator ?";
                $params = [$data->value];
        }

        $courses = $DB->get_records_select('course', $sql, $params);

        if ($courses) {
            return array_keys($courses);
        }

        return [];
    }

}
