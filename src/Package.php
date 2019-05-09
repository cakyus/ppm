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
	public $path;

	public function open(\Pdr\Ppm\Project $project, $name, $version) {
		$this->project = $project;
		$this->name = $name;
		$this->version = $version;
	}

	public function install() {

		if (	is_dir($this->project->getVendorDir().'/'.$this->name)
			&&	is_dir($this->project->getVendorDir().'/'.$this->name.'/.git')
			){
			return true;
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
			\Pdr\Ppm\Logger::error("Repository does not exists: {$this->name}");
		}

		$gitCommand = 'git'
			.' --git-dir '.$repositoryPath.'/.git'
			.' --work-tree '.$repositoryPath
			;

		\Pdr\Ppm\Console::exec('git init '.$repositoryPath);
		\Pdr\Ppm\Console::exec($gitCommand.' remote add composer '.$repositoryUrl);
		\Pdr\Ppm\Console::exec($gitCommand.' remote add origin '.$repositoryUrl);

		\Pdr\Ppm\Console::exec($gitCommand.' fetch origin '.$this->getVersion());

		\Pdr\Ppm\Console::exec($gitCommand.' checkout origin/'.$this->getVersion(). ' -b '.$this->getVersion());

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

		throw new \Exception("Parse error");
	}

	/**
	 * @return string branch|tag
	 **/

	public function getVersionType() {

		if (preg_match("/^dev\-(.+)/", $this->version, $match)){
			return 'branch';
		}

		throw new \Exception("Parse error");
	}
}
