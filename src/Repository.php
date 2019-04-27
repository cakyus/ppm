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

class Repository {

	protected $workTree;
	protected $gitDir;

	public function open($gitDir, $workTree=null){

		if (is_dir($gitDir) == false){
			throw new \Exception("gitDir is not found");
		}

		$this->gitDir = $gitDir;
		$this->workTree = $workTree;
	}

	public function getGitCommand() {
		$command = 'git';
		if (empty($this->workTree) == false){
			$command .= ' '.escapeshellarg('--work-tree='.$this->workTree);
		}
		$command .= ' '.escapeshellarg('--git-dir='.$this->gitDir);
		return $command;
	}

	public function getRemotes() {

		$remotes = array();
		$command  = $this->getGitCommand();
		$command .= ' remote --verbose';
		foreach (\Pdr\Ppm\Console::line($command) as $line){

			$match = preg_split("/\s+/", $line);

			$remoteName = $match[0];
			$remoteUrl = $match[1];

			$remote = new \Pdr\Ppm\Remote($this);
			$remote->name = $remoteName;
			$remote->url = $remoteUrl;

			$remotes[] = $remote;
		}

		return $remotes;
	}

	/**
	 * @return \Pdr\Ppm\Commit|boolean
	 **/

	public function getCommit($commitReference) {

		$commit = new \Pdr\Ppm\Commit;

		if ($commit->open($this, $commitReference)){
			return $commit;
		}

		return false;
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


	public function getRemote($remoteName) {
		foreach ($this->getRemotes() as $remote){
			if ($remote->name == $remoteName){
				return $remote;
			}
		}
		return false;
	}

	public function getGitDir() {
		return $this->gitDir;
	}
}

