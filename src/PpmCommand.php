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

class PpmCommand extends \Pdr\Ppm\Command {

	public function __construct() {
		parent::__construct();
		$this->version = '1.0';
	}

	public function commandInstall($packageText){

		if (is_null($packageText)){

			$composerLock = new \ComposerLock;
			$composerLock->open();
			foreach ($composerLock->data->packages as $package){
				$this->installPackage($package->name.':'.$package->source->reference);
			}

		} else {

			if ($package = $this->installPackage($packageText)) {

				$configLocal = new \ComposerConfigLocal;
				$configLocal->open();
				$configLocal->addPackage($package);
				$configLocal->save();

				$composerLock = new \ComposerLock;
				$composerLock->open();
				$composerLock->addPackage($package);
				$composerLock->save();

			} else {
				throw new \Exception("Install package fail");
			}

		}

		$composerCommand = new \ComposerCommand;
		$composerCommand->createAutoload();
	}

	/**
	 * Install package
	 *
	 * @param $package VENDOR_NAME/PROJECT_NAME[:VERSION]
	 **/

	protected function installPackage($package){

		// pdr/docker-files:dev-core
		if (preg_match("/^([^:]+):(.+)$/",$package,$match) == false){
			throw new \Exception("Parse package fail");
		}

		$packageName = $match[1];
		$packageVersion = $match[2];

		if (preg_match("/^dev\-(.+)$/",$packageVersion,$match)){
			$packageVersion = $match[1];
			$packageVersionType = 'branch';
			$packageBranchName = $match[1];
		} elseif (preg_match("/^([0-9a-f]{40,40})$/",$packageVersion,$match)){
			$packageVersion = $match[1];
			$packageVersionType = 'commit';
		} else {
			throw new \Exception("Parse package fail. '$packageVersion'");
		}

		// get packageBranchName for packageVersionType commit

		if ($packageVersionType == 'commit'){

			$config = new \ComposerConfigLocal;
			if ($config->open() == false){
				return false;
			}

			$repositoryFound = false;
			foreach ($config->data->require as $repositoryName => $repositoryVersion){
				if ($repositoryName != $packageName){
					continue;
				}
				$repositoryFound = true;
				if (preg_match("/^dev\-(.+)$/",$repositoryVersion,$match)){
					$packageBranchName = $match[1];
				} else {
					throw new \Exception("Parse repositoryVersion fail. '$repositoryVersion'");
				}

				break;
			}
			if (empty($repositoryFound)){
				throw new \Exception("Repository is not found");
			}
		}

		$config = new \ComposerConfigGlobal;
		if ($config->open() == false){
			return false;
		}

		$repositoryFound = false;
		foreach ($config->data->repositories as $repositoryName => $repository){
			if ($repositoryName != $packageName){
				continue;
			}
			$repositoryFound = true;
			break;
		}

		if (empty($repositoryFound)){
			throw new \Exception("Repository is not found");
		}

		// get packageVersion for packageVersionType commit ( packageVersion is not branchName )

		$line = \Console::text("git ls-remote {$repository->url} refs/heads/$packageBranchName");
		if (empty($line)){
			\Logger::error("Remote url does not have branch $packageBranchName");
		}

		if (preg_match("/^([0-9a-f]{40,40})\s+/",$line,$match) == false){
			throw new \Exception("Parse error");
		}

		$remoteCommit = $match[1];

		$packageVersion= substr($line, 0, 40);

		$project = new \Project;
		$packageDir = $project->getVendorDir().'/'.$packageName;

		$package = new \Package;
		$package->name = $packageName;
		$package->branchName = $packageBranchName;
		$package->version = $packageVersion;
		$package->versionType = $packageVersionType;
		$package->remoteUrl = $repository->url;

		if ($package->exist() == true){
			$package->update();
			return $package;
		}

		$package->create();

		return $package;

		\Logger::debug("Done");
	}

	public function commandStatus() {

		$project = new \Project;
		foreach ($project->getPackages() as $package) {
			$package->printStatus();
		}
	}

	/**
	 * Update composer lock file
	 **/

	public function commandLock(){

		$project = new \Project;

		foreach ($project->getPackages() as $package){

			$packageStatus = $package->getStatus();
			if (empty($packageStatus) == false){
				\Logger::error("Change exist on package {$package->name}");
			}

			$composerLock = new \ComposerLock;
			$composerLock->open();
			$packageLockFound = false;

			foreach ($composerLock->data->packages as $packageLock){
				if ($packageLock->name == $package->name){
					$packageLockFound = true;
					\Logger::debug("Check packageLock {$packageLock->name}");
					\Logger::debug($packageLock->source->reference.' => '.$package->getCurrentCommit());
					if ($packageLock->source->reference != $package->getCurrentCommit()){
						\Logger::debug("Update {$package->name}");
						$packageLock->source->reference = $package->getCurrentCommit();
					}
				}
			}

			if (empty($packageLockFound)){
				$composerLock->addPackage($package);
			}

			$composerLock->save();
		}
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
	 * Save autoload
	 **/

	public function commandSave(){

		$project = new \Pdr\Ppm\Project;

		$autoloadFile = $project->getVendorDir().'/autoload.php';
		$autoloadText  = "<?php\n\nfunction ppmAutoload(\$className){\n\n";
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

		$autoloadText .= "}\n\nspl_autoload_register('ppmAutoload');\n";

		file_put_contents($autoloadFile, $autoloadText);
	}
}
