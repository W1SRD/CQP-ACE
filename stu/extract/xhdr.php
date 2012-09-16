#!/usr/bin/php
<?php

// xhdr <inlog>
//
// Process a log file to extract required CQP contest information
// from the Cabrillo headers at the top of the log.
//
// The required information is output as a CSV record to stdout.
// Debug and error information is written to stderr
//


require_once('xhdr.inc.php');

// Utility functions

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

// Processing functions

$narg = count($argv);
if ($narg != 2) {
  error_log("Usage: xhdr <inlog>\n");
  exit(1);
}


// Open the log file
$LOG = fopen($argv[1], "r");
if (!$LOG) {
  error_log("Can't open input file - $argv[1]\n");
  exit(1);
}

$CQPF = XHDRcrack();

if (!$CQPF) {
  error_log("Failed processing Cabrillo log\n");
  exit(1);
}

$CQPF['LOGNAME'] = preg_replace("/.*\//", '', $argv[1]);


// If we get this far, we should have all the information we can extract
// so create a record for loading the DB.

print(implode(',',$CQPF) . "\n");
print_r($CQPF);
exit(0);



?>
