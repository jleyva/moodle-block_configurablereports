<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('block_configurable_reports/dbhost', get_string('dbhost', 'block_configurable_reports'),
                    get_string('dbhostinfo', 'block_configurable_reports'), '', PARAM_URL, 30));
    $settings->add(new admin_setting_configtext('block_configurable_reports/dbname', get_string('dbname', 'block_configurable_reports'),
                    get_string('dbnameinfo', 'block_configurable_reports'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('block_configurable_reports/dbuser', get_string('dbuser', 'block_configurable_reports'),
                    get_string('dbuserinfo', 'block_configurable_reports'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('block_configurable_reports/dbpass', get_string('dbpass', 'block_configurable_reports'),
                    get_string('dbpassinfo', 'block_configurable_reports'), '', PARAM_RAW, 30));

    $settings->add(new admin_setting_configtime('block_configurable_reports/cron_hour', 'cron_minute',
        get_string('executeat', 'block_configurable_reports'), get_string('executeatinfo', 'block_configurable_reports'), array('h' => 0, 'm' => 0)));

    $settings->add(new admin_setting_configcheckbox('block_configurable_reports/sqlsecurity', get_string('sqlsecurity', 'block_configurable_reports'),
        get_string('sqlsecurityinfo', 'block_configurable_reports'), 1));

    $settings->add(new admin_setting_configtext('block_configurable_reports/crrepository', get_string('crrepository', 'block_configurable_reports'),
        get_string('crrepositoryinfo', 'block_configurable_reports'), 'jleyva/moodle-configurable_reports_repository', PARAM_URL, 40));

    $settings->add(new admin_setting_configtext('block_configurable_reports/sharedsqlrepository', get_string('sharedsqlrepository', 'block_configurable_reports'),
        get_string('sharedsqlrepositoryinfo', 'block_configurable_reports'), 'jleyva/moodle-custom_sql_report_queries', PARAM_URL, 40));

    $settings->add(new admin_setting_configcheckbox('block_configurable_reports/sqlsyntaxhighlight', get_string('sqlsyntaxhighlight', 'block_configurable_reports'),
        get_string('sqlsyntaxhighlightinfo', 'block_configurable_reports'), 0));

    $reporttableoptions = array('html'=>'Simple', 'jquery'=>'jQuery', 'datatables'=>'DataTables JS');
    $settings->add(new admin_setting_configselect('block_configurable_reports/reporttableui', get_string('reporttableui', 'block_configurable_reports'),
        get_string('reporttableuiinfo', 'block_configurable_reports'), 0, $reporttableoptions ));

//    $settings->add(new admin_setting_configcheckbox('block_configurable_reports/datatables', get_string('datatables', 'block_configurable_reports'),
//        get_string('datatablesinfo', 'block_configurable_reports'), 0));

}


