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
	public $dependencyPackages;

	public $autoload;

	public $path;

	public $config;
	public $configLock;

	public $vendorDir;

	public function __construct() {

		$this->path = '.';
		$this->vendorDir = 'vendor';

		$this->packages = array();
		$this->developmentPackages = array();
		$this->dependencyPackages = array();

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

	/**
	 * @return array \Pdr\Ppm\Package
	 **/

	public function getPackages(){
		return $this->packages;
	}

	/**
	 * @return array \Pdr\Ppm\Package
	 **/

	public function getDevelopmentPackages(){
		return $this->developmentPackages;
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

	/**
	 * Update $HOME/.config/ppm/packages.json
	 *
	 * Schema
	 *
	 *   [
	 *     {
	 *         "name": <packageName>
	 *       , "repositories": [
	 *         {
	 *            "url": <packageRepositoryUrl>
	 *         }
	 *       ]
	 *     }
	 *   ]
	 *
	 **/

	public function updateConfigGlobalPackage() {

		if (is_dir($_SERVER['HOME'].'/.config') == FALSE){
			throw new \Exception("Folder not found '\$HOME/.config'");
		}

		if (is_dir($_SERVER['HOME'].'/.config/ppm') == FALSE){
			mkdir($_SERVER['HOME'].'/.config/ppm');
		}

		$filePath = $_SERVER['HOME'].'/.config/ppm/packages.json';
		$configPackages = array();

		if (is_file($filePath)){
			$fileText = file_get_contents($filePath);
			$configPackages = json_decode($fileText);
		}

		foreach ($this->getPackages() as $package){

			$configPackageFound = FALSE;
			$configPackageRepositoryFound = FALSE;

			foreach ($configPackages as $configPackage){
				if ($package->name == $configPackage->name){
					$configPackageFound = TRUE;
					foreach ($configPackage->repositories as $configPackageRepository){
						if ($package->repositoryUrl == $configPackageRepository->url){
							$configPackageRepositoryFound = TRUE;
							break;
						}
					}
					break;
				}
			}

			if ($configPackageFound == FALSE){

				$configPackage = new \stdClass;
				$configRespository = new \stdClass;

				$configPackage->name = $package->name;
				$configRespository->url = $package->repositoryUrl;
				$configPackage->repositories[] = $configRespository;

				$configPackages[] = $configPackage;

			} elseif ($configPackageRepositoryFound == FALSE){

				foreach ($configPackages as $configPackage){
					if ($package->name == $configPackage->name){
						$configRespository = new \stdClass;
						$configRespository->url = $package->repositoryUrl;
						$configPackage->repositories[] = $configRespository;
						break;
					}
				}

			}
		}

		$fileText = json_encode($configPackages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		$fileLines = array();
		foreach (explode("\n", $fileText) as $fileLine){
			$fileLines[] = str_replace('    ', '  ', $fileLine);
		}
		$fileText = implode("\n", $fileLines);
		file_put_contents($filePath, $fileText);
	}

}
