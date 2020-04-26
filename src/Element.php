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

class Element implements \Iterator {

	protected $_attributeNames;
	protected $_attributeValues;
	protected $_position;

	public function __construct(){
		$this->_attributeNames = array();
		$this->_attributeValues = array();
		$this->_position = 0;
	}

	public function loadObject($object){
		$this->__construct();
		foreach ($object as $attributeName => $attributeValue){
			$this->_attributeNames[] = $attributeName;
			$this->_attributeValues[] = $attributeValue;
		}
	}

	public function rewind(){
		$this->_position = 0;
	}

	public function current(){
		return $this->_attributeValues[$this->_position];
	}

	public function key(){
		return $this->_attributeNames[$this->_position];
	}

	public function next(){
		$this->_position++;
	}

	public function valid(){
		return isset($this->_attributeValues[$this->_position]);
	}
}

