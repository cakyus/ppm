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

	protected $optionFile;

	public function __construct(){
		$this->optionFile = '--global';
	}

	public function open($filePath) {
		$this->optionFile = '--file '.escapeshellarg($filePath);
	}

	public function set($configName, $configValue){

		$console = new \Pdr\Ppm\Console2;

		$gitCommand = 'git config '.$this->optionFile;

		$commandText = $gitCommand
			.' --replace-all '.escapeshellarg($configName).' '.escapeshellarg($configValue)
			;

		$console->exec($commandText);
	}

	public function del($configName){

		$console = new \Pdr\Ppm\Console2;

		$gitCommand = 'git config '.$this->optionFile;
		$commandText = $gitCommand.' --unset-all '.escapeshellarg($configName);

		$console->exec($commandText);
	}
}

