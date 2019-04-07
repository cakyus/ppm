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

class Logger {

	public static function debug($message){
		if (error_reporting() & E_USER_NOTICE){
			fwrite(STDERR, date('Y-m-d+H:i:s')." DEBUG $message\n");
		}
	}

	public static function warn($message){
		if (error_reporting() & E_USER_WARNING){
			fwrite(STDERR, date('Y-m-d+H:i:s')." WARN  $message\n");
		}
	}

	public static function error($message){
		fwrite(STDERR, date('Y-m-d+H:i:s')." ERROR $message\n");
		exit(1);
	}
}
