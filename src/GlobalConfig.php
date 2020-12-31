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

	protected $_filePath;
	protected $_attributes;

	public function __construct(){

		$this->_filePath = $_SERVER['HOME'].'/.config/ppm/config.json';
		$this->_attributes = array();

		if (is_file($this->_filePath) == FALSE){

			fwrite(STDERR, "WARNING Global configuration file is not found.\n");

			// Create initial configuration
			$this->repositories = array();
			$this->save();
			return FALSE;
		}

		$attributes = json_decode(file_get_contents($this->_filePath));

		if (json_last_error() > 0){
			throw new \Exception("JSON parse error.");
		}

		$this->_attributes = $attributes;
		return TRUE;
	}

	public function getRepositoryUrl($packageName) {

		if (	isset($this->data->repositories)
			&&	is_object($this->data->repositories)
			){
			if (array_key_exists($packageName, $this->data->repositories)) {
				$repository = $this->data->repositories->$packageName;
				if (isset($repository->url)){
					return $repository->url;
				}
				return false;
			}
		}

		return false;
	}

	public function __isset($attributeName){
		return array_key_exists($attributeName, $this->_attributes);
	}

	public function __get($attributeName){
		if (array_key_exists($attributeName, $this->_attributes) == TRUE){
			return $this->_attributes[$attributeName];
		}
		return NULL;
	}

	public function __set($attributeName, $attributeValue){
		$this->_attributes[$attributeName] = $attributeValue;
	}

	public function save(){
		return file_put_contents(
			  $this->_filePath
			, json_encode($this->_attributes)
			);
	}
}
