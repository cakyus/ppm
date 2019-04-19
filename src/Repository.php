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

class Repository {

	protected $workTree;
	protected $gitDir;

	public function open($gitDir, $workTree=null){

		if (is_dir($gitDir) == false){
			throw new \Exception("gitDir is not found");
		}

		$this->gitDir = $gitDir;
		$this->workTree = $workTree;
	}
}

