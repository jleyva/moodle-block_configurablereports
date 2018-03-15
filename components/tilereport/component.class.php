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
 *  for blocks_configurable_reports.
 *
 * @package     blocks_configurable_reports
 * @author      Donald Barrett <donald.barrett@learningworks.co.nz>
 * @copyright   2018 onwards, LearningWorks ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// No direct access.
defined('MOODLE_INTERNAL') || die();

if (!class_exists('component_base')) {
    global $CFG;
    require_once($CFG->dirroot.'/blocks/configurable_reports/component.class.php');
}

class component_tilereport extends component_base {

    /**
     * Defines a tile report whose summary reporting is set to count the total records.
     */
    const SUMMARY_COUNT     = 1;

    /**
     * Defines a tile report whose summary reporting is set to custom.
     */
    const SUMMARY_CUSTOM    = 2;

    public function init() {
        global $PAGE;

        $this->plugins = false;
        $this->ordering = false;
        $this->form = true;
        $this->help = true;
    }

    public function form_process_data(&$cform) {
        global $DB;
        if ($this->form) {
            $data = $cform->get_data();
            // Function cr_serialize() will add slashes.
            $components = cr_unserialize($this->config->components);
            $components['tilereport']['config'] = $data;
            $this->config->components = cr_serialize($components);
            $DB->update_record('block_configurable_reports', $this->config);
        }
    }

    public function form_set_data(&$cform) {
        if ($this->form) {
            $fdata = new stdclass;
            $components = cr_unserialize($this->config->components);
            $sqlconfig = (isset($components['tilereport']['config'])) ? $components['tilereport']['config'] : new stdclass;
            $cform->set_data($sqlconfig);
        }
    }
}