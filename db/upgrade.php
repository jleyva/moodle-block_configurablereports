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
 * This file keeps track of upgrades to the enrolment options plugin
 *
 * @package    block
 * @subpackage configurable_reports
 * @copyright  2011 Nick Koeppen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_block_configurable_reports_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012030800) {
        $table = new xmldb_table('block_configurable_reports_report');
        // Change report courseid to allow NULL (site-wide report)
        $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, 'id');
        $field->setNotNull(false);
        $dbman->change_field_notnull($table, $field);
        // Drop old "components" serialized field
        $field = new xmldb_field('components', XMLDB_TYPE_TEXT, 'small', null, false, null, null, 'pagination');
        if($dbman->field_exists($table, $field)){
            $dbman->drop_field($table, $field);
        }
        
        $file = $CFG->dirroot.'/blocks/configurable_reports/db/install.xml';
        // Add component table
        $table = 'block_configurable_reports_component';
        if (!$dbman->table_exists($table)) {
            $dbman->install_one_table_from_xmldb_file($file, $table);
        }
        // Add plugins table
        $table = 'block_configurable_reports_plugin';
        if (!$dbman->table_exists($table)) {
            $dbman->install_one_table_from_xmldb_file($file, $table);
        }
        
        upgrade_plugin_savepoint(true, 2012030800, 'block', 'configurable_reports');
    }

    return true;
}
