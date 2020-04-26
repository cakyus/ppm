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

namespace Pdr\Ppm\View;

class Config {

	protected $_object;

	public function open($object) {
		$this->_object = $object;
	}

	public function write(){
		$this->writeObject(NULL, $this->_object);
	}

	protected function writeObject($namespace, $object){
		foreach ($object as $attributeName => $attributeValue){
			$attributeType = gettype($attributeValue);
			if ($attributeType == 'array'){
				if (is_null($namespace)){
					$childNamespace = $attributeName;
				} else {
					$childNamespace = $namespace.'.'.$attributeName;
				}
				$this->writeObject($childNamespace, $attributeValue);
			} elseif ($attributeType == 'boolean') {
				if ($attributeValue){
					echo $namespace.'.'.$attributeName." true\n";
				} else {
					echo $namespace.'.'.$attributeName." false\n";
				}
			} elseif ($attributeType == 'string') {
				echo $namespace.'.'.$attributeName.' '.$attributeValue."\n";
			} else {
				throw new \Exception("Unsupported attributeType: $attributeType");
			}
		}
	}
}

