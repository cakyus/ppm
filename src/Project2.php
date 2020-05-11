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

class Project2 {

	public $path;
	public $config;

	public $vendorDir;

	public function __construct() {

		$projectPath = '.';

		$file = $projectPath.'/composer.json';
		if (is_file($file) == false){
			\Pdr\Ppm\Logger::warn("composer.json is not found");
		}

		$config = new \Pdr\Ppm\Config;
		$config->load($file);

		$this->config = $config;
		$this->path = $projectPath;

		$this->vendorDir = 'vendor';
	}

	public function getConfig(){
		return $this->config;
	}

	public function getLockConfig() {
		$file = $this->getPath().'/composer.lock';
		$lockConfig = new \Pdr\Ppm\LockConfig;
		$lockConfig->load($file);
		return $lockConfig;
	}


	public function getPath(){
		return $this->path;
	}

	public function getRealPath() {
		return realpath($this->path);
	}

	public function getVendorDir(){
		$vendorDir = $this->path.'/vendor';
		$vendorDir = preg_replace("/^\.\//", '', $vendorDir);
		return $vendorDir;
	}

	public function getPackages(){

		$config = new \Pdr\Ppm\Git\Config;

		$config->openLocal();
		$packages = array();

		foreach ($config->getNames() as $configName) {
			if (preg_match("/^ppm\.packages\.([^\.]+)\.revision/", $configName, $match) == TRUE) {
				$package = new \stdClass;
				$package->name = $match[1];
				$package->revision = $config->get('ppm.packages.'.$package->name.'.revision');
				$package->commit = $config->get('ppm.packages.'.$package->name.'.commit');
				$packages[$package->name] = $package;
			}
		}

		return $packages;
	}

	public function getPackageNames() {

		$config = new \Pdr\Ppm\Git\Config;

		$config->openLocal();
		$packageNames = array();

		foreach ($config->getNames() as $configName) {
			if (preg_match("/^ppm\.packages\.([^\.]+)\.revision/", $configName, $match) == TRUE) {
				$packageNames[$match[1]] = $match[1];
			}
		}

		$packageNames = array_values($packageNames);

		return $packageNames;
	}

	public function getPackage($packageName){

		$packages = $this->getPackages();
		if (isset($packages[$packageName])){
			return $packages[$packageName];
		}

		return false;
	}

	/**
	 * @param $packageText
	 **/

	public function addPackage($packageText){

		if (preg_match("/^([^\/]+\/[^:]+):(.+)/", $packageText, $match) == false){
			\Pdr\Ppm\Logger::error('Parse error');
		}

		$packageName = $match[1];
		$packageVersion = $match[2];

		$package = new \Pdr\Ppm\Package;
		$package->open($this, $packageName, $packageVersion);

		if ($package->install() === true){
			$config = $this->getConfig();
			$config->data->require->$packageName = $packageVersion;
		}
	}

	/**
	 * Get project repository
	 *
	 * @return \Pdr\Ppm\Repository
	 * @throw \Exception
	 **/

	public function getRepository() {
		$repository = new \Pdr\Ppm\Repository;
		$repository->open($this->path.'/.git', $this->path);
		return $repository;
	}

}
