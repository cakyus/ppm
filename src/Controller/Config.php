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

namespace Pdr\Ppm\Controller;

class Config extends \Pdr\Ppm\Cli\Controller {

  public function __construct() {
    parent::__construct();
  }

  /**
   * Usage: ppm config [option..] [[name] value]
   * Options:
   *  -g, -global       set scope to global configuration
   *  -l, -local        set scope to local project configuration
   *  -f, -file=<file>  set scope to <file>
   **/

  public function commandIndex(){
    $this->writeGlobal();
  }

  /**
   * { "repositories": [
   *     { "type": "package",
   *       "package": {
   *         "name": "pdr/ppm",
   *         "version": "master",
   *         "source": {
   *           "type": "cvs",
   *           "url": "https://github.com/cakyus/ppm.git"
   * }}}]}
   *
   * repos.pdr/ppm.master=https://github.com/cakyus/ppm.git
   **/

  protected function writeGlobal() {

    $project = new \Pdr\Ppm\Project;
    $config = $project->config('global');
    $object = $config->saveObject();

    if (empty($object->repositories) == FALSE){
      foreach ($object->repositories as $repository){
        if (  empty($repository->package->name) == FALSE
          &&  empty($repository->package->version) == FALSE
          &&  empty($repository->package->source->url) == FALSE
          ){
          echo 'repos'
            .'.'.$repository->package->name
            .'.'.$repository->package->version
            .'='.$repository->package->source->url
            ."\n";
        }
      }
    }
  }
}
