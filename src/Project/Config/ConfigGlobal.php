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

namespace Pdr\Ppm\Project\Config;

class ConfigGlobal extends \Pdr\Ppm\Project\Config\ConfigFile {

  public function __construct() {
    parent::__construct();
  }

  /**
   * Validate configuration
   **/

  public function loadFile($filePath) {

    parent::loadFile($filePath);

    if (empty($this->repositories)){
      $this->repositories = array();
    }

    return TRUE;
  }


  public function setPackage($packageName, $packageVersion, $packageUrl) {

    foreach ($this->repositories as $repository){

      if (  empty($repository->type) == FALSE && $repository->type == 'package'
        &&  empty($repository->package->name) == FALSE && $repository->package->name == $packageName
        &&  empty($repository->package->version) == FALSE && $repository->package->version == $packageVersion
        ){
        if (empty($repository->package->source)){
          $repository->package->source = new \stdClass;
        }
        if (empty($repository->package->source->type)){
          $repository->package->source->type = 'cvs';
        }
        $repository->package->source->url = $packageUrl;
        return TRUE;
      }
    }

    $repository = new \stdClass;
    $repository->type = 'package';
    $repository->package = new \stdClass;
    $repository->package->name = $packageName;
    $repository->package->version = $packageVersion;
    $repository->package->source = new \stdClass;
    $repository->package->source->type = 'cvs';
    $repository->package->source->url = $packageUrl;

    // Indirect modification of overloaded property

    $repositories = $this->repositories;
    $repositories[] = $repository;
    $this->repositories = $repositories;

    return TRUE;
  }
}
