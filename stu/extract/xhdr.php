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


require_once('robot.inc.php');
require_once('log.inc.php');


// Processing functions

$narg = count($argv);
if ($narg != 2) {
  error_log("Usage: xhdr <inlog>\n");
  exit(1);
}

$USER = getenv('CQPUSER');
$PASS = getenv('CQPPASS');


// Open the log file
$LOG = fopen($argv[1], "r");
if (!$LOG) {
  error_log("Can't open input file - $argv[1]\n");
  exit(1);
}

$CQPF = CabCrack("stu@ridgelift.com", $argv[1], _xhdr_getlog());

if (!$CQPF) {
  error_log("Failed processing Cabrillo log\n");
  exit(1);
}


// If we get this far, we should have all the information we can extract
// so create a record for loading the DB.

unset($CQPF['ASSISTED']);
unset($CQPF['CEXP']);
unset($CQPF['MOBILE']);
unset($CQPF['SCHOOL']);

try {
  $logt = new CQPACE_LOG_TABLE($USER, $PASS);
  $logt->log_row_create($CQPF);
  print("Inserted : " . $CQPF[':callsign'] . "\n");
} catch (Exception $e) {
  print ("Error on " . $CQPF[':callsign'] . "\n");
  print_r($CQPF);
  print "\n";
  print $e->getMessage() . "\n\n";
}

?>
