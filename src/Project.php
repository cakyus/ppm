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

	public $path;
	public $config;

	public function __construct() {

		$packagePath = '.';

		$file = $packagePath.'/composer.json';
		if (is_file($file) == false){
			\Pdr\Ppm\Logger::warn("composer.json is not found");
		}

		$config = new \Pdr\Ppm\Config;
		$config->load($file);

		$this->config = $config;
		$this->path = $packagePath;
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

	public function getVendorDir(){
		$vendorDir = $this->path.'/vendor';
		$vendorDir = preg_replace("/^\.\//", '', $vendorDir);
		return $vendorDir;
	}

	public function getPackages(){

		$packages = array();

		if (empty($this->config->data->require)) {
			return $packages;
		}

		foreach ($this->config->data->require as $packageName => $packageVersion){
			$package = new \Pdr\Ppm\Package;
			$package->open($this, $packageName, $packageVersion);
			$packages[$packageName] = $package;
		}

		return $packages;
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

		$package->install();
	}
}
