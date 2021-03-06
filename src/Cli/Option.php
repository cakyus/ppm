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

namespace Pdr\Ppm\Cli;

class Option {

	public function getOption($optionName){
		$optionValue = NULL;
		for ($i = 2; $i < $_SERVER['argc']; $i++){
			$optionText = $_SERVER['argv'][$i];
			if (preg_match("/^\-([^=]+)(=(.+))?$/", $optionText, $match)){
				if ($match[1] != $optionName){
					continue;
				}
				$optionValue = TRUE;
				if (isset($match[3])){
					$optionValue = $match[3];
				}
			}
		}
		return $optionValue;
	}

	public function getCommand($index){
		$commandIndex = 0;
		for ($i = 2; $i < $_SERVER['argc']; $i++){
			if (substr($_SERVER['argv'][$i],0,1) == '-'){
				continue;
			}
			if ($commandIndex == $index){
				return $_SERVER['argv'][$i];
			}
			$commandIndex++;
		}
		return NULL;
	}

	public function getCommandCount(){
		$commandCount = 0;
		for ($i = 2; $i < $_SERVER['argc']; $i++){
			if (substr($_SERVER['argv'][$i],0,1) != '-'){
				$commandCount++;
			}
		}
		return $commandCount;
	}
}
