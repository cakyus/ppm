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

class Config {

	protected $file;
	public $data;

	public function __construct(){}

	public function load($file){

		$this->file = $file;

		if (is_file($file) == false){
			return false;
		}


		$text = file_get_contents($file);
		$data = json_decode($text);

		if (json_last_error()){
			\Pdr\Ppm\Logger::error("JSON parse error on file $file. ".json_last_error_msg());
		}

		$this->data = $data;

		return true;
	}

	public function open(\Pdr\Ppm\Package $package){
		$file = $package->getPath().'/composer.json';
		return $this->load($file);
	}

	public function getData(){
		return $this->data;
	}

	public function save(){
		$text = json_encode($this->data, JSON_PRETTY_PRINT |  JSON_UNESCAPED_SLASHES);
		file_put_contents($this->file, $text);
	}
}
