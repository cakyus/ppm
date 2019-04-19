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

	public function loadFile($file){

		if (is_file($file) == false){
			throw new \Exception("File is not found");
		}

		$text = file_get_contents($file);
		$data = json_decode($text);

		$this->data = $data;
		$this->file = $file;
	}

	public function open(\Pdr\Ppm\Package $package){

		$file = $package->getPath().'/composer.json';
		if (is_file($file) == false){
			throw new \Exception("composer.json is not found");
		}

		$text = file_get_contents($file);
		$data = json_decode($text);

		$this->data = $data;
		$this->file = $file;
	}

	public function getData(){
		return $this->data;
	}

	public function save(){

	}
}

