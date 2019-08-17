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

class Remote {

	public $name;
	public $url;

	protected $repository;

	public function __construct($repository) {
		$this->repository = $repository;
	}

	public function getBranches() {

		$command = $this->repository->getGitCommand()
			.' for-each-ref --format "%(refname:short)" refs/remotes/'.$this->name
			;
		$branchNames = \Pdr\Ppm\Console::line($command);

		$branches = array();
		foreach ($branchNames as $branchName){
			$branch = new \Pdr\Ppm\Branch;
			$branch->name = basename($branchName);
			$branch->open($this->repository, $branchName);
			$branches[] = $branch;
		}

		return $branches;
	}

	public function getBranch($branchName) {

	}

	public function getTags() {

	}

	public function getTag($tagName) {

	}

	/**
	 * @return \Pdr\Ppm\Commit|boolean
	 **/

	public function getCommit($commitReference) {

		$gitDir = $this->repository->getGitDir();

		$fileFound = false;
		foreach ( array(
			  $gitDir.'/refs/remotes/'.$this->name.'/'.$commitReference
			, $gitDir.'/refs/tags/'.$commitReference
			) as $file ) {
			if (is_file($file) == false) {
				continue;
			}
			$fileFound = true;
			break;
		}

		if ($fileFound == false){
			return false;
		}

		$commitHash = file_get_contents($file);

		$commit = new \Pdr\Ppm\Commit;
		$commit->open($this->repository, $commitHash);
		return $commit;
	}

	/**
	 * @return string|boolean
	 **/

	public function getCommitHash($commitReference) {

		if ( ( $commit = $this->getCommit($commitReference) ) !== false ){
			return $commit->commitHash;
		}

		return false;
	}

	public function fetch($refspec) {

		$command  = $this->repository->getGitCommand();
		$command .= ' fetch --quiet --depth=1 ';
		if (empty($this->name) == false){
			$command .= ' '.$this->name;
		} else {
			$command .= ' '.$this->url;
		}
		$command .= ' '.$refspec;

		\Pdr\Ppm\Console::exec($command);
	}
}
