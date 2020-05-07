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

	public function __construct(){
		$this->options = array();
		$this->options['file'] = '--global';
	}

	public function openGlobal(){
		$this->options['file'] = '--global';
	}

	public function openLocal() {
		$project = new \Pdr\Ppm\Project;
		$filePath = $project->getPath().'/gitconfig';
		$this->options['file'] = '--file '.escapeshellarg($filePath);
	}

	public function get($configName){

		$console = new \Pdr\Ppm\Console2;

		$commandText = $this->getCommandText().' --list --name-only';

		foreach ($console->line($commandText) as $outputLine){
			if ($outputLine == $configName){
				$commandText = $this->getCommandText().' --get '.escapeshellarg($configName);
				return $console->text($commandText);
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
		} else {
			$commandText = $this->getCommandText()
				.' --add'
				.' '.escapeshellarg($configName)
				.' '.escapeshellarg($configValue)
				;
			$console->exec($commandText);
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

