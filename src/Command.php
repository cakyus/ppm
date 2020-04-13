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

class Command {

	protected $version;

	public function __construct() {}

	public function execute() {

		if (PHP_SAPI !== 'cli'){
			throw new \Exception("Invalid SAPI");
		}

		if ($_SERVER['argc'] == 1){
			$this->commandHelp();
			exit(0);
		}

		$arguments = $_SERVER['argv'];
		array_shift($arguments);
		$commandName = array_shift($arguments);
		$commandName = 'command'.ucfirst($commandName);

		if (method_exists($this, $commandName) == false){
			fwrite(STDERR, "ERROR: Command not exists\n");
			$this->commandHelp();
			exit(1);
		}

		call_user_func_array(array($this, $commandName), $arguments);
	}

	/**
	 * Print this information and exit
	 **/

	public function commandHelp() {
		fwrite(STDERR, file_get_contents(FCPATH.'/docs/ppm.txt'));
	}
}
