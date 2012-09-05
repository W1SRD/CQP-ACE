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

// Utility functions

function getl($f) {
  global $argv;

  $l = fgets($f);
  if (!$l) {
    // We don't expect premature EOF - moan and exit
    error_log("$argv[1]:  Premature EOF\n");
    exit(1);
  }
  $l = preg_replace("/\r/", ' ', $l);
  return($l);
}

// Processing functions

// cabCategory
// process a Cab 2 or 3 category line with potentially many fields
// eg: CATEGORY: SO-LP EXPEDITION SCHOOL

function cabCategory($l) {
  // Old styple category record
  $l = preg_replace("/^CATEGORY: /", '', $l);
  $l = preg_replace("/^CATEGORY.*: /", '', $l);
  $l = preg_replace("/\s{2,}/", ' ', $l);

  // Should be left with one or more tokens... bust into an array and proces
  // each one found
  $tok = explode(' ', $l);

  foreach ($tok as $t) {
    parseCatToken($t);
  }
}

// parseCatToken
// Handle a category token and fill in our CQP fields as we can
//
// Note: Doesn't handle every known token, looks only for ones
// we care about.
//

function parseCatToken($t) {
  global $CQPF;

  switch ($t) {
    case 'MULTI-OP':
    case 'MULTI-SINGLE':
    case 'MULTI-ONE':
    case 'MULTI-SINGLE-OP':
    case 'MS':
      $CQPF['CATEGORY'] = 'MS';
      break;

    case 'MULTI-MULTI':
    case 'TWO':
      $CQPF['CATEGORY'] = 'MM';
      break;

    case 'SINGLE-OP':
    case 'SINGLE-OPERATOR':
    case 'SINGLE-':
    case 'SO':
      $CQPF['CATEGORY'] = 'S';
      break;

    case 'SINGLE-OP-ASSISTED':
      $CQPF['ASSISTED'] = 'Y';
      $CQPF['CATEGORY'] = 'S';
      break;

    case 'SO-LP':
    case '(SO-LP)':
    case 'SINGLE-OPERATOR-LOW':
      $CQPF['CATEGORY'] = 'S';
      $CQPF['POWER'] = 'L';
      break;

    case 'SO-HP':
    case 'SINGLE-OP-HIGH-MIXED':
      $CQPF['CATEGORY'] = 'S';
      $CQPF['POWER'] = 'H';
      break;
 
    case 'MS-HP':
    case '(MS-HP)':
      $CQPF['CATEGORY'] = 'MS';
      $CQPF['POWER'] = 'H';
      break;

    case 'MS-LP':
      $CQPF['CATEGORY'] = 'MS';
      $CQPF['POWER'] = 'L';
      break;

    case 'HIGH':
    case 'HP':
      $CQPF['POWER'] = 'H';
      break;

    case 'LOW':
    case 'LO':
    case 'LP':
      $CQPF['POWER'] = 'L';
      break;

    case 'SO-QRP':
      $CQPF['CATEGORY'] = 'S';
      $CQPF['POWER'] = 'Q';
      break;

    case 'QRP':
      $CQPF['POWER'] = 'Q';
      break;

    case 'COUNTY-EXPEDITION':
    case 'EXPEDITION':
      $CQPF['CEXP'] = 'Y';
      break;

    case 'SCHOOL':
    case 'SCHOOL-CLUB':
      $CQPF['SCHOOL'] = 'Y';
      break;
 
    case 'MOBILE':
    case 'ROVER':
      $CQPF['MOBILE'] = 'Y';
      break;

    case 'CHECKLOG':
    case 'CHECK':
    case 'CHECK-LOG':
      $CQPF['CATEGORY'] = 'C';
      break;

    case 'NONASSISTED':
    case 'NON-ASSISTED':
    case 'UNASSISTED':
      $CQPF['ASSISTED'] = 'N';
      break;

    case 'ASSISTED':
    case 'UNLIMITED':
      $CQPF['ASSISTED'] = 'Y';
      break;

    default:
      break;
  }
}

// cabSoapbox
// Scan SOAPBOX for CQP categories

function cabSoapbox($l) {
  global $CQPF;

  $l = strtoupper($l);
  
  if (preg_match("/ YL /", $l)) {
    $CQPF['YL'] = 'Y';
  }

  if (preg_match("/ MOBILE /", $l)) {
    $CQPF['MOBILE'] = 'Y';
  }

  if (preg_match("/ SCHOOL /", $l)) {
    $CQPF['SCHOOL'] = 'Y';
  }
  if (preg_match("/ YOUTH /", $l)) {
    $CQPF['YOUTH'] = 'Y';
  }
  if (preg_match("/ NEW CONTESTER /", $l)) {
    $CQPF['NEWC'] = 'Y';
  }
}

// Initialize field array

$CQPF = array(
  'CALL'            => '',      // Callsign for entry
  'QTH'             => '',      // Location
  'CATEGORY'        => 'S', 	// Contest category (S|MS|MM)
  'POWER'           => 'H',  	// Power level (Q|L|H)
  'ASSISTED'        => 'N',	// Assisted or not
  'CLUB'            => '',	// Club for club competition - may be blank
  'YOUTH'           => 'N',	// Overlay YOUTH (Y|N|?)
  'YL'              => 'N',	// Overlay YL (Y|N|?)
  'SCHOOL'          => 'N',	// Overlay SCHOOL (Y|N)
  'NEWC'            => 'N',	// Overlay NEW CONTESTER (Y|N|?)
  'CEXP'            => 'N',	// Overlay COUNTY EXPEDITION (Y|N|?)
  'MOBILE'          => 'N',	// Overlay MOBILE (Y|N|?)
  'C_SCORE'         => 0, 	// Claimed score
  'LOGNAME'         => '',      // Name of processed log file stripped of any path
);


$narg = count($argv);
if ($narg != 2) {
  error_log("Usage: xhdr <inlog>\n");
  exit(1);
}


// Open the log file
$log = fopen($argv[1], "r");
if (!$log) {
  error_log("Can't open input file - $argv[1]\n");
  exit(1);
}

// Validate this is a Cabrillo file - version 2 or 3 - doesn't matter
$l = getl($log);

if (!preg_match("/^START-OF-LOG: /", $l)) {
  error_log("$argv[1]:  Not a Cabrillo file\n");
  exit(1);
}

// Scan the headers and determine what we can
while (!preg_match("/^QSO:/", $l)) {
  // Chop new line from the line
  $l = substr($l, 0, -1);

  if (preg_match("/^CATEGORY/", $l)) {
    cabCategory($l);
  } elseif (preg_match("/^SOAPBOX:/", $l)) {
    cabSoapbox($l);
  } elseif (preg_match("/^CLUB:/", $l)) {
    // Club record...
    $l = preg_replace("/^CLUB: /", '', $l);
    $l = preg_replace("/\s{2,}/", '', $l);
    $l = preg_replace("/,/", '-', $l);
    $CQPF['CLUB'] = trim($l);
  } elseif (preg_match("/CLAIMED-SCORE:/", $l)) {
    $l = preg_replace("/^CLAIMED-SCORE: /", '', $l);
    $l = preg_replace("/\s{2,}/", '', $l);
    $l = trim($l);
    $CQPF['C_SCORE'] = $l;
  }

  $l = getl($log);
}

// In theory, we break out of the loop with the first QSO record...
if (preg_match("/^QSO:/", $l)) {
  // Replace any tab with spaces
  $l = preg_replace("/\t/", ' ', $l);
  // Remove multiple spaces and chop into fields...
  $l = trim($l);
  $l = preg_replace("/\s{2,}/", ' ', $l);
  $f = explode(' ', $l);

  // We should have 11 fields including the QSO: at the front
  // If so, field 5 should be the sent callsign and field 7 the sent QTH...
  if (count($f) != 11) {
    print_r($f);
    error_log("$argv[1]:  INVALID number of fields in QSO record #1 - should be 11\n");
    exit(1);
  }
  $CQPF['CALL'] = trim($f[5]);
  $CQPF['QTH'] = trim($f[7]);
}


// Remove the path from the logname...
$CQPF['LOGNAME'] = preg_replace("/.*\//", '', $argv[1]);


// If we get this far, we should have all the information we can extract
// so create a record for loading the DB.

print(implode(',',$CQPF) . "\n");
exit(0);

?>
