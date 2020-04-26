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
	public $version;
	public $remotePath;

	public $path;

	public function open(\Pdr\Ppm\Project $project, $name, $version) {
		$this->project = $project;
		$this->name = $name;
		$this->version = $version;
	}

	public function create($packageName, $packageVersion, $packagePath) {

		$config = new \Pdr\Ppm\GlobalConfig;

		if (is_dir($packagePath) == true) {
			throw new \Exception("packagePath is exists");
		}

		$repositoryUrl = $config->getRepositoryUrl($packageName);

		$command = 'git init '.escapeshellarg($packagePath);
		\Pdr\Ppm\Console::exec($command);

		$gitCommand = 'git'
			.' --git-dir '.$packagePath.'/.git'
			.' --work-tree '.$packagePath
			;

		$command = $gitCommand.' remote add origin '.escapeshellarg($repositoryUrl);
		\Pdr\Ppm\Console::exec($command);

		$command = $gitCommand.' fetch --depth=1 origin '.$packageVersion;
		\Pdr\Ppm\Console::exec($command);

		$command = $gitCommand.' checkout origin/'.$packageVersion.' -b '.$packageVersion;
		\Pdr\Ppm\Console::exec($command);

		// Execute install command

		chdir($packagePath);

		$controller = new \Pdr\Ppm\Controller;
		$controller->commandInstall();
	}

	public function install2(){

		trigger_error("name: ".$this->name, E_USER_NOTICE);
		trigger_error("version: ".$this->version, E_USER_NOTICE);
		trigger_error("remotePath: ".$this->remotePath, E_USER_NOTICE);

		$project = new \Pdr\Ppm\Project;
		$console = new \Pdr\Ppm\Console2;

		// Get localPath

		$localPath = $project->vendorDir.'/'.$this->name;

		trigger_error("localPath: ".$localPath, E_USER_NOTICE);

		// Get git reference

		if (preg_match("/^dev\-(.+)$/", $this->version, $match) == FALSE){
			throw new \Exception("Invalid version");
		}
		$gitReference = $match[1];

		trigger_error("gitReference: ".$gitReference, E_USER_NOTICE);

		// Initialize local repository

		if (is_dir($localPath) == FALSE){
			$commandText = 'git init '.$localPath;
			$console->exec($commandText);
		}

		$gitCommand = 'git'
			.' --git-dir '.$localPath.'/.git'
			.' --work-tree '.$localPath
			;

		// Remove git remote

		$commandText = $gitCommand.' remote';
		foreach ($console->line($commandText) as $remoteName){
			if (	$remoteName != 'origin'
				&&	$remoteName != 'composer'
				){
				continue;
			}
			$commandText = $gitCommand.' remote rm '.$remoteName;
			$console->exec($commandText);
		}

		// Append git remote

		$commandText = $gitCommand.' remote add origin '.$this->remotePath;
		$console->exec($commandText);

		$commandText = $gitCommand.' remote add composer '.$this->remotePath;
		$console->exec($commandText);

		$commandText = $gitCommand.' fetch --depth=1 origin '.$gitReference;
		$console->exec($commandText);

		$commandText = $gitCommand.' checkout '.$gitReference;
		$console->exec($commandText);

		$commandText = $gitCommand.' branch --set-upstream-to=origin/'.$gitReference;
		$console->exec($commandText);

		// TODO Update config

		// Update autoload

		$controller = new \Pdr\Ppm\Controller;
		$controller->commandCreateAutoload();
		$controller->commandCreateLock();
	}

	/**
	 * @deprecated use install2
	 **/

	public function install($commitHash=null) {

		if (	is_dir($this->project->getVendorDir().'/'.$this->name)
			&&	is_dir($this->project->getVendorDir().'/'.$this->name.'/.git')
			){
			return true;
		}

		if (preg_match("/^ext\-(.+)$/", $this->name, $match)) {
			trigger_error("SKIP check installed extension ".$match[1], E_USER_NOTICE);
			return TRUE;
		}

		$repositoryPath = $this->project->getVendorDir().'/'.$this->name;
		$repositoryDir  = dirname($repositoryPath);

		if (is_dir($this->project->getVendorDir()) == false){
			mkdir($this->project->getVendorDir());
		}

		if (is_dir($repositoryDir) == false){
			mkdir($repositoryDir);
		}

		if (is_dir($repositoryPath) == false){
			mkdir($repositoryPath);
		}

		$repositoryUrl = $this->getRepositoryUrl();

		if (empty($repositoryUrl)){
			throw new \Exception("Repository does not exists: {$this->name}");
		}

		$gitCommand = 'git'
			.' --git-dir '.$repositoryPath.'/.git'
			.' --work-tree '.$repositoryPath
			;

		\Pdr\Ppm\Console::exec('git init '.$repositoryPath);
		\Pdr\Ppm\Console::exec($gitCommand.' remote add composer '.$repositoryUrl);
		\Pdr\Ppm\Console::exec($gitCommand.' remote add origin '.$repositoryUrl);

		if (is_null($commitHash) == false) {
			\Pdr\Ppm\Console::exec($gitCommand.' fetch origin '.$this->getVersion());
			\Pdr\Ppm\Console::exec($gitCommand.' checkout '.$commitHash.' -b '.$this->getVersion());
		} else {
			\Pdr\Ppm\Console::exec($gitCommand.' fetch --depth=1 origin '.$this->getVersion());
			\Pdr\Ppm\Console::exec($gitCommand.' checkout origin/'.$this->getVersion(). ' -b '.$this->getVersion());
		}

		// execute scripts post-package-install

		\Pdr\Ppm\Logger::debug("[{$this->name}] execute post-package-install ..");

		$packageDirectory = $this->getPath();
		$controller = new \Pdr\Ppm\Controller;
		$controller->commandExec('post-package-install', $packageDirectory);

		return true;
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
	 * Get git reference, ie. branchName, tagName, or commitHash
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
}
