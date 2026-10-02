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
 * @package    block_configurable_reports
 * @copyright  2027 Configurable Reports contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form to create/edit a recurring combined-email bundle: a named, scheduled email that
 * always sends a fixed set of reports together, to a fixed list of recipients.
 *
 * @package   block_configurable_reports
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editbundle_form extends moodleform {

    /**
     * Form definition
     */
    public function definition(): void {
        global $PAGE;

        $mform =& $this->_form;
        /** @var stdClass[] $availablereports Reports the current user can bundle, keyed by id. */
        $availablereports = $this->_customdata['availablereports'];

        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
        $mform->setType('courseid', PARAM_INT);

        if (!empty($this->_customdata['id'])) {
            $mform->addElement('hidden', 'id', $this->_customdata['id']);
            $mform->setType('id', PARAM_INT);
        }

        $mform->addElement('text', 'name', get_string('bundlename', 'block_configurable_reports'), ['size' => 60]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addHelpButton('name', 'bundlename', 'block_configurable_reports');
        $mform->addRule('name', null, 'required', null, 'client');

        $reportoptions = [];
        foreach ($availablereports as $report) {
            $reportoptions[$report->id] = format_string($report->name);
        }
        $mform->addElement(
            'select',
            'reportids',
            get_string('bundlereports', 'block_configurable_reports'),
            $reportoptions,
            ['multiple' => true, 'size' => min(10, max(3, count($reportoptions)))]
        );
        $mform->setType('reportids', PARAM_INT);
        $mform->addHelpButton('reportids', 'bundlereports', 'block_configurable_reports');
        $mform->addRule('reportids', null, 'required', null, 'client');

        $mform->addElement(
            'select',
            'emailschedule',
            get_string('emailschedule', 'block_configurable_reports'),
            cr_get_recurring_schedule_options()
        );
        $mform->addHelpButton('emailschedule', 'emailschedule', 'block_configurable_reports');
        $mform->setDefault('emailschedule', 1);

        $mform->addElement(
            'textarea',
            'emailto',
            get_string('emailto', 'block_configurable_reports'),
            ['rows' => 3, 'cols' => 50, 'id' => 'id_emailto']
        );
        $mform->setType('emailto', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('emailto', 'emailto', 'block_configurable_reports');
        $mform->addRule('emailto', null, 'required', null, 'client');

        $mform->addElement('text', 'subject', get_string('email_subject', 'block_configurable_reports'), ['size' => 60]);
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required', null, 'client');

        $editoroptions = [
            'trusttext' => true,
            'subdirs' => 0,
            'maxfiles' => 0,
        ];
        $mform->addElement('editor', 'message', get_string('email_message', 'block_configurable_reports'), null, $editoroptions);
        $mform->setType('message', PARAM_RAW);

        $this->add_action_buttons(true, get_string('savechanges'));

        $PAGE->requires->js_init_code(cr_email_chip_input_js('id_emailto'), true);
    }

    /**
     * Validate recipients and the selected reports.
     *
     * @param array $data
     * @param array $files
     * @return array Errors, keyed by element name.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $emailto = trim((string) ($data['emailto'] ?? ''));
        if ($emailto === '') {
            $errors['emailto'] = get_string('emailtorequired', 'block_configurable_reports');
        } else {
            $invalid = cr_get_invalid_email_recipients($emailto);
            if (!empty($invalid)) {
                $errors['emailto'] = get_string('invalidemail', 'block_configurable_reports', implode(', ', $invalid));
            }
        }

        if (empty($data['reportids'])) {
            $errors['reportids'] = get_string('bundlereportsrequired', 'block_configurable_reports');
        }

        return $errors;
    }

}
