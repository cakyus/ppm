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

class Console {

  public function exec($command){

    $fileOutput = tempnam(sys_get_temp_dir(), 'php');
    $fileError = tempnam(sys_get_temp_dir(), 'php');

    $command = "$command >$fileOutput 2>$fileError";

    if (getenv('PHP_TRACE')) {
      fwrite(STDERR, "> $command\n");
    }

    passthru($command, $exitCode);

    if ($exitCode != 0){

      if (empty(getenv('PHP_TRACE'))) {
        fwrite(STDERR, "> $command\n");
      }

      if (filesize($fileOutput)){
        fwrite(STDERR, "-- STDOUT --\n");
        fwrite(STDERR, file_get_contents($fileOutput)."\n");
      }

      if (filesize($fileError)){
        fwrite(STDERR, "-- STDERR --\n");
        fwrite(STDERR, file_get_contents($fileError)."\n");
      }

      unlink($fileOutput);
      unlink($fileError);

      throw new \Exception("Command return non zero exit code");
    }

    unlink($fileOutput);
    unlink($fileError);
  }

  public function text($command){
    if (getenv('PHP_TRACE')) {
      fwrite(STDERR, "> $command\n");
    }
    exec($command, $outputLines, $exitCode);
    if ($exitCode != 0){
      if (empty(getenv('PHP_TRACE'))) {
        fwrite(STDERR, "> $command\n");
      }
      throw new \Exception("Command return non zero exit code");
    }

    return implode("\n", $outputLines);
  }

  public function line($command){
    if (getenv('PHP_TRACE')) {
      fwrite(STDERR, "> $command\n");
    }
    exec($command, $outputLines, $exitCode);
    if ($exitCode != 0){
      if (empty(getenv('PHP_TRACE'))) {
        fwrite(STDERR, "> $command\n");
      }
      throw new \Exception("Command return non zero exit code");
    }
    return $outputLines;
  }
}
