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

class Console {

	public function exec($command){
		$option = new \Pdr\Ppm\Cli\Option;
		if ($option->getOption('v')){
			fwrite(STDERR, "> $command\n");
		}
		passthru($command, $exitCode);
		if ($exitCode != 0){
			throw new \Exception("Command return non zero exit code");
		}
	}

	public function text($command){
		$option = new \Pdr\Ppm\Cli\Option;
		if ($option->getOption('v')){
			fwrite(STDERR, "> $command\n");
		}
		exec($command, $outputLines, $exitCode);
		if ($exitCode != 0){
			throw new \Exception("Command return non zero exit code");
		}

		return implode("\n", $outputLines);
	}

	public function line($command){
		$option = new \Pdr\Ppm\Cli\Option;
		if ($option->getOption('v')){
			fwrite(STDERR, "> $command\n");
		}
		exec($command, $outputLines, $exitCode);
		if ($exitCode != 0){
			throw new \Exception("Command return non zero exit code");
		}
		return $outputLines;
	}
}
