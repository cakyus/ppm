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

		$console = new \Pdr\Ppm\Console;

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

		$console = new \Pdr\Ppm\Console;

		$command  = $this->getGitCommand();
		$command .= ' remote add'
			.' '.$remote->name
			.' '.$remote->url
			;

		$console->exec($command);
	}

	public function checkout($branchName, $commitHash) {

		$console = new \Pdr\Ppm\Console;

		$command  = $this->getGitCommand();
		$command .= ' clean --force -x';
		$console->exec($command);

		if ( ( $branch = $this->getCurrentBranch() ) == FALSE){
			// no branch -> create branch "master" that point to required commit hash
			$command  = $this->getGitCommand();
			$command .= ' branch '.$branchName.' '.$commitHash;
			$console->exec($command);
		} elseif ($branch->name != $branchName){
			// branchName and current branchName are different
			$command  = $this->getGitCommand();
			$command .= ' checkout '.$branchName;
			$console->exec($command);
		} else {
			// branchName and current branchName are the same -> do nothing
		}

		$command  = $this->getGitCommand();
		$command .= ' reset --hard '.$commitHash;
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

	public function addAllChanges() {

		$console = new \Pdr\Ppm\Console;

		$command  = $this->getGitCommand();
		$command .= ' add --all';

		$console->exec($command);
	}

	public function commit($commitMessage) {

		$console = new \Pdr\Ppm\Console;

		// -- check committer info -----------------------------------------

		if (is_file($_SERVER['HOME'].'/.gitconfig') == FALSE){
			// set committer email
			$command = 'git config --global user.email bot@127.0.0.1';
			$console->exec($command);
			// set committer name
			$command = 'git config --global user.name bot';
			$console->exec($command);
		}

		// -- commit -------------------------------------------------------

		$command  = $this->getGitCommand();
		$command .= ' commit --allow-empty -m '.escapeshellarg($commitMessage);

		$console->exec($command);
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

			$remotes[$remoteName] = $remote;
		}

		return $remotes;
	}

	public function getRemote($remoteName) {

		$remotes = $this->getRemotes();
		if (array_key_exists($remoteName, $remotes) == TRUE){
			return $remotes[$remoteName];
		}

		return NULL;
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
			$branch->open($this, $branchName);
			$branches[] = $branch;
		}

		return $branches;
	}

	/**
	 * Return active branch name
	 **/

	public function getCurrentBranch() {

		$console = new \Pdr\Ppm\Console;

		$command  = $this->getGitCommand();
		$command .= ' branch';
		$branches = $console->line($command);

		if (count($branches) == 0){
			// no branch defined , ie. $GIT_DIR/refs/heads is empty
			return FALSE;
		}

		$command  = $this->getGitCommand();
		$command .= ' rev-parse --abbrev-ref HEAD';
		$branchName = $console->text($command);

		$branch = new \Pdr\Ppm\Branch;
		$branch->open($this, $branchName);

		return $branch;
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
