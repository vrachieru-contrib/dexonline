<?php

class OS {
  static function errorAndExit($msg, $exitCode = 1) {
    Log::error("ERROR: $msg");
    exit($exitCode);
  }

  static function executeAndAssert($command) {
    self::executeAndAssertDebug($command, false);
  }

  static function executeAndAssertDebug($command, $debug) {
    $exit_code = 0;
    $output = null;
    Log::info("Executing $command");
    exec($command, $output, $exit_code);
    if ($exit_code || $debug) {
      Log::debug('Output: ' . implode("\n", $output));
    }
    if ($exit_code) {
      self::errorAndExit("Failed command: $command (code $exit_code)");
    }
  }

  static function executeAndReturnOutput($command) {
    $exit_code = 0;
    $output = null;
    exec($command, $output, $exit_code);
    if ($exit_code) {
      print("ERROR: Failed command: $command (code $exit_code)\n");
      var_dump($output);
      exit;
    }
    return $output;
  }

  /** Checks if the directory specified in $path is empty */
  static function isDirEmpty($path) {
    $files = scandir($path);
    return count($files) == 2;
  }
}

?>
