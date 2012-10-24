#!/usr/bin/php
<?php

// Validate a cab file and see whether its suitable for smashing
// into the DB.
//
// Split off all the QSO ecords in the file, then look at each
// field to make sure it has the format we expect.  If all records
// pass - we can use the DB for all other normalization - if not, we
// need a human to look this log over.
//
// Version 1.0 - 10/23/2012
// Initial version
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
  CALL		=> ":[A-Z]+[0-9]+|[0-9]+[A-Z]+.*:i",
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

if (count($argv) != 2) {
  print "usage: valcab <cabfile>\n";
  exit(1);
}

if (!($log = file_get_contents($argv[1]))) {
  print "$argv[1]: Cant open input file\n";
  exit(1);
}

if (!preg_match("/^START-OF-LOG:.*^END-OF-LOG:.$/ms", $log)) {
  print "$argv[1]: Didn't find Cabrillo log\n";
  exit(1);
}

// If we get this far, we likely have a Cabrillo log - split out the 
// QSO records...

if (!($nq = preg_match_all("/(^QSO:.*\n)/m", $log, $q))) {
  print "$argv[1]: No QSO records found\n";
  exit(1);
}

print "$argv[1]: QSO Records = $nq  ";

$nbad = 0;

for ($i=0; $i < count($q[0]); $i++) {
  $qso = $q[1][$i];

  if (!CheckFields($qso)) {
    $nbad++;
  }
}

if (!$nbad) {
  print "ALL MATCHED\n";
} else {
  print "$nbad Bad Qs\n";
}

exit(0);


?> 
