<?php

/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 **/

namespace Pdr\Ppm;

/**
 * PHP Package Manager
 **/

class Controller extends \Pdr\Ppm\Command {

	public function __construct() {
		parent::__construct();
		$this->version = '1.0';
	}

	/**
	 * Install package
	 **/

	public function commandInstall($packageText=null){

		if (is_null($packageText)){

			$project = new \Pdr\Ppm\Project;
			foreach ($project->getPackages() as $package){
				$package->install();
			}

			// generate autoload
			$this->commandSave();

			// execute post install command
			$this->commandExec('post-install-cmd');

		} else {

			$project = new \Pdr\Ppm\Project;
			$project->addPackage($packageText);

			$config = $project->getConfig();
			$config->save();

			// generate autoload
			$this->commandSave();

		}
	}

	/**
	 * Update remote
	 **/

	public function commandUpdate() {

		$project = new \Pdr\Ppm\Project;

		foreach ($project->getPackages() as $package){

			$packageVersion = $package->getVersion();

			echo "{$package->name} $packageVersion\n";

			if ( ( $repository = $package->getRepository() ) === false ){
				continue;
			}

			if ( ( $remote = $repository->getRemote('origin') ) === false ){
				continue;
			}

			$remote->fetch($packageVersion);
		}
	}

	/**
	 * Print statuses : local, local vs lock, and remote (cache) vs lock
	 **/

	public function commandStatus() {

		$project = new \Pdr\Ppm\Project;
		foreach ($project->getPackages() as $package) {

			// local status
			// check local repository status : initialized, has changes, or no change

			$localStatus = '?'; // Unknown

			if ( ( $repository = $package->getRepository() ) == false ){
				$localStatus = 'U'; // Un-initialize
			} else {

				$command  = $repository->getGitCommand();
				$command .= ' status --short';
				$text = \Pdr\Ppm\Console::text($command);

				if ($repository->hasChanges()){
					$localStatus = 'M'; // Has local changes
				} else {
					$localStatus = ' '; // No local changes
				}

			}

			// lock status
			// compare repository (HEAD) with composer lock file

			$lockStatus = '?'; // Unknown

			if (	( $localStatus == ' ' || $localStatus == 'M' )

				&&	( $lockConfig = $project->getLockConfig() ) != false
				&&	( $lockCommitHash = $lockConfig->getPackageCommitHash($package->name) ) != false

				&&	( $repositoryCommitHash = $repository->getCommitHash('HEAD') ) != false
				){

				if ($repositoryCommitHash == $lockCommitHash){
					$lockStatus = ' ';
				} else {
					$lockStatus = 'M';
				}
			}

			// remote status ( query from cache )
			// compare repository remote with composer lock file

			$remoteStatus = '?'; // Unknown

			if ( ( $lockStatus == ' ' || $lockStatus == 'M' )

				&& ( $remote = $repository->getRemote('origin') ) !== false
				&& ( $remoteCommitHash = $remote->getCommitHash($package->getVersion()) ) !== false
				){

				if ($remoteCommitHash == $lockCommitHash){
					$remoteStatus = ' ';
				} else {
					$remoteStatus = 'M';
				}
			}

			if (	$localStatus == ' '
				&&	$lockStatus == ' '
				&&	$remoteStatus == ' '
				){
				continue;
			}

			echo $localStatus.$lockStatus.$remoteStatus
				.' '.$project->getVendorDir().'/'.$package->name
				."\n";
		}
	}

	/**
	 * Update composer lock file
	 **/

	public function commandLock(){

		$project = new \Pdr\Ppm\Project;
		$composerLock = $project->getLockConfig();

		foreach ($project->getPackages() as $package){

			if ( ( $repository = $package->getRepository() ) == false ){
				throw new \Exception("Package not installed : ".$package->name);
			}

			if ($repository->hasChanges()){
				\Pdr\Ppm\Logger::error("Change exist on package {$package->name}");
			}

			$packageLockFound = false;
			foreach ($composerLock->data->packages as $packageLock){
				if ($packageLock->name == $package->name){
					$packageLockFound = true;
					$repositoryCurrentCommit = $repository->getCommitHash('HEAD');
					if ($packageLock->source->reference != $repositoryCurrentCommit){
						\Logger::debug("Update ".$package->name);
						$packageLock->source->reference = $repositoryCurrentCommit;
					}
				}
			}

			if ($packageLockFound == false){
				$repositoryCurrentCommit = $repository->getCommitHash('HEAD');
				$composerLock->addPackage($package, $repositoryCurrentCommit);
			}

		}

		$composerLock->save();
	}

	/**
	 * Display a list of packages
	 **/

	public function commandList(){

		$config = new \Pdr\Ppm\GlobalConfig;
		$config->open();

		$repositories = array();

		if (	isset($config->data->repositories)
			&&	is_object($config->data->repositories)
			){
			foreach ($config->data->repositories as $repositoryName => $repository){
				if ($repositoryName == 'packagist'){
					continue;
				}
				$repositories[$repositoryName] = '';
				if (isset($repository->url)){
					$repositories[$repositoryName] = $repository->url;
				}
			}
		}

		// print result

		foreach ($repositories as $repositoryName => $repositoryUrl){
			echo $repositoryName.' '.$repositoryUrl."\n";
		}
	}

	/**
	 * Save autoload
	 **/

	public function commandSave(){

		$project = new \Pdr\Ppm\Project;

		$autoloadFile = $project->getVendorDir().'/autoload.php';
		$autoloadText  = "<?php\n\n";
		$autoloadText .= "// DO NOT EDIT. THIS FILE IS AUTOGENERATED. ".date('Y-m-d H:i:s')."\n\n";
		$autoloadText .= "function ppmAutoload(\$className){\n\n";
		$autoloadText .= "\t\$vendorDir = dirname(__FILE__);\n";
		$autoloadText .= "\t\$projectDir = dirname(\$vendorDir);\n\n";

		$config = $project->getConfig();
		if (isset($config->data->autoload)){

			foreach ($config->data->autoload as $autoloadMethod => $autoload){

				// psr-4
				if ($autoloadMethod == 'psr-4'){
					foreach ($autoload as $classPrefix => $pathPrefix){
						$classPrefixLength = strlen($classPrefix);
						$autoloadText .= "\tif (substr(\$className,0,$classPrefixLength) == '".str_replace('\\', '\\\\', $classPrefix)."'){\n";
						$autoloadText .= "\t\t\$classFile = \$projectDir.'/$pathPrefix'.str_replace('\\\\','/',substr(\$className, $classPrefixLength)).'.php';\n";
						$autoloadText .= "\t\tif (is_file(\$classFile)){ require_once(\$classFile); }\n";
						$autoloadText .= "\t}\n\n";
					}
				} elseif ($autoloadMethod == 'psr-0'){
					foreach ($autoload as $classPrefix => $pathPrefix){
						$classPrefixLength = strlen($classPrefix);
						$autoloadText .= "\tif (substr(\$className,0,$classPrefixLength) == '".str_replace('\\', '\\\\', $classPrefix)."'){\n";
						//~ $autoloadText .= "echo '$classPrefix '.substr(\$className,0,$classPrefixLength).\"\\n\";\n";
						$autoloadText .= "\t\t\$classFile = \$projectDir.'/$pathPrefix.str_replace('\\\\','/',\$className).'.php';\n";
						//~ $autoloadText .= "echo \"\$classFile\\n\";\n";
						$autoloadText .= "\t\tif (is_file(\$classFile)){ require_once(\$classFile); }\n";
						$autoloadText .= "\t}\n\n";
					}
				} else {
					throw new \Exception("Unsupported autoloadMethod: {$autoloadMethod}");
				}
			}
		}

		foreach ($project->getPackages() as $package){

			$config = $package->getConfig();
			if (isset($config->data->autoload) == false){
				continue;
			}

			foreach ($config->data->autoload as $autoloadMethod => $autoload){

				// psr-4
				if ($autoloadMethod == 'psr-4'){
					foreach ($autoload as $classPrefix => $pathPrefix){
						$classPrefixLength = strlen($classPrefix);
						$pathPrefix = '$vendorDir.\'/'.$package->name.'/'.$pathPrefix.'\'';
						$autoloadText .= "\tif (substr(\$className,0,$classPrefixLength) == '".str_replace('\\', '\\\\', $classPrefix)."'){\n";
						//~ $autoloadText .= "echo '$classPrefix '.substr(\$className,0,$classPrefixLength).\"\\n\";\n";
						$autoloadText .= "\t\t\$classFile = $pathPrefix.str_replace('\\\\','/',substr(\$className, $classPrefixLength)).'.php';\n";
						//~ $autoloadText .= "echo \"\$classFile\\n\";\n";
						$autoloadText .= "\t\tif (is_file(\$classFile)){ require_once(\$classFile); }\n";
						$autoloadText .= "\t}\n\n";
					}
				} elseif ($autoloadMethod == 'psr-0'){
					foreach ($autoload as $classPrefix => $pathPrefix){
						$classPrefixLength = strlen($classPrefix);
						$pathPrefix = '$vendorDir.\'/'.$package->name.'/'.$pathPrefix.'\'';
						$autoloadText .= "\tif (substr(\$className,0,$classPrefixLength) == '".str_replace('\\', '\\\\', $classPrefix)."'){\n";
						//~ $autoloadText .= "echo '$classPrefix '.substr(\$className,0,$classPrefixLength).\"\\n\";\n";
						$autoloadText .= "\t\t\$classFile = $pathPrefix.str_replace('\\\\','/',\$className).'.php';\n";
						//~ $autoloadText .= "echo \"\$classFile\\n\";\n";
						$autoloadText .= "\t\tif (is_file(\$classFile)){ require_once(\$classFile); }\n";
						$autoloadText .= "\t}\n\n";
					}
				} else {
					throw new \Exception("Unsupported autoloadMethod: {$autoloadMethod}");
				}
			}

		}

		if (is_dir(dirname($autoloadFile)) == false) {
			mkdir(dirname($autoloadFile));
		}

		$autoloadText .= "}\n\nspl_autoload_register('ppmAutoload');\n";
		file_put_contents($autoloadFile, $autoloadText);
	}

	/**
	 * Perform unit test
	 **/

	public function commandTest($option = null) {

		// check PHP syntax

		if (is_null($option)){
			$option = new \stdClass;
			$testFile = sys_get_temp_dir().'/ppm.test.'.md5(getcwd());
			$option->testDir = getcwd();
			$option->testDate = 0;
			if (is_file($testFile)){
				$option->testDate = filemtime($testFile);
			}
			touch($testFile);
		}

		if ($dh = opendir($option->testDir)){
			while (($file = readdir($dh)) !== false){
				if ($file == '.' || $file == '..'){
					continue;
				}
				$path = $option->testDir.'/'.$file;
				if (is_dir($path)){
					$iOption = new \stdClass;
					$iOption->testDir = $path;
					$iOption->testDate = $option->testDate;
					$this->commandTest($iOption);
				} elseif (substr($path, -4) == '.php') {
					if (filemtime($path) > $option->testDate){
						passthru('php -l '.escapeshellarg($path).' >/dev/null 2>&1', $exit);
						if ($exit){
							echo "Syntax-Error: $path\n"; exit(1);
						}
					}
				}
			}
			closedir($dh);
		}
	}

	/**
	 * Execute composer scripts
	 **/

	public function commandExec($scriptName, $workingDirectory=null) {

		$project = new \Pdr\Ppm\Project;
		$config = $project->getConfig();
		$currentDirectory = getcwd();

		if (empty($config->data->scripts)){
			return false;
		}

		foreach ($config->data->scripts as $eventName => $scripts){
			if ($eventName != $scriptName){
				continue;
			}
			foreach ($scripts as $command){
				\Pdr\Ppm\Logger::debug("Executing [$scriptName] > $command ..");
				if (is_null($workingDirectory) == false){
					chdir($workingDirectory);
				}
				passthru($command, $exitCode);
				if (is_null($workingDirectory) == false){
					chdir($currentDirectory);
				}
				if ($exitCode !== 0){
					return false;
				}
			}
		}

		\Pdr\Ppm\Logger::debug("Execute scripts done");
		return true;
	}

	/**
	 * Execute command on each package
	 **/

	public function commandEach($command) {

		$project = new \Pdr\Ppm\Project;
		$projectRealPath = $project->getRealPath();

		foreach ($project->getPackages() as $package){

			chdir($projectRealPath);
			if ( ( $repository = $package->getRepository() ) === false ){
				\Pdr\Ppm\Logger::warn("[".$package->name."] repository not found.");
				continue;
			}

			$packagePath = $package->getPath();
			chdir($packagePath);

			\Pdr\Ppm\Logger::debug("[{$package->name}] > $command");
			passthru($command);
		}
	}
}
