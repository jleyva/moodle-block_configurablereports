<?php

require(dirname(dirname(dirname(__FILE__))).'/config.php');

$userandrepo = get_config('block_configurable_reports','sharedsqlrepository');
if (empty($userandrepo)) {
    $userandrepo = 'nadavkav/moodle-custom_sql_report_queries';
}

$content = file_get_contents("https://raw.github.com/$userandrepo/master/".$_GET['reportname']);
list($subject,$description,$sql) = explode('###',$content);

echo json_encode($sql);
