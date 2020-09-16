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

	public $autoload;

	public $scripts;
	public $license;

	protected $filePath;

	public function __construct() {}

	public function open(\Pdr\Ppm\Project $project){

		$this->project = $project;
		$this->filePath = $this->project->getPath().'/ppm.json';
		$this->scripts = array();

		if (is_file($this->filePath)){

			$fileText = file_get_contents($this->filePath);
			$fileData = json_decode($fileText);

			$this->project->name = $fileData->name;
			if (isset($fileData->description) == TRUE){
				$this->project->description = $fileData->description;
			} else {
				$this->project->description = NULL;
			}

			foreach (array(
				'scripts'
				, 'license'
				) as $attributeName){

				if (isset($fileData->$attributeName) == TRUE){
					$this->$attributeName = $fileData->$attributeName;
				} else {
					$this->$attributeName = new \stdClass;
				}
			}

			$this->project->packages = array();
			$attributeName = 'require';
			if (isset($fileData->$attributeName) == TRUE) {
				foreach ($fileData->$attributeName as $packageDataName => $packageDataReference){
					$package = new \Pdr\Ppm\Package;
					$package->open($project, $packageDataName, $packageDataReference, NULL);
					$this->project->packages[] = $package;
				}
			} else {
				$fileData->$attributeName = new \stdClass;
			}

			$this->project->developmentPackages = array();
			$attributeName = 'require-dev';
			if (isset($fileData->$attributeName) == TRUE) {
				foreach ($fileData->$attributeName as $packageDataName => $packageDataReference){
					$package = new \Pdr\Ppm\Package;
					$package->open($project, $packageDataName, $packageDataReference, NULL);
					$this->project->developmentPackages[] = $package;
				}
			} else {
				$fileData->$attributeName = new \stdClass;
			}
		}

	}

	public function save(){

		$project = new \stdClass;

		$project->name = $this->project->name;
		$project->description = $this->project->description;

		$packages = $this->project->packages;

		if (count($packages) > 0){

			// sort by package name
			$packageIndexes = array_keys($packages);
			foreach ($packageIndexes as $packageIndex){
				$package = $packages[$packageIndex];
				$packages[$package->name] = $package;
				unset($packages[$packageIndex]);
			}

			ksort($packages);

			$attributePackage = 'require';
			$project->$attributePackage = new \stdClass;

			foreach ($packages as $package){
				$packageName = $package->name;
				$packageReference = $package->reference;
				$project->$attributePackage->$packageName = $packageReference;
			}
		}

		$packages = $this->project->developmentPackages;

		if (count($packages) > 0){

			// sort by package name
			$packageIndexes = array_keys($packages);
			foreach ($packageIndexes as $packageIndex){
				$package = $packages[$packageIndex];
				$packages[$package->name] = $package;
				unset($packages[$packageIndex]);
			}

			ksort($packages);

			$attributePackage = 'require-dev';
			$project->$attributePackage = new \stdClass;

			foreach ($packages as $package){
				$packageName = $package->name;
				$packageReference = $package->reference;
				$project->$attributePackage->$packageName = $packageReference;
			}
		}

		if (empty($this->autoload) == FALSE){
			$project->autoload = $this->autoload;
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
