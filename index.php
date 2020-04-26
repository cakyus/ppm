<?php

define('FCPATH', dirname(__FILE__));

date_default_timezone_set('Asia/Jakarta');

include_once(__DIR__.'/lib/main.php');
include_once(__DIR__.'/lib/ppm.php');
include_once(__DIR__.'/vendor/autoload.php');

$error = new \Pdr\Ppm\Cli\ErrorHandler;
$error->execute();

$controller = new \Pdr\Ppm\Controller;
$controller->execute();
