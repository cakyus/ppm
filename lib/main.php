<?php

/**
 * Command Line Helper
 **/


class Controller {

	public function __construct(){

		if (PHP_SAPI !== 'cli'){
			throw new \Exception("Invalid SAPI");
		}

		if ($_SERVER['argc'] == 1){
			$this->commandHelp();
			exit(1);
		}

		// set error level
		if (empty($_SERVER['PHP_TRACE']) == false){

			if (	$_SERVER['PHP_TRACE'] == 'DEBUG'
				||	$_SERVER['PHP_TRACE'] == '3'
				){
				// E_USER_NOTICE
				error_reporting(1024);
			} elseif (
						$_SERVER['PHP_TRACE'] == 'WARNING'
				||	$_SERVER['PHP_TRACE'] == '2'
				){
				// E_USER_WARNING
				error_reporting(512);
			} elseif (
						$_SERVER['PHP_TRACE'] == 'ERROR'
				||	$_SERVER['PHP_TRACE'] == '1'
				){
				// E_USER_ERROR
				error_reporting(256);
			} else {
				error_reporting(0);
			}

		} else {
			error_reporting(0);
		}

		set_exception_handler(array($this, 'exceptionHandler'));

		$arguments = $_SERVER['argv'];
		array_shift($arguments);
		$commandName = array_shift($arguments);
		$commandName = 'command'.ucfirst($commandName);

		if (method_exists($this, $commandName) == false){
			fwrite(STDERR, "ERROR: Command not exists\n");
			$this->commandHelp();
			exit(1);
		}

		call_user_func_array(array($this, $commandName), $arguments);
	}

	/**
	 * Print this information and exit
	 **/

	public function commandHelp(){

		$class = new \ReflectionClass($this);

		echo "Commands:\n";

		foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method){

			if (substr($method->getName(),0,2) == '__'){
				continue;
			}

			if (preg_match("/^command([A-Za-z]+)/",$method->getName(),$match) == false){
				continue;
			}

			$commandName = lcfirst($match[1]);
			$comment = $method->getDocComment();

			$comment = preg_replace("/^\/\*+/", "", $comment);
			$comment = preg_replace("/\*+\/$/", "", $comment);

			$commentLines = array();
			foreach (explode("\n", $comment) as $commentLine){
				$commentLine = preg_replace("/^\s+\*+/", "", $commentLine);
				$commentLines[] = $commentLine;
			}
			$comment = implode("\n", $commentLines);
			$comment = trim($comment);

			echo "  $commandName - $comment\n";
		}
	}

	public function exceptionHandler($exception){
		$message = $exception->getMessage();
		$location = $exception->getFile().':'.$exception->getLine();
		fwrite(STDERR, date('Y-m-d+H:i:s')." EXCEPTION $message $location\n");
		exit(1);
	}
}


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

date_default_timezone_set('Asia/Jakarta');
