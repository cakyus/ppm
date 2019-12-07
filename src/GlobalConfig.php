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

	public $data;

	public function __construct(){

		$filePath = null;

		if (is_file($_SERVER['HOME'].'/.composer/config.json')){
			$filePath = $_SERVER['HOME'].'/.composer/config.json';
		} elseif (is_file($_SERVER['HOME'].'/.config/composer/config.json')){
			$filePath = $_SERVER['HOME'].'/.config/composer/config.json';
		} elseif (
					isset($_SERVER['APPDATA'])
			&&	is_file($_SERVER['APPDATA'].'/Composer/config.json')
			){
			$filePath = $_SERVER['APPDATA'].'/Composer/config.json';
		}

		if (is_null($filePath)){
			\Pdr\Ppm\Logger::warn("Global composer config file is not found");
			return false;
		}

		if (($data = json_decode(file_get_contents($filePath))) == false){
			\Pdr\Ppm\Logger::warn("JSON parse fail");
			return false;
		}

		$this->data = $data;
		return true;
	}

	public function open() {
		\Pdr\Ppm\Logger::deprecated(__METHOD__);
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
}
