<?php

defined('MOODLE_INTERNAL') || die;

require_once "$CFG->libdir/adminlib.php";

if ($ADMIN->fulltree) {
    $configs   = array();

    $configs[] = new admin_setting_configtext('dbhost', get_string('dbhost', 'block_configurable_reports'),
        get_string('dbhostinfo', 'block_configurable_reports'), 'localhost', PARAM_RAW, 30);
    $configs[] = new admin_setting_configtext('dbname', get_string('dbname', 'block_configurable_reports'),
        get_string('dbnameinfo', 'block_configurable_reports'), '', PARAM_RAW, 30);
    $configs[] = new admin_setting_configtext('dbuser', get_string('dbuser', 'block_configurable_reports'),
        get_string('dbuserinfo', 'block_configurable_reports'), '', PARAM_RAW, 30);
    $configs[] = new admin_setting_configtext('dbpass', get_string('dbpass', 'block_configurable_reports'),
        get_string('dbpassinfo', 'block_configurable_reports'), '', PARAM_RAW, 30);

    // Define the config plugin so it is saved to
    // the config_plugin table then add to the settings page
    foreach ($configs as $config) {
        $config->plugin = 'blocks/configurable_reports';
        $settings->add($config);
    }


}


