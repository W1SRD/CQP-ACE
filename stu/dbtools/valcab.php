#!/usr/bin/php
<?php

// Validate a cab file and see whether its suitable for smashing
// into the DB.
//
// Version 1.0 - 10/23/2012
// Initial version
//
// Version 2.0 - 10/24/2012
// - Turned into a utility to do various validations of a Cabrillo file
//


// This is intended for QSO Party style logs where the format is:
// QSO: <band> <mode> <date> <time> <sent call> <sent #> <sent qth> <rx call> <rx #> <rx qth>
//
// Valid formats confirm to Cabrillo requirements.

// Field types
define('QDATE', 1);
define('ALPHA', 2);
define('QTIME', 3);
define('CALL', 4);
define('NUMERIC', 5);

// Field mappings - field numbered 0 from QSO
$FM = array(
  0 => '',
  1 => NUMERIC,
  2 => ALPHA,
  3 => QDATE,
  4 => QTIME,
  5 => CALL,
  6 => NUMERIC,
  7 => ALPHA,
  8 => CALL,
  9 => NUMERIC,
 10 => ALPHA,
);

// Regular expressions to match field types
$FTREGX = array(
  QDATE 	=> "/[0-9]{4}-[0-9]{2}-[0-9]{2}/",
  QTIME		=> "/[0-9]{4}/",
  ALPHA		=> "/[A-Z]{1,}/i",
  NUMERIC	=> "/[0-9]{1,}/",
  CALL		=> ":[A-Z]+[0-9]+|[0-9]+[A-Z]+.{1,}:i",
);


//
// CabCleanQRec($q)
// 
// Clean up a Cab QSO record
//

function CabCleanQRec($q) {
  $q = preg_replace('/:/', ': ', $q);           // At least one space after QSO:
  $q = preg_replace("/[\n\r'\"]/", '', $q);     // Remove \n, \r, ' and "
  $q = preg_replace("/[\s|\t]{1,}/", ' ', $q);  // Replace multiple spaces and tabs with 1 space
  $q = trim($q);

  return ($q);
}

//
// CheckFields($r)
//
// Passedin a raw QSO record from the log - returns TRUE is ALL fields match
// the fm spec.  
//
// If the record contains less than the number of fields in fm, returns FALSE
// If the record contains greather than the number of fields in fm + 1, returns FALSE
//

function CheckFields($r) {
  global $FM, $FTREGX;

  $nfm = count($FM);
  
  // Clean up record
  $rec = CabCleanQRec($r);

  $f = explode(' ', $rec);
  $nf = count($f);

  if ($nf < $nfm || $nf > ($nfm + 1)) { 
    return FALSE;
  }

  for ($i = 1; $i < $nfm; $i++) {
    if (!empty($f[$i]) && !preg_match($FTREGX[$FM[$i]], $f[$i])) {
      return FALSE;
    }
  }

  return TRUE;
}


// MAIN starts here

if (count($argv) < 2) {
  print "usage: valcab [options] <cabfile>\n";
  print "Options:\n";
  print "-e    - only print if there are any errors\n";
  print "-v    - print invalid QSO records\n";
  exit(1);
}

$flag = getopt("ve");
$fname = $argv[count($argv)-1];

$verbose = FALSE;
$eonly = FALSE;

if ($flag) {
  if (array_key_exists('v', $flag)) {
    $verbose = TRUE;
  }
  if (array_key_exists('e', $flag)) {
    $eonly = TRUE;
  }

}

if (!($log = file_get_contents($fname))) {
  print "$fname: Cant open input file\n";
  exit(1);
}

if (!preg_match("/^START-OF-LOG:.*^END-OF-LOG:.$/ms", $log)) {
  print "$fname: Didn't find Cabrillo log\n";
  exit(1);
}

// If we get this far, we likely have a Cabrillo log - split out the 
// QSO records...

if (!($nq = preg_match_all("/(^QSO:.*\n)/m", $log, $q))) {
  print "$fname: No QSO records found\n";
  exit(1);
}

$nbad = 0;
$badrecs = array();

for ($i=0; $i < count($q[0]); $i++) {
  $qso = $q[1][$i];

  if (!CheckFields($qso)) {
    if ($verbose) {
      $badrecs[] = $qso;
    }
    $nbad++;
  }
}

// Print results
if ($eonly && $nbad) {
  print "$fname: QSO recs = $nq  Bad Q = $nbad\n";
} 

if ($verbose) {
  if (!$eonly) {
    print "$fname: QSO recs = $nq  Bad Q = $nbad\n";
  }

  foreach ($badrecs as $q) {
    print $q;
  }
}

exit(0);


?> 
