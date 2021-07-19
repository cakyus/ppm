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

namespace Pdr\Ppm\Project\Config;

class ConfigFile extends \Pdr\Ppm\Common\Attribute {

	protected $_filePath;

	public function __construct() {
		parent::__construct();
	}

	public function loadFile($filePath) {
		if (is_file($filePath)){
			return FALSE;
		}
		if ( ! $fileText = file_get_contents($filePath)){
			return FALSE;
		}
		$fileData = json_decode($fileText);
		if (json_last_error() !== JSON_ERROR_NONE){
			return FALSE;
		}
		$this->_filePath = $filePath;
		return TRUE;
	}

	public function save() {
		$fileText = json_encode($this->saveObject());
		return file_put_contents($this->_filePath, $fileText);
	}
}
