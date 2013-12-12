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

/** Configurable Reports
  * A Moodle block for creating customizable reports
  * @package blocks
  * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
  * @date: 2009
  */

define('REPORT_CUSTOMSQL_MAX_RECORDS', 5000);

class report_sql extends report_base {

	function init(){
		$this->components = array('customsql','filters', 'template','permissions','calcs','plot');
	}

	function prepare_sql($sql) {
		global $DB, $USER, $CFG, $COURSE;

        // Enable debug mode from SQL query.
        $this->config->debug = (strpos($sql, '%%DEBUG%%') !== false) ? true : false;

        // Pass special custom undefined variable as filter.
        // Security warning !!! can be used for sql injection.
        // Use %%FILTER_VAR%% in your sql code with caution.
        $filter_var = optional_param('filter_var', '', PARAM_RAW);
        if (!empty($filter_var)) {
            $sql = str_replace('%%FILTER_VAR%%', $filter_var, $sql);
        }

        $sql = str_replace('%%USERID%%', $USER->id, $sql);
        $sql = str_replace('%%COURSEID%%', $COURSE->id, $sql);
        $sql = str_replace('%%CATEGORYID%%', $COURSE->category, $sql);

		// See http://en.wikipedia.org/wiki/Year_2038_problem
		$sql = str_replace(array('%%STARTTIME%%','%%ENDTIME%%'),array('0','2145938400'),$sql);
		$sql = str_replace('%%WWWROOT%%', $CFG->wwwroot, $sql);
		$sql = preg_replace('/%{2}[^%]+%{2}/i','',$sql);

		$sql = str_replace('?', '[[QUESTIONMARK]]', $sql);

		return $sql;
	}

	function execute_query($sql, $limitnum = REPORT_CUSTOMSQL_MAX_RECORDS) {
		global $remoteDB, $DB, $CFG;

		$sql = preg_replace('/\bprefix_(?=\w+)/i', $CFG->prefix, $sql);

        // Use a custom $DB (and not current system's $DB)
        // todo: major security issue
        $remoteDBhost = get_config('block_configurable_reports','dbhost');
        if (empty($remoteDBhost)) {
            $remoteDBhost = $CFG->dbhost;
        }
        $remoteDBname = get_config('block_configurable_reports','dbname');
        if (empty($remoteDBname)) {
            $remoteDBname = $CFG->dbname;
        }
        $remoteDBuser = get_config('block_configurable_reports','dbuser');
        if (empty($remoteDBuser)) {
            $remoteDBuser = $CFG->dbuser;
        }
        $remoteDBpass = get_config('block_configurable_reports','dbpass');
        if (empty($remoteDBpass)) {
            $remoteDBpass = $CFG->dbpass;
        }

        $db_class = get_class($DB);
        $remoteDB = new $db_class();
        $remoteDB->connect($remoteDBhost, $remoteDBuser, $remoteDBpass, $remoteDBname, $CFG->prefix);

        $starttime = microtime(true);

        if (preg_match('/\b(INSERT|INTO|CREATE)\b/i', $sql)) {
            // Run special (dangerous) queries directly.
            $results = $remoteDB->execute($sql);
        } else {
            $results = $remoteDB->get_recordset_sql($sql, null, 0, $limitnum);
        }

        // Update the execution time in the DB.
        $updaterecord = $DB->get_record('block_configurable_reports', array('id' => $this->config->id));
        $updaterecord->lastexecutiontime = round((microtime(true) - $starttime) * 1000);
        $this->config->lastexecutiontime = $updaterecord->lastexecutiontime;

        $DB->update_record('block_configurable_reports', $updaterecord);

        return $results;
	}

	function create_report(){
		global $DB, $CFG;

		$components = cr_unserialize($this->config->components);

		$filters = (isset($components['filters']['elements']))? $components['filters']['elements'] : array();
		$calcs = (isset($components['calcs']['elements']))? $components['calcs']['elements'] : array();

		$tablehead = array();
		$finalcalcs = array();
		$finaltable = array();
		$tablehead = array();

		$components = cr_unserialize($this->config->components);
		$config = (isset($components['customsql']['config']))? $components['customsql']['config'] : new stdclass;
        $totalrecords = 0;

        $sql = '';
		if(isset($config->querysql)){
			// FILTERS
			$sql = $config->querysql;
			if (!empty($filters)) {
				foreach($filters as $f){
					require_once($CFG->dirroot.'/blocks/configurable_reports/components/filters/'.$f['pluginname'].'/plugin.class.php');
					$classname = 'plugin_'.$f['pluginname'];
					$class = new $classname($this->config);
					$sql = $class->execute($sql, $f['formdata']);
				}
			}

			$sql = $this->prepare_sql($sql);

			if ($rs = $this->execute_query($sql)) {
		        foreach ($rs as $row) {
					if(empty($finaltable)){
						foreach($row as $colname=>$value){
							$tablehead[] = str_replace('_', ' ', $colname);
						}
					}
                    $array_row = array_values((array) $row);
                    foreach($array_row as $ii => $cell) {
                        $array_row[$ii] = str_replace('[[QUESTIONMARK]]', '?', $cell);
                    }
                    $totalrecords++;
					$finaltable[] = $array_row;
				}
            }
		}
        $this->sql = $sql;
        $this->totalrecords = $totalrecords;

		// Calcs

		$finalcalcs = $this->get_calcs($finaltable,$tablehead);

		$table = new stdclass;
		$table->id = 'reporttable';
		$table->data = $finaltable;
		$table->head = $tablehead;

		$calcs = new html_table();
        $calcs->id = 'calcstable';
		$calcs->data = array($finalcalcs);
		$calcs->head = $tablehead;

        if(!$this->finalreport) {
            $this->finalreport = new StdClass;
        }
		$this->finalreport->table = $table;
		$this->finalreport->calcs = $calcs;

		return true;
	}

}

