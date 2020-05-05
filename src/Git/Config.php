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

	protected $filePath;

	public function open($filePath) {
		$this->filePath = $filePath;
	}

	public function set($configName, $configValue){

		$console = new \Pdr\Ppm\Console2;

		if (is_null($this->filePath)){
			$optionFile = '--global';
		} else {
			$optionFile = '--file '.escapeshellarg($this->filePath);
		}

		$gitCommand = 'git config '.$optionFile;

		$commandText = $gitCommand
			.' --replace-all '.escapeshellarg($configName).' '.escapeshellarg($configValue)
			;

		$console->exec($commandText);
	}
}

