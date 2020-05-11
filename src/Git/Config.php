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

namespace Pdr\Ppm\Git;

class Config {

	protected $options;
	protected static $_configNames;
	protected static $_configValues;

	public function __construct(){
		$this->options = array();
		$this->options['file'] = '--global';
	}

	public function openGlobal(){
		$this->options['file'] = '--global';
		if (is_null(self::$_configNames)){
			$console = new \Pdr\Ppm\Console2;
			$commandText = $this->getCommandText().' --list --name-only';
			$configNames = $console->line($commandText);
			self::$_configNames = $configNames;
			self::$_configValues = array();
		}
	}

	public function openLocal() {
		$project = new \Pdr\Ppm\Project;
		$filePath = $project->getPath().'/gitconfig';
		$this->options['file'] = '--file '.escapeshellarg($filePath);
		if (is_null(self::$_configNames)){
			$console = new \Pdr\Ppm\Console2;
			$commandText = $this->getCommandText().' --list --name-only';
			$configNames = $console->line($commandText);
			self::$_configNames = $configNames;
			self::$_configValues = array();
		}
	}

	public function getNames() {
		return self::$_configNames;
	}

	public function get($configName){

		$console = new \Pdr\Ppm\Console2;

		if (isset(self::$_configValues[$configName])){
			return self::$_configValues[$configName];
		}

		$commandText = $this->getCommandText().' --list --name-only';

		foreach (self::$_configNames as $outputLine){
			if ($outputLine == $configName){
				$commandText = $this->getCommandText().' --get '.escapeshellarg($configName);
				$configValue = $console->text($commandText);
				self::$_configValues[$outputLine] = $configValue;
				return $configValue;
			}
		}

		return NULL;
	}

	public function set($configName, $configValue){

		$console = new \Pdr\Ppm\Console2;

		$value = $this->get($configName);

		if (is_null($value) == FALSE){
			if ($value == $configValue){
				return TRUE;
			}
			$commandText = $this->getCommandText()
				.' '.escapeshellarg($configName)
				.' '.escapeshellarg($configValue)
				;
			$console->exec($commandText);
			self::$_configValues[$configName] = $configValue;
		} else {
			$commandText = $this->getCommandText()
				.' --add'
				.' '.escapeshellarg($configName)
				.' '.escapeshellarg($configValue)
				;
			$console->exec($commandText);
			self::$_configValues[$configName] = $configValue;
		}

		return TRUE;
	}

	public function del($configName){

		$console = new \Pdr\Ppm\Console2;

		$value = $this->get($configName);

		if (is_null($value) == TRUE){
			return TRUE;
		}

		$commandText = $this->getCommandText()
			.' --unset '.escapeshellarg($configName)
			;
		$console->exec($commandText);

		unset(self::$_configNames[$configName]);

		return TRUE;
	}

	/**
	 * Get all values for a configuration
	 *
	 * @return array
	 **/

	public function listGet($configName){

	}

	/**
	 * Set a value in a configuration
	 *
	 * @return boolean
	 **/

	public function listSet($configName, $configValue){

	}

	/**
	 * Delete a value in a configuration
	 *
	 * @return boolean
	 **/

	public function listDel($configName, $configValue){

	}

	public function getCommandText(){
		return 'git config '.implode(' ', $this->options);
	}
}
