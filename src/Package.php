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

class Package {

	public $project;
	public $name;
	public $version;
	public $path;

	public function open(\Pdr\Ppm\Project $project, $name, $version) {
		$this->project = $project;
		$this->name = $name;
		$this->version = $version;
	}

	public function getConfig(){

		$config = new \Pdr\Ppm\Config;
		$config->open($this);

		return $config;
	}

	public function getPath(){
		return $this->project->path.'/vendor/'.$this->name;
	}

	public function getRepository(){

		$repositoryPath = $this->getPath();

		if (is_dir($repositoryPath) == false){
			\Pdr\Logger::warn("repositoryPath is not found: '$repositoryPath'");
			return false;
		}

		$repository = new \Pdr\Ppm\Repository;
		$repository->open($repositoryPath.'/.git', $repositoryPath);

		return $repository;
	}
}

