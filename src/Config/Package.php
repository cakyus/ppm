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

namespace Pdr\Ppm\Config;

/**
 * $HOME/.config/ppm/packages.json
 *
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

class Package {

	public $packages;

	protected $project;

	public function __construct() {
		$this->packages = array();
	}

	public function open(\Pdr\Ppm\Project $project) {
		$this->project = $project;
	}

	public function save() {

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

		foreach ($this->project->getPackages() as $package){

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
