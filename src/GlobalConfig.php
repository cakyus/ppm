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

class GlobalConfig {

	protected static $_attributes;

	public function __construct(){

		if (is_null(self::$_attributes) == TRUE){
			$filePath = $_SERVER['HOME'].'/.config/ppm/config.json';
			if (is_file($filePath)){
				$fileText = file_get_contents($filePath);
				$fileData = json_decode($fileText);
				if ($fileData){
					$attributes = array();
					foreach ($fileData as $attributeName => $attributeValue){
						$attributes[$attributeName] = $attributeValue;
					}
					self::$_attributes = $attributes;
					return TRUE;
				}
			}
		} else {
			return TRUE;
		}

		self::$_attributes = array();
		return FALSE;
	}

	public function getRepositoryUrl($packageName) {

		if (	isset($this->repositories)
			&&	is_object($this->repositories)
			){
			if (array_key_exists($packageName, $this->repositories)) {
				$repository = $this->repositories->$packageName;
				if (isset($repository->url)){
					return $repository->url;
				}
				return false;
			}
		}

		return false;
	}

	public function __get($attributeName) {
		if (array_key_exists($attributeName, self::$_attributes)){
			return self::$_attributes[$attributeName];
		}
		return NULL;
	}

	public function __set($attributeName, $attributeValue) {
		self::$_attributes[$attributeName] = $attributeValue;
	}

	public function save() {

		$fileDir = $_SERVER['HOME'].'/.config';
		if (is_dir($fileDir) == FALSE){
			mkdir($fileDir);
		}

		$fileDir = $fileDir.'/.ppm';
		if (is_dir($fileDir) == FALSE){
			mkdir($fileDir);
		}

		$filePath = $fileDir.'/config.json';
		$fileText = json_encode(self::$_attributes);

		file_put_contents($filePath, $fileText);
	}

}
