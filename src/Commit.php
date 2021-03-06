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

class Commit {

	public $repository;
	public $commitHash;

	public function __construct() {}

	public function open(\Pdr\Ppm\Repository $repository, $commitReference){

		// TODO check if $commitReference is a commitHash

		$this->repository = $repository;
		$command  = $this->repository->getGitCommand();
		$command .= " log --format='%H' -n 1 $commitReference";

		$commitHash = \Pdr\Ppm\Console::text($command);
		if (empty($commitHash)){
			return false;
		}

		$this->commitHash = $commitHash;

		return true;
	}

	public function getFormat($formatText) {
		$command  = $this->repository->getGitCommand();
		$command .= " log --format=".escapeshellarg($formatText)." -n 1 ".$this->commitHash;
		return \Pdr\Ppm\Console::text($command);
	}
}
