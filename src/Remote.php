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

class Remote {

	public $name;
	public $url;

	protected $repository;

	public function __construct($repository) {
		$this->repository = $repository;
	}

	public function getBranches() {

	}

	public function getBranch($branchName) {

	}

	public function getTags() {

	}

	public function getTag($tagName) {

	}

	public function fetch($refspec) {

		$command  = $this->repository->getGitCommand();
		$command .= ' fetch ';
		if (empty($this->name) == false){
			$command .= ' '.$this->name;
		} else {
			$command .= ' '.$this->url;
		}
		$command .= ' '.$refspec;

		\Pdr\Ppm\Console::exec($command);
	}
}

