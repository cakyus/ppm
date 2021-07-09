<?php

function ppm_autoload_03be68e1a6585f78286264ff4d806356($className){

	$vendorDir = dirname(__FILE__);
	$projectDir = dirname($vendorDir);

	if (substr($className,0,8) == 'Pdr\\Ppm\\'){
		$classFile = $projectDir.'/src/'.str_replace('\\','/',substr($className, 8)).'.php';
		if (is_file($classFile)){ require_once($classFile); }
	}
}

spl_autoload_register('ppm_autoload_03be68e1a6585f78286264ff4d806356');
