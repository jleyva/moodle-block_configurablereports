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
 *
 * @package block_configurablereports
 * @author   Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @date 2009
 */

// TODO namespace

/**
 * Class plugin_base
 *
 * @package block_configurablereports
 * @author   Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @date 2009
 */
abstract class plugin_base {

    /**
     * @var string
     */
    public $fullname = '';

    /**
     * @var string
     */
    public $type = '';

    /**
     * @var false|mixed|stdClass|null
     */
    public $report = null;

    /**
     * @var bool
     */
    public $form = false;

    /**
     * @var array
     */
    public $cache = [];

    /**
     * @var bool
     */
    public $unique = false;

    /**
     * @var array
     */
    public $reporttypes = [];

    /**
     * @param $report
     */
    public function __construct($report) {
        global $DB;

        if (is_numeric($report)) {
            $this->report = $DB->get_record('block_configurable_reports', ['id' => $report]);
        } else {
            $this->report = $report;
        }
        $this->init();
    }

    /**
     * Summary
     *
     * @param object $data
     * @return string
     */
    public function summary(object $data): string {
        return '';
    }

    // Should be override.
    public function init() : void {
        throw new coding_exception('init method not implemented');
    }

}
