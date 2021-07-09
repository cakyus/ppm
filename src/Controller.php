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

class Controller extends \Pdr\Ppm\Cli\Controller {

	public function __construct() {
		parent::__construct();
	}

	public function execute() {

		if ($_SERVER['argc'] == 1){
			$this->commandHelp();
			exit(0);
		}

		$arguments = $_SERVER['argv'];
		array_shift($arguments);
		$commandText = array_shift($arguments);
		$commandName = 'command'.ucfirst($commandText);

		if (method_exists($this, $commandName) == false){
			return $this->commandExec($commandText);
		}

		call_user_func_array(array($this, $commandName), $arguments);
	}

	public function commandInit() {

		$this->initAutoload();

		$project = new \Pdr\Ppm\Project;
		$configGlobal = $project->getConfigGlobal();
		$configGlobal->save();
	}

	/**
	 * Generate autoload file
	 **/

	protected function initAutoload(){

		$project = new \Pdr\Ppm\Project;

		$autoloadFunctionName = 'ppm_autoload';

		$autoloadFile = $project->getVendorDir().'/autoload.php';
		$autoloadClassMapText = '';
		$autoloadText  = "<?php\n\n";
		$autoloadText .= "// DO NOT EDIT. THIS FILE IS AUTOGENERATED.\n\n";
		$autoloadText .= "function $autoloadFunctionName(\$className){\n\n";
		$autoloadText .= "\t\$vendorDir = dirname(__FILE__);\n";
		$autoloadText .= "\t\$projectDir = dirname(\$vendorDir);\n\n";

		$composerFile = $project->getPath().'/composer.json';
		if (is_file($composerFile) == FALSE){
			$composerFile = $project->getPath().'/ppm.json';
			trigger_error("$composerFile is deprecated", E_USER_DEPRECATED);
		}

		$packageDir = '$projectDir';
		$autoloadText .= $this->initAutoloadFile($packageDir, $composerFile);

		if (is_dir(dirname($autoloadFile)) == false) {
			mkdir(dirname($autoloadFile));
		}

		foreach ($project->getPackages() as $package) {
			$packageName = $package->name;
			fwrite(STDERR, "packageName $packageName\n");
			if ($packageName == 'php'){
				continue;
			}
			$composerFile = $project->getVendorDir().'/'.$packageName.'/composer.json';
			if (is_file($composerFile) == FALSE){
				$composerFile = $project->getVendorDir().'/'.$packageName.'/ppm.json';
				trigger_error("$composerFile is deprecated", E_USER_DEPRECATED);
			}
			$packageDir = '$vendorDir.\'/'.$packageName.'\'';
			$autoloadText .= $this->initAutoloadFile($packageDir, $composerFile);
		}

		foreach ($project->developmentPackages as $package) {
			$packageName = $package->name;
			fwrite(STDERR, "packageName $packageName\n");
			if ($packageName == 'php'){
				continue;
			}
			$composerFile = $project->getVendorDir().'/'.$packageName.'/composer.json';
			if (is_file($composerFile) == FALSE){
				$composerFile = $project->getVendorDir().'/'.$packageName.'/ppm.json';
				trigger_error("$composerFile is deprecated", E_USER_DEPRECATED);
			}
			$packageDir = '$vendorDir.\'/'.$packageName.'\'';
			$autoloadText .= $this->initAutoloadFile($packageDir, $composerFile);
		}

		$autoloadText .= "}\n\nspl_autoload_register('$autoloadFunctionName');\n";

		file_put_contents($autoloadFile, $autoloadText);
	}

	/**
	 * @return string autoloadText
	 **/

	protected function initAutoloadFile($packageDir, $composerFile) {

		$autoloadText = "\t".'// package '
			.str_replace(array('$vendorDir.\'/',"'"), '', $packageDir)
			."\n\n";

		if (is_file($composerFile) == FALSE) {
			return $autoloadText;
		}

		$text =  file_get_contents($composerFile);
		$data =  json_decode($text, TRUE);
		if (json_last_error() != 0){
			throw new \Exception("JSON Parse Error. '$composerFile'");
		}

		if (isset($data['autoload']) == FALSE) {
			return $autoloadText;
		}

		foreach ($data['autoload'] as $method => $autoload){

			// classmap

			if ($method == 'classmap') {
				$autoloadText .= "\t// > autoload classmap\n";
				foreach ($autoload as $autoloadSourceFile){
					$autoloadText .= "\t\$classFile = ".$packageDir.".'/".$autoloadSourceFile."';\n";
					$autoloadText .= "\tif (is_file(\$classFile)){ require_once(\$classFile); }\n\n";
				}
				continue;
			}

			// psr-4

			if ($method == 'psr-4'){
				$autoloadText .= "\t// > autoload psr-4\n\n";
				foreach ($autoload as $classPrefix => $pathPrefix){
					$classPrefixLength = strlen($classPrefix);
					$pathPrefix = $packageDir.'.\'/'.$pathPrefix.'\'';
					$autoloadText .= "\tif (substr(\$className,0,$classPrefixLength) == '".str_replace('\\', '\\\\', $classPrefix)."'){\n";
					$autoloadText .= "\t\t\$classFile = $pathPrefix.str_replace('\\\\','/',substr(\$className, $classPrefixLength)).'.php';\n";
					$autoloadText .= "\t\tif (is_file(\$classFile)){ require_once(\$classFile); }\n";
					$autoloadText .= "\t}\n\n";
				}
				continue;
			}

			// psr-0

			if ($method == 'psr-0'){
				$autoloadText .= "\t// > autoload psr-0\n\n";
				foreach ($autoload as $classPrefix => $pathPrefix){
					$classPrefixLength = strlen($classPrefix);
					$pathPrefix = $packageDir.'.\'/'.$pathPrefix.'\'';
					$autoloadText .= "\tif (substr(\$className,0,$classPrefixLength) == '".str_replace('\\', '\\\\', $classPrefix)."'){\n";
					$autoloadText .= "\t\t\$classFile = $pathPrefix.str_replace(array('\\\\', '_'),'/',\$className).'.php';\n";
					$autoloadText .= "\t\tif (is_file(\$classFile)){ require_once(\$classFile); }\n";
					$autoloadText .= "\t}\n\n";
				}
				continue;
			}

			// files

			if ($method == 'files'){
				$autoloadText .= "\t// > autoload files\n\n";
				foreach ($autoload as $filePath){
					$autoloadText .= "\t\$classFile = $packageDir.'/$filePath';\n";
					$autoloadText .= "\tif (is_file(\$classFile)){ require_once(\$classFile); }\n";
					$autoloadText .= "\n\n";
				}
				continue;
			}

			fwrite(STDERR, "WARNING Unsupported autoload method '$method'\n");
		}

		return $autoloadText;
	}

	public function initConfig(){

		$project = new \Pdr\Ppm\Project2;
		$console = new \Pdr\Ppm\Console;
		$config = new \Pdr\Ppm\Git\Config;

		$vendorDir = $project->getVendorDir();
		$config->openLocal();

		foreach ($project->getPackages() as $package){

			if (is_dir($vendorDir.'/'.$package->name) == FALSE){
				continue;
			}

			$gitCommand = 'git'
				.' --git-dir '.escapeshellarg($vendorDir.'/'.$package->name.'/.git')
				.' --work-tree '.escapeshellarg($vendorDir.'/'.$package->name)
				;

			// check commit
			$commandText = $gitCommand
				.' for-each-ref --format "%(objectname)" refs/heads/'.$package->revision
				;

			$packageCommit = $console->text($commandText);

			if (strlen($packageCommit) == 0){
				fwrite(STDERR, 'WARNING'
					.' '.$package->name
					.' '.$package->revision
					.' has no commit'
					."\n");
				continue;
			}

			$config->set('ppm.packages.'.$package->name.'.commit', $packageCommit);
		}
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
	 * @usage install
	 * @usage install <packageName>:<packageReference>
	 * @usage install <packageName>:<packageReference> <packageRepositoryUrl>
	 **/

	public function commandInstall(){

		$project = new \Pdr\Ppm\Project;
		$option = new \Pdr\Ppm\Cli\Option;

		$vendorDir = $project->getVendorDir();
		if (is_dir($vendorDir) == FALSE) {
			mkdir($vendorDir);
		}

		if ($option->getCommandCount() == 0){

			$lockConfig = $project->getConfigLock();

			foreach ($project->getPackages() as $package){
				if ($lockConfig && $packageData = $lockConfig->getPackage($package->name)) {
					$package->install( $packageData->source->reference );
				} else {
					$package->install();
				}
			}

			// generate autoload
			$this->initAutoload();

			// execute post install command
			$this->commandExec('post-install-cmd');

		} elseif ($option->getCommandCount() == 1) {

			$optionDevelopmentPackage = $option->getOption('dev');
			$packageNameReference = $option->getCommand(0);
			if (preg_match("/^([^:]+):(.+)$/", $packageNameReference, $match) == FALSE){
				throw new \Exception("Invalid packageNameReference '$packageNameReference'");
			}

			$packageName = $match[1];
			$packageReference = $match[2];
			$packageRepositoryUrl = NULL;

			if ($optionDevelopmentPackage == FALSE){
				$project->createPackage($packageName, $packageReference, $packageRepositoryUrl);
			} else {
				$project->createDelopmentPackage($packageName, $packageReference, $packageRepositoryUrl);
			}

			$project->configLock->save();
			$project->configPackage->save();
			$this->initAutoload();

		} elseif ($option->getCommandCount() == 2) {

			$optionDevelopmentPackage = $option->getOption('dev');
			$packageNameRevision = $option->getCommand(0);
			$packageRepositoryUrl = $option->getCommand(1);

			if (preg_match("/^([^:]+):(.+)$/", $packageNameRevision, $match) == FALSE){
				throw new \Exception("Invalid packageNameRevision '$packageNameRevision'");
			}

			$packageName = $match[1];
			$packageRevision = $match[2];

			if ($optionDevelopmentPackage == FALSE){
				$project->createPackage($packageName, $packageRevision, $packageRepositoryUrl);
			} else {
				$project->createDelopmentPackage($packageName, $packageRevision, $packageRepositoryUrl);
			}
		}
	}

	/**
	 * Update packages
	 **/

	public function commandUpgrade(){

		$project = new \Pdr\Ppm\Project;

		foreach ($project->getPackages() as $package){
			if ($package->name == 'php'){
				continue;
			}
			$package->update();
		}

		foreach ($project->getDevelopmentPackages() as $package){
			if ($package->name == 'php'){
				continue;
			}
			$package->update();
		}

		$project->configLock->save();
		$project->configPackage->save();
		$this->initAutoload();
	}

	/**
	 * Update repositories database
	 **/

	public function commandUpdate() {

	}

	/**
	 * Print status of local, local vs remove, and local vs lock
	 **/

	public function commandStatus() {

		$project = new \Pdr\Ppm\Project;
		$console = new \Pdr\Ppm\Console;

		$packages = array();

		foreach ($project->getPackages() as $package){
			$packages[] = $package;
		}

		foreach ($project->getDevelopmentPackages() as $package){
			$packages[] = $package;
		}

		foreach ($packages as $package) {

			// TODO check php-extension status

			if ($package->name == 'php') {
				continue;
			}

			// set default statuses

			$localStatus  = '?'; // Unknown
			$lockStatus   = '?'; // Unknown
			$remoteStatus = '?'; // Unknown

			$packageDir = $project->getVendorDir().'/'.$package->name;

			if (is_dir($packageDir) == TRUE && is_dir($packageDir.'/.git') == TRUE){

				$gitCommand = 'git'
					.' --git-dir '.escapeshellarg($packageDir.'/.git')
					.' --work-tree '.escapeshellarg($packageDir)
					;

				// local status

				$commandText = $gitCommand.' status --short';
				$commandLine = $console->line($commandText);
				if (\count($commandLine) == 0){
					$localStatus = ' ';
				} else {
					$localStatus = 'M';
				}

				$commandText = $gitCommand.' log -n 1 --format=%H';
				$localCommitHash = $console->text($commandText);

				// remote status : local vs remote

				if (is_file($packageDir.'/.git/refs/remotes/origin/master') == TRUE){
					$remoteCommitHash = file_get_contents($packageDir.'/.git/refs/remotes/origin/master');
					$remoteCommitHash = trim($remoteCommitHash);
					if ($localCommitHash == $remoteCommitHash){
						$remoteStatus = ' ';
					} else {
						$remoteStatus = 'M';
					}
				}

				// lock status : local vs lock

				foreach ($project->configLock->packages as $lockPackage){
					if ($package->name == $lockPackage->name){
						if ($localCommitHash == $lockPackage->source->reference){
							$lockStatus = ' ';
						} else {
							$lockStatus = 'M';
						}
					}
				}
			}

			if ($localStatus == ' ' && $remoteStatus == ' ' && $lockStatus == ' '){
				continue;
			}

			fwrite(STDOUT, $localStatus.$remoteStatus.$lockStatus.' '.$package->name."\n");
		}
	}

	/**
	 * Update composer lock file
	 **/

	public function commandLock(){
		$project = new \Pdr\Ppm\Project;
		$configLock = $project->getConfigLock();
		return $configLock->save();
	}

	/**
	 * Display list of installed packages
	 **/

	public function commandList(){

		$option = new \Pdr\Ppm\Cli\Option;

		$optionListGlobal = FALSE;
		$optionListLocal = TRUE;

		if (	$option->getOption('g') != NULL
			||	$option->getOption('global') != NULL
			) {
			$optionListGlobal = TRUE;
			$optionListLocal = FALSE;
		}

		if ($optionListGlobal == TRUE) {

			$project = new \Pdr\Ppm\Project;
			$packages = array();

			foreach ($project->getConfigPackage()->packages as $package) {
				foreach ($package->repositories as $repository) {
					if (isset($packages[$package->name]) == FALSE){
						$packages[$package->name] = array();
					}
					$packages[$package->name][] = $repository;
				}
			}

			ksort($packages);

			foreach ($packages as $packageName => $repositories){
				foreach ($repositories as $repository){
					fwrite(STDOUT, "{$packageName} {$repository->url}\n");
				}
			}
		}

		if ($optionListLocal == TRUE) {

			$project = new \Pdr\Ppm\Project;

			$packages = array();

			foreach ($project->getPackages() as $package){
				$packages[] = $package;
			}

			foreach ($project->getDevelopmentPackages() as $package){
				$packages[] = $package;
			}

			foreach ($packages as $package) {

				if ($package->name == 'php') {
					continue;
				}

				echo $package->name
					.' '.$package->version
					.' '.$package->repositoryUrl
					."\n";
			}
		}
	}

	/**
	 * Execute composer scripts
	 **/

	public function commandExec($commandText) {

		$project = new \Pdr\Ppm\Project;

		if (empty($project->config->scripts) == TRUE){
			fwrite(STDERR, "Script not found\n");
			return FALSE;
		}

		$commandFound = FALSE;

		foreach ($project->config->scripts as $scriptName => $script){

			if ($scriptName != $commandText){
				continue;
			}

			if (is_array($script)){
				foreach ($script as $scriptItem){
					$this->commandExec($scriptItem);
				}
			} elseif (preg_match("/^([A-Z][^:]+)::([a-z].+)$/", $script, $match)){
				// "build": "App\\Build\\Controller::start"
				include_once($project->getVendorDir().'/autoload.php');
				fwrite(STDERR, "> $scriptName => $script\n", E_USER_NOTICE);
				$className = $match[1];
				$functionName = $match[2];
				$_ENV['FCPATH'] = $project->getPath();
				call_user_func(array($className, $functionName));
				unset($_ENV['FCPATH']);
			} else {
				// "build": "make"
				fwrite(STDERR, "> $scriptName => $script\n", E_USER_NOTICE);
				passthru($script);
			}

			$commandFound = TRUE;
		}

		if ($commandFound == FALSE) {
			fwrite(STDERR, "Script '$commandText' is not found\n");
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

	public function commandConfig(){

		$this->shiftCommand();

		$controller = new \Pdr\Ppm\Controller\Config;
		$option = new \Pdr\Ppm\Cli\Option;

		if ($option->getCommandCount() == 0){
			$_SERVER['argc']++;
			$_SERVER['argv'][] = 'index';
		}

		$controller->execute();
	}

	/**
	 * Print this information and exit
	 **/

	public function commandHelp() {
		fwrite(STDERR, file_get_contents(FCPATH.'/docs/ppm.txt'));
	}
}
