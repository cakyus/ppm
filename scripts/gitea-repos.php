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

// List all accessible repositories

$repos = array();
$config = array();

include($_SERVER['HOME'].'/.config.php');

$curl = 'curl'
	.' --silent'
	.' --insecure'
	;

$command = $curl
	.' -X GET '
	.' -H "Accept: application/json"'
	.' '.$config['GITEA_LOCATION'].'/user/repos?access_token='.$config['GITEA_PASSWORD']
	;

$line = array();
exec($command, $line);

$text = $line[0];
$data = json_decode($text, TRUE);
foreach ($data as $item) {
	$repos[] = $item['clone_url'];
}

foreach ($repos as $repo) {
	echo $repo."\n";
}

