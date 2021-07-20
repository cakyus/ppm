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

		$this->_filePath = $filePath;

		if (is_file($filePath) == FALSE){
			return FALSE;
		}

		if ( ! $fileText = file_get_contents($filePath)){
			return FALSE;
		}

		$fileData = json_decode($fileText);
		if (json_last_error() !== JSON_ERROR_NONE){
			return FALSE;
		}

		$this->loadObject($fileData);

		return TRUE;
	}

	public function getFilePath() {
		return $this->_filePath;
	}

	public function save() {

		$fileData = $this->saveObject();
		$fileText = json_encode($fileData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

		// replace 4 spaces to 2 spaces

		$fileLine = explode("\n", $fileText);
		foreach ($fileLine as $fileLineIndex => $fileLineItem){
			if (preg_match("/^( +)(.+)$/", $fileLineItem, $match) == FALSE){
				continue;
			}
			$fileLine[$fileLineIndex] = str_repeat(' ', strlen($match[1]) / 2).$match[2];
		}
		$fileText = implode("\n", $fileLine);

		// write to file

		return file_put_contents($this->_filePath, $fileText);
	}
}
