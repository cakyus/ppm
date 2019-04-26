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

class LockConfig extends \Pdr\Ppm\Config {

	public function __construct() {
		parent::__construct();
	}

	public function getPackage($packageName) {

		foreach ($this->data->packages as $packageData){
			if ($packageData->name == $packageName){
				return $packageData;
			}
		}

		return false;
	}

	public function getPackageCommitHash($packageName) {

		if ( ( $packageData = $this->getPackage($packageName) ) === false ){
			return false;
		}

		if (empty( $packageData->source->reference) ){
			return false;
		}

		return $packageData->source->reference;
	}
}

