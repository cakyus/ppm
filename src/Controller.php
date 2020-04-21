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

class Controller {

	public function __construct() {

		if (PHP_SAPI !== 'cli'){
			throw new \Exception("Invalid SAPI");
		}

		if ($_SERVER['argc'] == 1){
			$this->commandHelp();
			exit(0);
		}

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
	 * Create project
	 **/

	public function commandCreate($packageText, $targetDirectory) {

		// <vendorName>/<packageName>:<packageVersion>

		if (preg_match("/^([^\/]+)\/([^:]+):(.+)$/", $packageText, $match) == false) {
			throw new \Exception("Parse failed. Invalid package");
		}

		$packageName = $match[1].'/'.$match[2];
		$packageVersion = $match[3];

		$package = new \Pdr\Ppm\Package;
		$package->create($packageName, $packageVersion, $targetDirectory);
	}

	/**
	 * Install package
	 **/

	public function commandInstall($packageText=null){

		if (is_null($packageText)){

			$project = new \Pdr\Ppm\Project;
			$lockConfig = $project->getLockConfig();

			foreach ($project->getPackages() as $package){
				if ($lockConfig && $packageData = $lockConfig->getPackage($package->name)) {
					$package->install( $packageData->source->reference );
				} else {
					$package->install();
				}
			}

			// generate autoload
			$this->commandAutoload();

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

			if ($package->name == 'php') {
				continue;
			}

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

			if ($package->name == 'php') {
				continue;
			}

			if ( ( $repository = $package->getRepository() ) == false ){
				throw new \Exception("package {$package->name} not installed");
			}

			if ($repository->hasChanges()){
				throw new \Exception("package {$package->name} has changes");
			}

			$packageLockFound = false;
			foreach ($composerLock->data->packages as $packageLock){
				if ($packageLock->name == $package->name){
					$packageLockFound = true;
					$repositoryCurrentCommit = $repository->getCommitHash('HEAD');
					if ($packageLock->source->reference != $repositoryCurrentCommit){
						$packageLock->source->reference = $repositoryCurrentCommit;
					}
				}
			}

			if ($packageLockFound == false){
				$repositoryCurrentCommit = $repository->getCommitHash('HEAD');
				$composerLock->addPackage($package, $repositoryCurrentCommit);
			}
		}


		foreach ($composerLock->data->packages as $packageIndex => $packageLock){

			$packageFound = false;
			foreach ($project->getPackages() as $package){
				if ($packageLock->name == $package->name){
					$packageFound = true;
					break;
				}
			}

			if ($packageFound == false) {
				unset($composerLock->data->packages[$packageIndex]);
			}
		}

		$composerLock->data->packages = array_values( $composerLock->data->packages );

		$composerLock->save();
	}

	/**
	 * Display a list of packages
	 **/

	public function commandList(){

		$project = new \Pdr\Ppm\Project;
		$globalConfig = new \Pdr\Ppm\GlobalConfig;

		$config = $project->getConfig();

		if (empty($config->data->require)) {
			 return false;
		}

		foreach ($config->data->require as $packageName => $packageVersion) {
			 echo $packageName.' '.$packageVersion;
			 if ( ( $packageUrl = $globalConfig->getRepositoryUrl($packageName) ) !== false ) {
					echo ' '.$packageUrl;
			 }
			 echo "\n";
		}
	}

	/**
	 * Generate autoload file
	 **/

	public function commandAutoload(){

		$project = new \Pdr\Ppm\Project;

		$autoloadFile = $project->getVendorDir().'/autoload.php';
		$autoloadClassMapText = '';
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

				if ($autoloadMethod == 'classmap') {
					foreach ($autoload as $autoloadSourceFile){
						echo $autoloadSourceFile."\n";
						$autoloadText .= "\t\$classFile = \$vendorDir.'/".$package->name."/".$autoloadSourceFile."';\n";
						$autoloadText .= "\tif (is_file(\$classFile)){ require_once(\$classFile); }\n\n";
					}
					continue;
				}

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

	/**
	 * find className
	 **/

	public function commandFind($findClassName, $findMethodName=null) {

		// load autoload.php
		$workDir = getcwd();

		if (is_file('vendor/autoload.php') == false) {
			fwrite(STDERR, "File is not found: 'vendor/autoload.php'");
			return false;
		}

		include_once('vendor/autoload.php');

		// find source directories

		$sourceDir = array();

		if (is_file('composer.json') == false) {
			fwrite(STDERR, "File is not found: 'composer.json'");
			return false;
		}

		$composerFile = glob('vendor/*/*/composer.json');
		$composerFile[] = 'composer.json';

		foreach ($composerFile as $composerFileItem) {

			$composer = json_decode ( file_get_contents($composerFileItem) );
			$composerDirItem = dirname($composerFileItem);

			if (empty($composer)) {
				fwrite(STDERR, "Parse failed: 'composer.json'");
				return false;
			}

			if (empty($composer->autoload) == false) {
				foreach ($composer->autoload as $autoloadType => $autoload) {
					foreach ($autoload as $namespaceName => $sourceDir) {
						$sourceDirs[] = rtrim($composerDirItem.'/'.$sourceDir, '/');
					}
				}
			}
		}

		if (empty($sourceDirs)) {
			fwrite(STDERR, "Search directories are not found");
			return false;
		}

		// find source files

		$sourceFiles = array();

		while (true) {

			if (empty($sourceDirs)) {
				break;
			}

			$sourceDir = current($sourceDirs);

			if (is_dir($sourceDir) == false) {
				array_shift($sourceDirs);
				continue;
			}
			$dh = opendir($sourceDir);
			while ( ( $file = readdir($dh) ) !== false) {

				if ($file == '.' || $file == '..') {
					continue;
				}

				$filePath = $sourceDir.'/'.$file;

				if (is_dir($filePath)) {
					$sourceDirs[] = $filePath;
				} elseif (is_file($filePath) && substr($filePath, -4) == '.php'){
					$sourceFiles[] = $filePath;
				}
			}
			closedir($dh);

			array_shift($sourceDirs);
		}

		// find source class names

		$classNames = get_declared_classes();

		foreach ($sourceFiles as $filePath) {
			require_once($filePath);
		}

		$classNames = array_diff(get_declared_classes(), $classNames);

		// find class name

		$patternClass = str_replace('\\', '\\\\', $findClassName).'[;\s\(]';

		foreach ($classNames as $className) {

			$class = new \ReflectionClass($className);
			$classFile = $class->getFileName();
			if (empty($classFile)) {
				continue;
			}
			$classLine = file($classFile, FILE_IGNORE_NEW_LINES);
			$classFilePrint = substr($classFile, strlen($workDir) + 1);

			foreach ($class->getMethods() as $method) {

				$lineStart = $method->getStartLine();
				$lineStop = $method->getEndLine();

				$methodName = $method->getName();
				$methodLine = array_slice($classLine, $lineStart - 1, $lineStop - $lineStart + 1);

				// find method

				$patternClassVar = null;

				if (is_null($findMethodName) == false) {

					foreach ($methodLine as $methodLineIndex => $methodLineItem) {

						if (is_null($patternClassVar)) {
							if (preg_match("/\\\$([^\s\=]+)\s*=\s*new\s+\\\\$patternClass/", $methodLineItem, $match)) {
								$patternClassVar = "\\\$".$match[1];
							}
						}

						if (is_null($patternClassVar)) {
							continue;
						}

						if (preg_match("/$patternClassVar\->$findMethodName/", $methodLineItem, $match)) {
							echo $classFilePrint.' '.( $lineStart + $methodLineIndex ).' '.$className.'::'.$methodName."\n";
							echo '  '.trim($methodLineItem)."\n";
							break;
						}
					}
				} else {

				// find class

					foreach ($methodLine as $methodLineIndex => $methodLineItem) {
						if (preg_match("/(\\\$[^\s\=]+)\s*=\s*new\s+\\\\$patternClass/", $methodLineItem, $match)) {
							echo $classFilePrint.' '.( $lineStart + $methodLineIndex ).' '.$className.'::'.$methodName."\n";
							echo '  '.trim($methodLineItem)."\n";
							break;
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * List the same file from all branches
	 **/

	public function commandLs($fileName) {

		$project = new \Pdr\Ppm\Project;
		$repository = $project->getRepository();

		// local branches

		$data = array();

		foreach ($repository->getBranches() as $branch){

			$command = $repository->getGitCommand()
				.' ls-tree '.escapeshellarg($branch->name).' '.escapeshellarg($fileName)
				;
			if (\Pdr\Ppm\Console::line($command)){
				$data[$branch->name] = date('Y-m-d H:i:s', $branch->getFormat('%ct'));
			}
		}

		// remote branches

		foreach ($repository->getRemotes() as $remote){

			foreach ($remote->getBranches() as $branch){

				$command = $repository->getGitCommand()
					.' ls-tree '.escapeshellarg($remote->name).'/'.escapeshellarg($branch->name)
					.' '.escapeshellarg($fileName)
					;
				if (\Pdr\Ppm\Console::line($command)){
					$data[$remote->name.'/'.$branch->name] = date('Y-m-d H:i:s', $branch->getFormat('%ct'));
				}
			}
		}

		// print result
		asort($data);
		foreach ($data as $dataIndex => $item){
			echo $item.' '.$dataIndex."\n";
		}
	}

	/**
	 * Print this information and exit
	 **/

	public function commandHelp() {
		fwrite(STDERR, file_get_contents(FCPATH.'/docs/ppm.txt'));
	}
}
