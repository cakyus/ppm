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

class Branch extends \Pdr\Ppm\Commit {

	public $name;

	public function __construct() {
		parent::__construct();
	}

	/**
	 * @deprecated use Repository.checkout()
	 **/

	public function open(\Pdr\Ppm\Repository $repository, $commitReference){

		if (parent::open($repository, $commitReference) == TRUE){
			$this->name = $commitReference;
			return TRUE;
		}

		return FALSE;
	}
}
