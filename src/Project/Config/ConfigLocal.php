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

/**
 * @see docs/config.local.json
 * @link https://getcomposer.org/doc/04-schema.md
 **/

class ConfigLocal extends \Pdr\Ppm\Project\Config\ConfigFile {

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Validate configuration
	 **/

	public function loadFile($filePath) {

		if (parent::loadFile($filePath) === FALSE){
			throw new \Exception("Load local configuration failed");
		}

		if (empty($this->name)){
			throw new \Exception("Project name is not defined");
		}

		// Version should determited by version control.
		// Specifying the version yourself will most likely end up
		// creating problems at some point due to human error.

		if (empty($this->version) == FALSE){
			throw new \Exception("Project version should not defined");
		}

		if (empty($this->license)){
			throw new \Exception("Project license is not defined");
		}

		$this->setDefaultValues();

		return TRUE;
	}

	protected function setDefaultValues() {
		foreach (array(
			'require'
			, 'require-dev'
			, 'autoload'
			, 'autoload-dev'
			) as $attributeName){
			if (empty($this->$attributeName)){
				$this->$attributeName = new \stdClass;
			}
		}
	}

	public function getPackages() {

	}

	public function setPackage($packageName, $packageReference) {

		$option = new \Pdr\Ppm\Cli\Option;
		if ($option->getOption('dev')){
			$attributeName = 'require-dev';
		} else {
			$attributeName = 'require';
		}

		$this->$attributeName->$packageName = $packageReference;
	}

	public function saveObject() {

		$object = parent::saveObject();

		// remove empty attributes

		foreach (array(
			'require'
			, 'require-dev'
			, 'autoload'
			, 'autoload-dev'
			) as $attributeName){
			$attributeItemCount = 0;
			foreach ($object->$attributeName as $attributeItemValue){
				$attributeItemCount++;
			}
			if ($attributeItemCount == 0){
				unset($object->$attributeName);
			}
		}

		return $object;
	}
}
