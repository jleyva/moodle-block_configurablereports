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
    global $CFG, $DB, $OUTPUT, $SITE;

    $dbman = $DB->get_manager();

    /* Restructured components and plugins to a subplugin-like API */
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
    
    /* Moved export formats into component/plugin API */
    if ($oldversion < 2012031902) {
        require_once($CFG->dirroot.'/blocks/configurable_reports/locallib.php');
        // Move export configuration data to plugin table        
        $exports = array();
        $reports = $DB->get_records('block_configurable_reports_report');
        foreach($reports as $id => $report){
            $exports[$id] = explode(',', $report->export);
        }
        
        $compdata = new stdClass();
        $compdata->component = 'export';
        foreach($exports as $reportid => $exports){
            $compdata->reportid = $reportid;
            $compdata->configdata = cr_serialize($exports);
            $DB->insert_record('block_configurable_reports_component', $compdata);
        }
        
        // Drop old "exports" CSV field
        $table = new xmldb_table('block_configurable_reports_report');
        $field = new xmldb_field('export', XMLDB_TYPE_CHAR, '255', null, false, false, null, 'pagination');
        if($dbman->field_exists($table, $field)){
            $dbman->drop_field($table, $field);
        }
        
        upgrade_plugin_savepoint(true, 2012031902, 'block', 'configurable_reports');
    }
    
    /* Implement JS using graceful degradation - no need for option */
    if ($oldversion < 2012033000) {
        $table = new xmldb_table('block_configurable_reports_report');
        // Drop jsordering field        
        $field = new xmldb_field('jsordering', XMLDB_TYPE_INTEGER, '4', true, false, null, null, 'pagination');
        if($dbman->field_exists($table, $field)){
            $dbman->drop_field($table, $field);
        }
        
        upgrade_plugin_savepoint(true, 2012033000, 'block', 'configurable_reports');
    }
    
    /* Convert courseid field to a contextid field */
    if ($oldversion < 2012040600) {
        $table = new xmldb_table('block_configurable_reports_report');
        // Drop courseid key
        $key = new xmldb_key('course', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));
        $dbman->drop_key($table, $key);
        // Convert entries from courseid to contextid
        $records = $DB->get_records('block_configurable_reports_report');
        $syscontext = context_system::instance();
        foreach($records as $record){
            if (!isset($record->courseid) || $record->courseid == $SITE->id) {
                $context = $syscontext;
            } else {
                $context = context_course::instance($record->courseid);
            }
            $DB->set_field('block_configurable_reports_report', 'courseid', $context->id);
        }
        // Rename courseid field
        $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, !XMLDB_NOTNULL, null, null, 'id');
        $dbman->rename_field($table, $field, 'contextid');
        // Add contextid key
        $key = new xmldb_key('context', XMLDB_KEY_FOREIGN, array('contextid'), 'context', array('id'));
        $dbman->add_key($table, $key);
        
        upgrade_plugin_savepoint(true, 2012040600, 'block', 'configurable_reports');
    }

    return true;
}
