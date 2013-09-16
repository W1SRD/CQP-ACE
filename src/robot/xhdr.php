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

function mypd($l) {
  print ($l . "\n");
}

// Processing functions

$narg = count($argv);
if ($narg != 3) {
  error_log("Usage: xhdr <email> <inlog>\n");
  exit(1);
}

$USER = getenv('ACEUSER');
$PASS = getenv('ACEPASS');


// Open the log file
$log = file_get_contents($argv[2]);
if (!$log) {
  error_log("Can't open input file - $argv[1]\n");
  exit(1);
}

$CQPF = CabCrack($argv[1], $argv[2], $log);

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
  print_r($CQPF);

  $logt = new CQPACE_LOG_TABLE($USER, $PASS);

  // Attempt a select on the callsign to see if a
  // record exists..
  try {
    $row = $logt->log_row_select(array(':callsign' => $CQPF[':callsign']));
  } catch (Exception $e) {
    // This shouldn't happen... but just in case...
    mypd("  CQP-ACE ERROR SELECT:");
    mypd("  " . $e->getMessage());
    CQPACERenameFile($fullfname);
    return;
  }

  // Ok, we get this far, we know whether we have a record in
  // the DB or not...  in either event, insert or update based on
  // whether $row is TRUE

  try {
    if ($row) {
      // A row already exists - this is an update
      $logt->log_row_update($CQPF);
      mypd("    DB record updated for " . $CQPF[':callsign']);
    } else {
      // No record for this callsign in the DB - insert
      $logt->log_row_create($CQPF);
      mypd("    DB record created for " . $CQPF[':callsign']);
    }
  } catch (Exception $e) {
    // This shouldn't happen... but just in case...
    mypd("    CQP-ACE ERROR CREATE/UPDATE:");
    mypd("  " . $e->getMessage());
  }
} catch (Exception $e) {
  print ("Error on " . $CQPF[':callsign'] . "\n");
  print_r($CQPF);
  print "\n";
  print $e->getMessage() . "\n\n";
}

?>
