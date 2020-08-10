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

class ConfigLock {

	protected $project;
	protected $filePath;

	public function open(\Pdr\Ppm\Project $project){

		$this->project = $project;
		$this->filePath = $this->project->getPath().'/ppm.lock.json';

		if (is_file($this->filePath)){

			$fileText = file_get_contents($this->filePath);
			$fileData = json_decode($fileText);

			foreach ($fileData->packages as $packageData){

				foreach ($this->project->packages as $package){
					if ($package->name == $packageData->name){
						$package->version = $packageData->version;
						$package->commitHash = $packageData->source->reference;
					}
				}

				foreach ($this->project->developmentPackages as $package){
					if ($package->name == $packageData->name){
						$package->version = $packageData->version;
						$package->commitHash = $packageData->source->reference;
					}
				}
			}
		}
	}

	public function save() {

		$project = new \stdClass;
		$project->packages = array();

		foreach ($this->project->packages as $package){

			$packageData = new \stdClass;
			$packageDataSource = new \stdClass;
			$packageData->name = $package->name;
			$packageData->version = $package->version;
			$packageDataSource->type = 'cvs';
			$packageDataSource->reference = $package->commitHash;
			$packageData->source = $packageDataSource;

			$project->packages[] = $packageData;
		}

		foreach ($this->project->developmentPackages as $package){

			$packageData = new \stdClass;
			$packageDataSource = new \stdClass;
			$packageData->name = $package->name;
			$packageData->version = $package->version;
			$packageDataSource->type = 'cvs';
			$packageDataSource->reference = $package->commitHash;
			$packageData->source = $packageDataSource;

			$project->packages[] = $packageData;
		}

		$filePath = $this->project->getPath().'/ppm.lock.json';
		$fileText = json_encode($project, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		$fileLines = array();
		foreach (explode("\n", $fileText) as $fileLine){
			$fileLines[] = str_replace('    ', '  ', $fileLine);
		}
		$fileText = implode("\n", $fileLines);
		file_put_contents($filePath, $fileText);
	}
}
