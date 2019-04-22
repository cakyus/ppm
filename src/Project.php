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

		//~ $packagePath = getcwd();
		$packagePath = '.';

		$file = $packagePath.'/composer.json';
		if (is_file($file) == false){
			throw new \Exception("composer.json is not found");
		}

		$config = new \Pdr\Ppm\Config;
		$config->loadFile($file);

		$this->config = $config;
		$this->path = $packagePath;
	}

	public function getConfig(){
		return $this->config;
	}

	public function getPath(){
		return $this->path;
	}

	public function getVendorDir(){
		return $this->path.'/vendor';
	}

	public function getPackages(){

		$packages = array();

		foreach ($this->config->data->require as $packageName => $packageVersion){
			$package = new \Pdr\Ppm\Package;
			$package->open($this, $packageName, $packageVersion);
			$packages[] = $package;
		}

		return $packages;
	}
}
