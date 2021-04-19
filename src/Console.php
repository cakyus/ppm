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
 * @deprecated use \Pdr\Ppm\Console2
 **/

class Console {

	public static function exec($command){

		passthru($command, $exitCode);
		if ($exitCode != 0){
			fwrite(STDERR, "> $command\n");
			throw new \Exception("Command return non zero exit code");
		}
	}

	public static function text($command){

		exec($command, $outputLines, $exitCode);
		if ($exitCode != 0){
			fwrite(STDERR, "> $command\n");
			throw new \Exception("Command return non zero exit code");
		}

		return implode("\n", $outputLines);
	}

	public static function line($command){

		exec($command, $outputLines, $exitCode);
		if ($exitCode != 0){
			fwrite(STDERR, "> $command\n");
			throw new \Exception("Command return non zero exit code");
		}
		return $outputLines;
	}
}
