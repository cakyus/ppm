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

class Config {

	protected $project;
	protected $filePath;

	public function __construct() {}

	public function open(\Pdr\Ppm\Project $project){

		$this->project = $project;
		$this->filePath = $this->project->getPath().'/ppm.json';

		if (is_file($this->filePath)){

			$fileText = file_get_contents($this->filePath);
			$fileData = json_decode($fileText);

			$this->project->name = $fileData->name;
			$this->project->description = $fileData->description;

			$this->project->packages = array();
			$attributeName = 'require';
			foreach ($fileData->$attributeName as $packageDataName => $packageDataReference){
				$package = new \Pdr\Ppm\Package;
				$package->open($project, $packageDataName, $packageDataReference, NULL);
				$this->project->packages[] = $package;
			}

			$this->project->developmentPackages = array();
			$attributeName = 'require-dev';
			foreach ($fileData->$attributeName as $packageDataName => $packageDataReference){
				$package = new \Pdr\Ppm\Package;
				$package->open($project, $packageDataName, $packageDataReference, NULL);
				$this->project->developmentPackages[] = $package;
			}
		}

	}

	public function save(){

		$project = new \stdClass;

		$project->name = $this->project->name;
		$project->description = $this->project->description;

		$attributePackage = 'require';
		$project->$attributePackage = new \stdClass;
		$attributeDevelopmentPackage = 'require-dev';
		$project->$attributeDevelopmentPackage = new \stdClass;

		foreach ($this->project->packages as $package){
			$packageName = $package->name;
			$packageReference = $package->reference;
			$project->$attributePackage->$packageName = $packageReference;
		}

		foreach ($this->project->developmentPackages as $package){
			$packageName = $package->name;
			$packageReference = $package->reference;
			$project->$attributeDevelopmentPackage->$packageName = $packageReference;
		}

		$fileText = json_encode($project, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		$fileLines = array();
		foreach (explode("\n", $fileText) as $fileLine){
			$fileLines[] = str_replace('    ', '  ', $fileLine);
		}
		$fileText = implode("\n", $fileLines);
		file_put_contents($this->filePath, $fileText);
	}
}
