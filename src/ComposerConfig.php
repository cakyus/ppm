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
 * Merge ComposerGlobalConfig and ComposerLocalConfig
 **/

class ComposerConfig extends \Pdr\Ppm\Element {

	protected $_filePath;

	public function __construct(){
		parent::__construct();
	}

	public function open($filePath=NULL){

		if (is_null($filePath)){
			foreach (array(
				$_SERVER['HOME'].'/.config/composer/config.json'
				) as $configPath){
				trigger_error("Check $configPath", E_USER_NOTICE);
				if (is_file($configPath)){
					$filePath = $configPath;
					break;
				}
			}
		}

		if (is_null($filePath)){
			throw new \Exception("filePath is not found");
		}

		$fileText = file_get_contents($filePath);
		$fileObject = json_decode($fileText, TRUE);
		$this->_filePath = $filePath;

		return parent::loadObject($fileObject);
	}
}
