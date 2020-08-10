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

/**
 * # Data Structure
 *
 *   packages
 *     + packageName
 *       + packageRevision: commitHash
 **/

class LockConfig {

	protected $filePath;
	protected $packages;

	public function __construct() {
		$this->packages = array();
	}

	public function open(\Pdr\Ppm\Package $package){

		$project = $package->getProject();
		$filePath = $project->getPath().'/ppm.lock.json';

		$this->filePath = $filePath;

		$packageData = new \stdClass;
		$packageDataSource = new \stdClass;
		$packageData->name = $package->name;
		$packageData->version = $package->version;
		$packageDataSource->type = 'cvs';
		$packageDataSource->reference = $package->commit;
		$packageData->source = $packageDataSource;

		$this->packages[] = $packageData;
	}

	public function save() {
		$fileData = $this->packages;
		$fileText = json_encode($fileData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		file_put_contents($this->filePath, $fileText);
	}
}
