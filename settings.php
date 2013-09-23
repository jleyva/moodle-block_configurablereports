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
}


