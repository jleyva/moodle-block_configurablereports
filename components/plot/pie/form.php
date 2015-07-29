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

if (!defined('MOODLE_INTERNAL')) {
    //  It must be included from a Moodle page.
    die('Direct access to this script is forbidden.');
}

require_once($CFG->libdir.'/formslib.php');

class pie_form extends moodleform {
    public function definition() {
        global $DB, $USER, $CFG;

        $mform =& $this->_form;
        $options = array();

        $report = $this->_customdata['report'];

        if ($report->type != 'sql') {
            $components = cr_unserialize($this->_customdata['report']->components);

            if (!is_array($components) || empty($components['columns']['elements'])) {
                print_error('nocolumns');
            }

            $columns = $components['columns']['elements'];
            foreach ($columns as $c) {
                if (!empty($c['summary'])) {
                    $options[] = $c['summary'];
                }
            }
        } else {

            require_once($CFG->dirroot.'/blocks/configurable_reports/report.class.php');
            require_once($CFG->dirroot.'/blocks/configurable_reports/reports/'.$report->type.'/report.class.php');

            $reportclassname = 'report_'.$report->type;
            $reportclass = new $reportclassname($report);

            $components = cr_unserialize($report->components);
            $config = (isset($components['customsql']['config'])) ? $components['customsql']['config'] : new \stdclass;

            if (isset($config->querysql)) {

                $sql = $config->querysql;
                $sql = $reportclass->prepare_sql($sql);
                if ($rs = $reportclass->execute_query($sql)) {
                    foreach ($rs as $row) {
                        $i = 0;
                        foreach ($row as $colname => $value) {
                            $options[$i] = str_replace('_', ' ', $colname);
                            $i++;
                        }
                        break;
                    }
                    $rs->close();
                }
            }
        }

        $mform->addElement('header',  'crformheader', get_string('coursefield', 'block_configurable_reports'), '');

        $mform->addElement('select', 'areaname', get_string('pieareaname', 'block_configurable_reports'), $options);
        $mform->addElement('select', 'areavalue', get_string('pieareavalue', 'block_configurable_reports'), $options);
        $mform->addElement('checkbox', 'group', get_string('groupvalues', 'block_configurable_reports'));

        // Buttons.
        $this->add_action_buttons(true, get_string('add'));
    }
}
