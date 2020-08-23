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

		// TODO resolve version from reference
		$packageVersion = $packageReference;

		$this->project = $project;

		$this->name = $packageName;
		$this->reference = $packageReference;
		$this->version = $packageVersion;
		$this->repositoryUrl = $packageRepositoryUrl;
		$this->path = $project->getVendorDir().'/'.$packageName;

		// repositoryUrl

		if (is_dir($this->path)){
			if (is_null($packageRepositoryUrl) == TRUE){
				$gitCommand = 'git'
					.' --git-dir '.$this->path.'/.git'
					.' --work-tree '.$this->path
					;
				$commandText = $gitCommand.' config remote.origin.url';
				$packageRepositoryUrl = \Pdr\Ppm\Console::text($commandText);
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
			$packageCommitHash = \Pdr\Ppm\Console::text($commandText);
			$packageCommitHash = trim($packageCommitHash);
			$this->commitHash = $packageCommitHash;
		}
	}

	public function create() {

		$config = new \Pdr\Ppm\GlobalConfig;

		$packageName = $this->name;
		// TODO resolve packageVersion from packageRevision
		$packageVersion = $this->version;
		$packageRepositoryUrl = $this->repositoryUrl;
		$packagePath = $this->project->getVendorDir().'/'.$packageName;

		if (is_dir(dirname($packagePath)) == FALSE){
			mkdir(dirname($packagePath));
		}

		if (is_dir($packagePath) == FALSE){
			mkdir($packagePath);
		}

		$gitCommand = 'git'
			.' --git-dir '.$packagePath.'/.git'
			.' --work-tree '.$packagePath
			;

		if (is_dir($packagePath.'/.git') ==  FALSE){

			$commandText = 'git init '.escapeshellarg($packagePath);
			\Pdr\Ppm\Console::exec($commandText);

			$commandText = $gitCommand.' remote add origin '.escapeshellarg($packageRepositoryUrl);
			\Pdr\Ppm\Console::exec($commandText);

			$commandText = $gitCommand.' fetch --depth=1 origin '.$packageVersion;
			\Pdr\Ppm\Console::exec($commandText);

			$commandText = $gitCommand.' checkout origin/'.$packageVersion.' -b '.$packageVersion;
			\Pdr\Ppm\Console::exec($commandText);
		}

		$commandText = $gitCommand.' log -n 1 --format=%H HEAD';
		$commitHash = \Pdr\Ppm\Console::text($commandText);
		$commitHash = trim($commitHash);
		$this->commitHash = $commitHash;
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


	public function getConfig(){

		$config = new \Pdr\Ppm\Config;
		$config->open($this);

		return $config;
	}

	public function getLockConfig(){

		$config = new \Pdr\Ppm\LockConfig;
		$config->open($this);

		return $config;
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

		fwrite(STDOUT, "Update {$this->name} {$this->repositoryUrl} ..\n");

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
	}
}
