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

class Project {

	public $name;
	public $description;

	public $packages;
	public $developmentPackages;

	public $path;

	public $config;
	public $configLock;

	public $vendorDir;

	public function __construct() {

		$this->path = '.';
		$this->vendorDir = 'vendor';

		$this->packages = array();
		$this->developmentPackages = array();

		$config = new \Pdr\Ppm\Config;
		$this->config = $config;


		$configLock = new \Pdr\Ppm\ConfigLock;
		$this->configLock = $configLock;

		$config->open($this);
		$configLock->open($this);
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

		$packages = array();

		foreach (array('require', 'require-dev') as $propertyName) {
			foreach ($this->config->data->$propertyName as $packageName => $packageVersion){
				$package = new \Pdr\Ppm\Package;
				$package->open($this, $packageName, $packageVersion);
				$packages[$packageName] = $package;
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

	public function createPackage($packageName, $packageRevision, $packageRepositoryUrl) {

		$packageVersion = $packageRevision;

		$package = new \Pdr\Ppm\Package;
		$package->open($this, $packageName, $packageVersion, $packageRepositoryUrl);
		$package->create();

		$this->addPackage($package);
		$this->config->save();
		$this->configLock->save();
	}

	public function addPackage($package) {
		foreach ($this->packages as $packageIndex => $packageItem){
			if ($package->name == $packageItem->name){
				unset($this->packages[$packageIndex]);
			}
		}
		$this->packages[] = $package;
	}

	public function createDelopmentPackage($packageName, $packageRevision, $packageRepositoryUrl) {

		$packageVersion = $packageRevision;

		$package = new \Pdr\Ppm\Package;
		$package->open($this, $packageName, $packageVersion, $packageRepositoryUrl);
		$package->create();

		$this->addDevelopmentPackage($package);
		$this->config->save();
		$this->configLock->save();
	}

	public function addDevelopmentPackage($package) {
		foreach ($this->developmentPackages as $packageIndex => $packageItem){
			if ($package->name == $packageItem->name){
				unset($this->developmentPackages[$packageIndex]);
			}
		}
		$this->developmentPackages[] = $package;
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
