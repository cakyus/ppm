<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('FCPATH', dirname(__FILE__));
define('WORKDIR', getcwd());

date_default_timezone_set('Asia/Jakarta');

include_once(__DIR__.'/lib/main.php');
include_once(__DIR__.'/lib/ppm.php');
include_once(__DIR__.'/vendor/autoload.php');

$controller = new \Pdr\Ppm\Controller;
$controller->execute();
