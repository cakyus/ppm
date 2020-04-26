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

class ErrorHandler {

	public function execute(){
		set_error_handler(array('\\Pdr\\Ppm\\Cli\\ErrorHandler', 'writeError'));
	}

	public static function writeError($number, $message, $file, $line){

		if (!(error_reporting() & $number)) {
			// This error code is not included in error_reporting, so let it fall
			// through to the standard PHP error handler
			return false;
		}

		$file = str_replace(FCPATH, '', $file);
		$file = ltrim($file, '/');

		$location = $file.':'.$line;
		$debug = debug_backtrace();
		if (isset($debug[2]['class']) && isset($debug[2]['function']) && isset($debug[1]['line'])){
			$location = $debug[2]['class'].'.'.$debug[2]['function'].':'.$debug[1]['line'];
		}

		if ($number == E_USER_ERROR){
			fwrite(STDERR, date('H:i:s').' ERROR '.$message.' '.$location."\n");
		} elseif ($number == E_USER_WARNING){
			fwrite(STDERR, date('H:i:s').' WARNING '.$message.' '.$location."\n");
		} elseif ($number == E_USER_NOTICE){
			fwrite(STDERR, date('H:i:s').' NOTICE '.$message.' '.$location."\n");
		}

		// Don't execute PHP internal error handler
		return true;
	}
}
