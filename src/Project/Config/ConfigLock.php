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

namespace Pdr\Ppm\Project\Config;

/**
 * # Data Structure
 *
 *   "packages": [
 *     {
 *         "name": <packageName>
 *       , "version": <packageVersion>
 *       , "source": {
 *           "type": "cvs"
 *         , "reference": <packageCommit>
 *       }
 *     }
 *     , ..
 *   ]
 **/

class ConfigLock extends \Pdr\Ppm\Project\Config\ConfigFile {

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Validate configuration
	 **/

	public function loadFile($filePath) {

		parent::loadFile($filePath);

		if (empty($this->packages)){
			$this->packages = array();
		}

		return TRUE;
	}

	public function getPackage($packageName) {

		foreach ($this->packages as $package){

			if (empty($package->name) == FALSE && $package->name == $packageName){
				return $package;
			}
		}

		return FALSE;
	}

	public function setPackage($packageName, $packageVersion, $packageCommit) {

		foreach ($this->packages as $package){

			if (	empty($package->name) == FALSE && $package->name == $packageName
				&&	empty($package->version) == FALSE && $package->version == $packageVersion
				){
				if (empty($package->source)){
					$package->source = new \stdClass;
				}
				if (empty($package->source->type)){
					$package->source->type = 'cvs';
				}
				$package->source->reference = $packageCommit;
				return TRUE;
			}
		}

		$package = new \stdClass;
		$package->name = $packageName;
		$package->version = $packageVersion;
		$package->source = new \stdClass;
		$package->source->type = 'cvs';
		$package->source->reference = $packageCommit;

		// Indirect modification of overloaded property

		$packages = $this->packages;
		$packages[] = $package;
		$this->packages = $packages;

		return TRUE;
	}
}
