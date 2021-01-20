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

namespace Pdr\Ppm\Common;

class Attribute {

	protected $_attributes;

	public function __construct() {
		$this->_attributes = array();
	}

	public function loadObject($object) {
		$this->_attributes = array();
		foreach ($object as $attributeName => $attributeValue){
			$this->_attributes[$attributeName] = $attributeValue;
		}
		return TRUE;
	}

	public function saveObject() {
		$object = new \stdClass;
		foreach ($this->_attributes as $attributeName => $attributeValue){
			$object->$attributeName = $attributeValue;
		}
		return $object;
	}

	public function get($attributeName) {
		return $this->__get($attributeName);
	}

	public function set($attributeName, $attributeValue) {
		return $this->__set($attributeName, $attributeValue);
	}

	public function __isset($attributeName) {
		return array_key_exists($attributeName, $this->_attributes);
	}

	public function __unset($attributeName) {
		unset($this->_attributes[$attributeName]);
	}

	public function __get($attributeName) {
		if (array_key_exists($attributeName, $this->_attributes) == TRUE){
			return $this->_attributes[$attributeName];
		}
		return NULL;
	}

	public function __set($attributeName, $attributeValue) {
		$this->_attributes[$attributeName] = $attributeValue;
	}
}
