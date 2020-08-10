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

	// @deprecated use revision
	public $version;

	public $revision;
	public $commit;

	// @deprecated use repository
	public $remotePath;

	public $repository;
	public $repositoryUrl;

	public $path;

	public function open(\Pdr\Ppm\Project $project, $name, $revision, $repositoryUrl) {
		$this->project = $project;
		$this->name = $name;
		$this->revision = $revision;
		$this->version = $revision;
		$this->repositoryUrl = $repositoryUrl;
	}

	public function create() {

		$config = new \Pdr\Ppm\GlobalConfig;

		$packageName = $this->name;
		// TODO resolve packageVersion from packageRevision
		$packageVersion = $this->revision;
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
		$this->commit = $commitHash;

		$lockConfig = new \Pdr\Ppm\LockConfig;
		$lockConfig->open($this);
		$lockConfig->save();
	}

	public function install2(){

		$project = new \Pdr\Ppm\Project;
		$console = new \Pdr\Ppm\Console2;

		// Get localPath

		$localPath = $project->vendorDir.'/'.$this->name;

		// Initialize local repository

		$commandText = 'git init '.$localPath;
		$console->exec($commandText);

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

		$commandText = $gitCommand.' remote add origin '.$this->repository;
		$console->exec($commandText);

		// Check remote revision

		$commandText = $gitCommand.' ls-remote origin master';
		$revisions = $console->line($commandText);
		if (count($revisions) == 0){
			fwrite(STDERR, "WARNING revision not exist\n");
			return FALSE;
		}

		$commandText = $gitCommand.' fetch --depth=1 origin '.$this->revision;
		$console->exec($commandText);

		$commandText = $gitCommand.' checkout '.$this->revision;
		$console->exec($commandText);

		$commandText = $gitCommand.' branch --set-upstream-to=origin/'.$this->revision;
		$console->exec($commandText);

		// update commit value
		$commandText = $gitCommand.' log -n 1 --format=%H';
		$this->commit = $console->text($commandText);

		$this->createLock();

		return TRUE;
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

	public function getProject() {
		return $this->project;
	}
}
