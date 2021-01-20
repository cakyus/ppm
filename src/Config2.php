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
 * Composer Configuration
 **/

class Config2 extends \Pdr\Ppm\Common\Attribute {

	public $_filePath;

	public function __construct() {
		parent::__construct();
	}

	public function open($filePath) {

		if (is_file($filePath) == FALSE){
			return FALSE;
		}


		$this->_filePath = $filePath;
		$fileText = file_get_contents($filePath);
		$object = json_decode($fileText);
		$this->loadObject($object);

		return TRUE;
	}

	public function save() {
		$object = $this->saveObject();
		$fileText = json_encode($object, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		return file_put_contents($this->_filePath, $fileText);
	}
}
