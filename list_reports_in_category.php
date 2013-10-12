<?php

require(dirname(dirname(dirname(__FILE__))).'/config.php');

$userandrepo = get_config('block_configurable_reports','sharedsqlrepository');
if (empty($userandrepo)) {
    $userandrepo = 'nadavkav/moodle-custom_sql_report_queries';
}

$res = file_get_contents("https://api.github.com/repos/$userandrepo/contents/".$_GET['category']);
$res = json_decode($res);
//print_r($res);
$reportlist = array();
foreach ($res as $item) {
    //echo "[ $item->type , $item->path ]";
    $report = new stdClass();
    $report->name = str_replace($_GET['category'].'/','',$item->path);
    $report->fullname = $item->path;
    if ($item->type == 'file') $reportlist[] = $report;
}

echo json_encode($reportlist);
