<?php

class Console {

	public static function exec($command){

		if (error_reporting() & E_USER_NOTICE){
			fwrite(STDERR, "> $command\n");
		}

		passthru($command, $exitCode);
		if ($exitCode != 0){
			throw new \Exception("Command return non zero exit code");
		}
	}

	public static function text($command){

		if (error_reporting() & E_USER_NOTICE){
			fwrite(STDERR, "> $command\n");
		}

		exec($command, $outputLines, $exitCode);
		if ($exitCode != 0){
			throw new \Exception("Command return non zero exit code");
		}
		return implode("\n", $outputLines);
	}

	public static function line($command){

		if (error_reporting() & E_USER_NOTICE){
			fwrite(STDERR, "> $command\n");
		}

		exec($command, $outputLines, $exitCode);
		if ($exitCode != 0){
			throw new \Exception("Command return non zero exit code");
		}
		return $outputLines;
	}
}

class Logger {

	public static function debug($message){
		if (error_reporting() & E_USER_NOTICE){
			fwrite(STDERR, date('Y-m-d+H:i:s')." DEBUG $message\n");
		}
	}

	public static function warn($message){
		if (error_reporting() & E_USER_WARNING){
			fwrite(STDERR, date('Y-m-d+H:i:s')." WARN  $message\n");
		}
	}

	public static function error($message){
		fwrite(STDERR, date('Y-m-d+H:i:s')." ERROR $message\n");
		exit(1);
	}
}

class Config {

	private static $instance;
	public $data;

	public function __construct(){
		$this->data = array();
	}

	public static function getInstance(){
		if (is_null(self::$instance)){
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}

	public function get($name){
		$config = \Config::getInstance();
		if (array_key_exists($name, $config->data)){
			return $config->data[$name];
		}
		return null;
	}

	public function set($name, $value){
		$config = \Config::getInstance();
		$config->data[$name] = $value;
	}

	public function getNames(){
		$config = \Config::getInstance();
		return array_keys($config->data);
	}
}

class FileSystem {

	/**
	 * Remove directory recursively
	 **/

	public function removeDirectory($dir){

		if (is_dir($dir) == false){
			return true;
		}

		if ($dh = opendir($dir)){
			while (($file = readdir($dh)) !== false){
				if ($file == '.' || $file == '..'){
					continue;
				}
				$path = $dir.'/'.$file;
				if (is_dir($path)){
					$this->removeDirectory($path);
				} else {
					unlink($path);
				}
			}
			closedir($dir);
		}

	}
}

