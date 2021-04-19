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

	public function create($gitDir, $workTree=NULL) {

		$console = new \Pdr\Ppm\Console2;

		$command  = 'git';
		$command .= ' init';

		if (is_null($workTree) == TRUE){
			$command .= ' --bare '.escapeshellarg($gitDir);
		} elseif (dirname($gitDir) != $workTree) {
			$command .= ' --separate-git-dir '.escapeshellarg($gitDir);
			$command .= ' '.escapeshellarg($workTree);
		} else {
			$command .= ' '.escapeshellarg($workTree);
		}

		$console->exec($command);

		if (is_null($workTree) == TRUE){
			$this->gitDir = $gitDir;
		} else {
			$this->gitDir = $gitDir;
			$this->workTree = $workTree;
		}
	}

	public function addRemote($remote) {

		$console = new \Pdr\Ppm\Console2;

		$command  = $this->getGitCommand();
		$command .= ' remote add'
			.' '.$remote->name
			.' '.$remote->url
			;

		$console->exec($command);
	}
	public function open($gitDir, $workTree=NULL){

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

	/**
	 * @return array Collection of \Pdr\Ppm\Remote
	 **/

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

	public function hasChanges($path=NULL) {

		$command  = $this->getGitCommand();
		$command .= ' status --short';
		$text = \Pdr\Ppm\Console::text($command);

		if (empty($text)){
			return false; // No local changes
		} else {
			return true; // Has local changes
		}
	}

	public function getBranches() {

		$command = $this->getGitCommand()
			.' for-each-ref --format "%(refname:short)" refs/heads'
			;
		$branchNames = \Pdr\Ppm\Console::line($command);

		$branches = array();
		foreach ($branchNames as $branchName){
			$branch = new \Pdr\Ppm\Branch;
			$branch->name = $branchName;
			$branch->open($this, $branchName);
			$branches[] = $branch;
		}

		return $branches;
	}

	/**
	 * Return active branch name
	 **/

	public function getCurrentBranch() {

	}

	/**
	 * Get commit object of HEAD
	 * @return string|boolean
	 **/

	public function getCurrentCommit() {

		$commandText  = $this->getGitCommand();
		$commandText .= ' rev-parse HEAD';
		$commitHash = \Pdr\Ppm\Console::text($commandText);
		return $this->getCommit($commitHash);
	}
}
