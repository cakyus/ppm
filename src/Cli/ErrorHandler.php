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

	public static function register() {
		register_shutdown_function(array('Pdr\Ppm\Cli\ErrorHandler', 'handleShutdown'));
		set_error_handler(array('Pdr\Ppm\Cli\ErrorHandler', 'handleError'));
	}

	public static function handleError($errno, $errstr, $errfile, $errline) {

		$errmap = array(
			E_ERROR => 'ERROR'
			, E_WARNING => 'WARNING'
			, E_PARSE => 'PARSE'
			, E_NOTICE => 'NOTICE'
			, E_CORE_ERROR => 'CORE ERROR'
			, E_CORE_WARNING => 'CORE WARNING'
			, E_COMPILE_ERROR => 'COMPILE ERROR'
			, E_COMPILE_WARNING => 'COMPILE WARNING'
			, E_USER_ERROR => 'USER ERROR'
			, E_USER_WARNING => 'USER WARNING'
			, E_USER_NOTICE => 'USER NOTICE'
			, E_STRICT => 'STRICT'
			, E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR'
			, E_DEPRECATED => 'DEPRECATED'
			, E_USER_DEPRECATED => 'USER DEPRECATED'
		);

    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting, so let it fall
        // through to the standard PHP error handler
        return FALSE;
    }

		if (isset($_ENV['FCPATH']) == TRUE) {
			$FCPATH = $_ENV['FCPATH'];
			if ($FCPATH == '.') {
				$FCPATH = getcwd();
			}
		} else {
			$FCPATH = FCPATH;
		}

		$strfile = str_replace($FCPATH, '', $errfile);
		$strfile = ltrim($strfile, '/');
		$strpath = $strfile.':'.$errline;

		$trace = debug_backtrace();
		if (isset($trace[1]['class']) && isset($trace[1]['function'])){
			$strpath = $trace[1]['class'].'.'.$trace[1]['function'].':'.$errline;
		}

		// Exit on all error

		foreach ($errmap as $errmapno => $errmapstr){
			if ($errno == $errmapno){
				fwrite(STDERR, date('H:i:s').' '.$errmapstr.' '.$errstr.' '.$strpath."\n");
			}
		}

    // Don't execute PHP internal error handler
     return TRUE;
	}


	public static function handleShutdown() {

		// Returns an associative array describing the last error with keys
		// "type", "message", "file" and "line".

		$error = error_get_last();

		// Check if it's a core/fatal error. Otherwise, it's a normal shutdown
		if($error !== NULL && $error['type'] === E_ERROR) {

			$strfile = str_replace(FCPATH, '', $error['file']);
			$strfile = ltrim($strfile, '/');
			$strpath = $strfile.':'.$error['line'];

			fwrite(STDERR, date('H:i:s').' ERROR '.$error['message'].' '.$strpath."\n");
			exit(1);
		}
	}
}
