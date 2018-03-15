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
 * Tile settings for a configurable report for block_configurable_reports.
 *
 * @package     block_configurable_reports
 * @author      Donald Barrett <donald.barrett@learningworks.co.nz>
 * @copyright   2018 onwards, LearningWorks ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// No direct access.
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/formslib.php');

// One day I will be namespaced. This better be the tilereport component.
require_once(dirname(__FILE__).'/component.class.php');

class tilereport_form extends \moodleform {
    public function definition() {
        global $DB, $USER, $CFG;

        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('general'));
        $mform->setExpanded('general');

        // Enable tileability.
        $mform->addElement('selectyesno', 'tileable', get_string('showontiles', 'block_configurable_reports'));
        $mform->setDefault('tileable', 0);

        // Tile name.
        $mform->addElement('text', 'tilename', get_string('tilename', 'block_configurable_reports'), ['maxlength' => 60, 'size' => 58]);
        $mform->addHelpButton('tilename', 'tilename', 'block_configurable_reports');

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('tilename', PARAM_TEXT);
        } else {
            $mform->setType('tilename', PARAM_NOTAGS);
        }

        $mform->addRule('tilename', null, 'required', null, 'client');
        $mform->disabledIf('tilename', 'tileable', 0);

        // Tile report configs.
        $mform->addElement('header', 'header_reportsummary', get_string('tilereportsummary', 'block_configurable_reports'));
        $mform->setExpanded('header_reportsummary');

        // Options to choose summary options.
        $options = [
                component_tilereport::SUMMARY_COUNT     => get_string('countsummary', 'block_configurable_reports'),
                component_tilereport::SUMMARY_CUSTOM    => get_string('customsummary', 'block_configurable_reports')
        ];
        $mform->addElement('select', 'summaryoptions', get_string('summaryoptions', 'block_configurable_reports'), $options);

        // Buttons.
        $this->add_action_buttons(true, get_string('add'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}