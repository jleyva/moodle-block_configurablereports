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

class plugin_base{

	var $fullname = '';
	var $type = '';
	var $report = null;
	var $form = false;
	var $cache = array();
	var $unique = false;
	var $reporttypes = array();

	function __construct($report){
		global $DB, $CFG, $remoteDB;

		if(is_numeric($report))
			$this->report = $DB->get_record('block_configurable_reports',array('id' => $report));
		else
			$this->report = $report;
		$this->init();

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

    }

	function summary($data){
		return '';
	}
}

