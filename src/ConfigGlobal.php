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
 * # Files
 *
 * $HOME/.config/ppm/config.json
 *
 * # Data Structure
 *
 * ## Repositories
 * https://getcomposer.org/doc/05-repositories.md
 *
 *   "repositories": [
 *     {
 *       "type": "package",
 *       "package": {
 *         "name": <packageName>,
 *         "version": <packageVersion>.
 *         "source": {
 *            "type": "cvs",
 *            "url": <repositoryUrl>
 *          }
 *        }
 *     }
 *     , ..
 *   ]
 **/

class ConfigGlobal {

	protected $project;
	protected $filePath;
	protected $config;

	public function __construct() {
		$this->config = new \stdClass;
	}

	public function open(\Pdr\Ppm\Project $project){

		$this->project = $project;

		foreach (array(
			$_SERVER['HOME'].'/.config',
			$_SERVER['HOME'].'/.config/ppm'
			) as $folderPath){
			if (is_dir($folderPath) == FALSE){
				mkdir($folderPath);
			}
		}

		$this->filePath = $_SERVER['HOME'].'/.config/ppm/config.json';

		if (is_file($this->filePath)){
			$fileText = file_get_contents($this->filePath);
			$fileData = json_decode($fileText);
			$this->config = $fileData;
		}

		if (empty($this->config->repositories) == TRUE){
			$this->config->repositories = array();
		}
	}

	public function replaceRepository($packageName, $packageVersion, $packageUrl){

		foreach ($this->config->repositories as $repository){
			if ($repository->type != 'package'){
				continue;
			}
			if (	$repository->package->name == $packageName
				&&	$repository->package->version == $packageVersion
				&&	$repository->package->source->url == $packageUrl
				){
				return $repository;
			}
		}

		$repository = new \stdClass;
		$repository->type = 'package';
		$repository->package = new \stdClass;
		$repository->package->name = $packageName;
		$repository->package->version = $packageVersion;
		$repository->package->source = new \stdClass;
		$repository->package->source->type = 'cvs';
		$repository->package->source->url = $packageUrl;

		$this->config->repositories[] = $repository;

		return $repository;
	}

	public function save() {

		trigger_error("Generate ConfigGlobal '{$this->filePath}' ..", E_USER_NOTICE);

		$project = new \stdClass;

		foreach ($this->project->packages as $package){
			$this->replaceRepository(
				$package->name,
				$package->version,
				$package->repositoryUrl
				);
		}

		foreach ($this->project->developmentPackages as $package){
			$this->replaceRepository(
				$package->name,
				$package->version,
				$package->repositoryUrl
				);
		}

		foreach ($this->project->dependencyPackages as $package){
			$this->replaceRepository(
				$package->name,
				$package->version,
				$package->repositoryUrl
				);
		}

		$filePath = $this->filePath;
		$fileText = json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		$fileLines = array();
		foreach (explode("\n", $fileText) as $fileLine){
			$fileLines[] = str_replace('    ', '  ', $fileLine);
		}
		$fileText = implode("\n", $fileLines);
		file_put_contents($filePath, $fileText);
	}
}
