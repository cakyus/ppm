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

class Package {

	public $project;

	public $name;

	// git reference
	public $reference; // eg. master, 1.*

	// git branch or tag
	public $version; // eg. master, 1.0.10

	// git commit hash

	public $commitHash;

	public $repositoryUrl;

	public $path;

	public function open(\Pdr\Ppm\Project $project, $packageName, $packageReference, $packageRepositoryUrl) {

		$console = new \Pdr\Ppm\Console;

		$packageVersion = $packageReference;

		$this->project = $project;

		$this->name = $packageName;
		$this->reference = $packageReference;
		$this->version = $packageVersion;
		$this->version = preg_replace("/^dev\-/", "", $packageVersion);
		$this->repositoryUrl = $packageRepositoryUrl;
		$this->path = $project->getVendorDir().'/'.$packageName;

		// resolve repositoryUrl

		if (is_dir($this->path)){
			if (is_null($packageRepositoryUrl) == TRUE){
				$gitCommand = 'git'
					.' --git-dir '.$this->path.'/.git'
					.' --work-tree '.$this->path
					;
				$commandText = $gitCommand.' config remote.origin.url';
				$packageRepositoryUrl = $console->text($commandText);
				$packageRepositoryUrl = trim($packageRepositoryUrl);
				$this->repositoryUrl = $packageRepositoryUrl;
			}
		}

		// commitHash

		if (is_dir($this->path) && is_dir($this->path.'/.git')){

			$gitCommand = 'git'
				.' --git-dir '.$this->path.'/.git'
				.' --work-tree '.$this->path
				;

			$commandText = $gitCommand.' log -n 1 --format=%H';
			$packageCommitHash = $console->text($commandText);
			$packageCommitHash = trim($packageCommitHash);
			$this->commitHash = $packageCommitHash;
		}

		if (empty($packageRepositoryUrl)){
			trigger_error("Can not resolve packageRepositoryUrl"
				."\n (packageName: $packageName, packageRepositoryUrl: $packageRepositoryUrl)"
				, E_USER_ERROR
				);
			exit(1);
		}
	}

	public function create() {

		$config = new \Pdr\Ppm\GlobalConfig;
		$console = new \Pdr\Ppm\Console;

		$project = $this->project;
		$packageName = $this->name;
		$packageReference = $this->reference;
		$packageVersion = $this->version;
		$packageRepositoryUrl = $this->repositoryUrl;
		$packagePath = $this->project->getVendorDir().'/'.$packageName;

		if (is_dir(dirname($packagePath)) == FALSE){
			mkdir(dirname($packagePath));
		}

		if (is_dir($packagePath) == FALSE){
			mkdir($packagePath);
		}

		trigger_error("Install $packageName:$packageVersion $packageRepositoryUrl ..", E_USER_NOTICE);

		$gitCommand = 'git'
			.' --git-dir '.$packagePath.'/.git'
			.' --work-tree '.$packagePath
			;

		if (is_dir($packagePath.'/.git') ==  FALSE){

			$commandText = 'git init '.escapeshellarg($packagePath);
			$console->exec($commandText);

			$commandText = $gitCommand.' remote add origin '.escapeshellarg($packageRepositoryUrl);
			$console->exec($commandText);

			$commandText = $gitCommand.' fetch --depth=1 origin '.$packageVersion;
			$console->exec($commandText);

			$commandText = $gitCommand.' checkout origin/'.$packageVersion.' -b '.$packageVersion;
			$console->exec($commandText);
		}

		$commandText = $gitCommand.' log -n 1 --format=%H HEAD';
		$packageCommitHash = $console->text($commandText);
		$packageCommitHash = trim($packageCommitHash);
		$this->commitHash = $packageCommitHash;

		if (isset($project->dependencyPackages[$packageName]) == FALSE){
			$object = new \stdClass;
			$object->name = $packageName;
			$object->version = $packageVersion;
			$object->commitHash = $packageCommitHash;
			$object->source = new \stdClass;
			$object->source->type = 'cvs';
			$object->source->reference = $packageCommitHash;
			$project->dependencyPackages[$packageName] = $object;
		}

		// install dependencies

		$packageConfigPath = $this->getPath().'/ppm.json';
		if (is_file($packageConfigPath)){
			$packageConfigText = file_get_contents($packageConfigPath);
			$packageConfig = json_decode($packageConfigText);
			if (json_last_error() != 0){
				trigger_error(json_last_error_msg(), E_USER_WARNING);
				throw new \Exception("JSON Parse Error. '$packageConfigPath'");
			}
			$attributeName = 'require';
			if (empty($packageConfig->$attributeName) == FALSE){
				foreach ($packageConfig->$attributeName as $packageItemName => $packageItemReference){
					if (isset($project->dependencyPackages[$packageItemName]) == FALSE){
						$package = new \Pdr\Ppm\Package;
						$package->open($this->project, $packageItemName, $packageItemReference, NULL);
						$package->create();
					}
				}
			}
			$attributeName = 'require-dev';
			if (empty($packageConfig->$attributeName) == FALSE){
				foreach ($packageConfig->$attributeName as $packageItemName => $packageItemReference){
					if (isset($project->dependencyPackages[$packageItemName]) == FALSE){
						$package = new \Pdr\Ppm\Package;
						$package->open($this->project, $packageItemName, $packageItemReference, NULL);
						$package->create();
					}
				}
			}
		}
	}

	public function getRepositoryUrl() {

		$config = new \Pdr\Ppm\GlobalConfig;
		$config->open();

		foreach ($config->data->repositories as $repositoryName => $repository){
			if ($repositoryName == $this->name){
				return $repository->url;
			}
		}

		return false;
	}

	public function getPath(){
		return $this->project->path.'/vendor/'.$this->name;
	}

	public function getRepository(){

		$repositoryPath = $this->getPath();

		if (is_dir($repositoryPath) == false){
			return false;
		}

		$repository = new \Pdr\Ppm\Repository;
		$repository->open($repositoryPath.'/.git', $repositoryPath);

		return $repository;
	}

	/**
	 * Get Version
	 **/

	public function getVersion() {

		if (preg_match("/^dev\-(.+)/", $this->version, $match)){
			return $match[1];
		}

		if ($this->name == 'php') {
			return phpversion();
		}

		// tags , eg. "3.*"

		// 1. find remote repository url

		$globalConfig = new \Pdr\Ppm\GlobalConfig;
		$repositoryUrl = $globalConfig->getRepositoryUrl($this->name);

		if (empty($repositoryUrl)) {
			throw new \Exception("repositoryUrl is not found. ".$this->name);
		}

		// 2. find tags

		$command = 'git ls-remote --tags '.$repositoryUrl.' '.$this->version;
		$line = \Pdr\Ppm\Console::line($command);

		if (count($line) == 0) {
			throw new \Exception("version not found, $command");
		}

		$versions = array();
		foreach ($line as $lineItem) {
			if (preg_match("/^([a-f0-9]+)\s([^\s]+)$/", $lineItem, $match)) {
				$versions[$match[1]] = basename($match[2]);
			}
		}

		usort($versions, 'version_compare');

		$version = end($versions);

		return $version;
	}

	/**
	 * @return string branch|tag
	 **/

	public function getVersionType() {

		if (preg_match("/^dev\-(.+)/", $this->version, $match)){
			return 'branch';
		} else {
			return 'tag';
		}
	}

	public function getProject() {
		return $this->project;
	}

	/**
	 * Get Git Command
	 *
	 * @return string git command
	 **/

	protected function getGitCommand() {

		if (is_dir($this->path) == FALSE){
			throw new \Exception("Package workTree not exits. '{$this->path}'");
		}

		if (is_dir($this->path.'/.git') == FALSE){
			throw new \Exception("Package gitDir not exits. '{$this->path}/.git'");
		}

		return 'git'
			.' --git-dir '.$this->path.'/.git'
			.' --work-tree '.$this->path
			;
	}

	public function update() {

		fwrite(STDOUT, "Update {$this->name} {$this->version} {$this->repositoryUrl} ..\n");

		$gitCommand = $this->getGitCommand();

		$commandText = $gitCommand.' fetch origin '.escapeshellarg($this->version);
		\Pdr\Ppm\Console::exec($commandText);

		$commandText = $gitCommand.' status --short';
		$commandLine = \Pdr\Ppm\Console::line($commandText);
		if (\count($commandLine) == 0){
			// no local changes
			$commandText = $gitCommand.' merge --ff-only origin/'.$this->version;
			\Pdr\Ppm\Console::exec($commandText);
		}

		$project = $this->project;
		$packageName = $this->name;
		$packageReference = $this->reference;
		$packageVersion = $this->version;
		$packageCommitHash = $this->commitHash;
		$packageRepositoryUrl = $this->repositoryUrl;
		$packagePath = $this->project->getVendorDir().'/'.$packageName;

		if (isset($project->dependencyPackages[$packageName]) == FALSE){
			$object = new \stdClass;
			$object->name = $packageName;
			$object->version = $packageVersion;
			$object->commitHash = $packageCommitHash;
			$object->source = new \stdClass;
			$object->source->type = 'cvs';
			$object->source->reference = $packageCommitHash;
			$project->dependencyPackages[$packageName] = $object;
		}

		// install dependencies

		$packageConfigPath = $this->getPath().'/ppm.json';
		if (is_file($packageConfigPath)){
			$packageConfigText = file_get_contents($packageConfigPath);
			$packageConfig = json_decode($packageConfigText);
			if (json_last_error() != 0){
				trigger_error(json_last_error_msg(), E_USER_WARNING);
				throw new \Exception("JSON Parse Error. '$packageConfigPath'");
			}
			$attributeName = 'require';
			if (empty($packageConfig->$attributeName) == FALSE){
				foreach ($packageConfig->$attributeName as $packageItemName => $packageItemReference){
					if (isset($project->dependencyPackages[$packageItemName]) == FALSE){
						$package = new \Pdr\Ppm\Package;
						$package->open($this->project, $packageItemName, $packageItemReference, NULL);
						$package->update();
					}
				}
			}
			$attributeName = 'require-dev';
			if (empty($packageConfig->$attributeName) == FALSE){
				foreach ($packageConfig->$attributeName as $packageItemName => $packageItemReference){
					if (isset($project->dependencyPackages[$packageItemName]) == FALSE){
						$package = new \Pdr\Ppm\Package;
						$package->open($this->project, $packageItemName, $packageItemReference, NULL);
						$package->update();
					}
				}
			}
		}
	}
}
