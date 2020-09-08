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

class ExceptionHandler {

	public static function register() {
		set_exception_handler(array('Pdr\Ppm\Cli\ExceptionHandler', 'handleException'));
	}

	public static function handleException($exception) {

		$errstr = $exception->getMessage();
		$errno = $exception->getCode();
		$errfile = $exception->getFile();
		$errline = $exception->getLine();
		$errtrace = $exception->getTraceAsString();

		$strfile = str_replace(FCPATH, '', $errfile);
		$strfile = ltrim($strfile, '/');
		$strpath = $strfile.':'.$errline;

		fwrite(STDERR, date('H:i:s').' EXCEPTION '.$errstr.' '.$strpath."\n");
		exit(1);
	}
}
