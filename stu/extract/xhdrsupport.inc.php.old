<?php

// Utility functions


function setup_xhdr_getl($log) {
}


function _xhdr_getl() {
  global $argv, $LOG;

  $l = fgets($LOG);
  if (!$l) {
    // We don't expect premature EOF - moan and exit
    error_log("$argv[1]:  Premature EOF\n");
    exit(1);
  }
  $l = preg_replace("/\r/", ' ', $l);
  return($l);
}

function _xhdr_getlog() {
  global $argv;
  return(file_get_contents($argv[2]));
}

?>

