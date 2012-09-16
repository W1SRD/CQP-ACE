<?php

//
// XHDRCrack
//
// Process a log file to extract required CQP contest information
// from the Cabrillo headers at the top of the log.
//
// The required information is output as a CSV record to stdout.
// Debug and error information is written to stderr
//

// ASSUMES that the caller provides a routine _xhdr_getl that
// will return the log to be processed line by line.

// Global data

$_xhdr_CQPF = '';


// Processing functions

// _xhdr_cabCategory
// process a Cab 2 or 3 category line with potentially many fields
// eg: CATEGORY: SO-LP EXPEDITION SCHOOL

function _xhdr_cabCategory($l) {
  // Old styple category record
  $l = preg_replace("/^CATEGORY: /", '', $l);
  $l = preg_replace("/^CATEGORY.*: /", '', $l);
  $l = preg_replace("/\s{2,}/", ' ', $l);

  // Should be left with one or more tokens... bust into an array and proces
  // each one found
  $tok = explode(' ', $l);

  foreach ($tok as $t) {
    _xhdr_parseCatToken($t);
  }
}

// xhdr_parseCatToken
// Handle a category token and fill in our CQP fields as we can
//
// Note: Doesn't handle every known token, looks only for ones
// we care about.
//

function _xhdr_parseCatToken($t) {
  global $_xhdr_CQPF;

  switch ($t) {
    case 'MULTI-OP':
    case 'MULTI-SINGLE':
    case 'MULTI-ONE':
    case 'MULTI-SINGLE-OP':
    case 'MS':
      $_xhdr_CQPF['CATEGORY'] = 'MS';
      break;

    case 'MULTI-MULTI':
    case 'TWO':
      $_xhdr_CQPF['CATEGORY'] = 'MM';
      break;

    case 'SINGLE-OP':
    case 'SINGLE-OPERATOR':
    case 'SINGLE-':
    case 'SO':
      $_xhdr_CQPF['CATEGORY'] = 'S';
      break;

    case 'SINGLE-OP-ASSISTED':
      $_xhdr_CQPF['ASSISTED'] = 'Y';
      $_xhdr_CQPF['CATEGORY'] = 'S';
      break;

    case 'SO-LP':
    case '(SO-LP)':
    case 'SINGLE-OPERATOR-LOW':
      $_xhdr_CQPF['CATEGORY'] = 'S';
      $_xhdr_CQPF['POWER'] = 'L';
      break;

    case 'SO-HP':
    case 'SINGLE-OP-HIGH-MIXED':
      $_xhdr_CQPF['CATEGORY'] = 'S';
      $_xhdr_CQPF['POWER'] = 'H';
      break;
 
    case 'MS-HP':
    case '(MS-HP)':
      $_xhdr_CQPF['CATEGORY'] = 'MS';
      $_xhdr_CQPF['POWER'] = 'H';
      break;

    case 'MS-LP':
      $_xhdr_CQPF['CATEGORY'] = 'MS';
      $_xhdr_CQPF['POWER'] = 'L';
      break;

    case 'HIGH':
    case 'HP':
      $_xhdr_CQPF['POWER'] = 'H';
      break;

    case 'LOW':
    case 'LO':
    case 'LP':
      $_xhdr_CQPF['POWER'] = 'L';
      break;

    case 'SO-QRP':
      $_xhdr_CQPF['CATEGORY'] = 'S';
      $_xhdr_CQPF['POWER'] = 'Q';
      break;

    case 'QRP':
      $_xhdr_CQPF['POWER'] = 'Q';
      break;

    case 'COUNTY-EXPEDITION':
    case 'EXPEDITION':
      $_xhdr_CQPF['CEXP'] = 'Y';
      break;

    case 'SCHOOL':
    case 'SCHOOL-CLUB':
      $_xhdr_CQPF['SCHOOL'] = 'Y';
      break;
 
    case 'MOBILE':
    case 'ROVER':
      $_xhdr_CQPF['MOBILE'] = 'Y';
      break;

    case 'CHECKLOG':
    case 'CHECK':
    case 'CHECK-LOG':
      $_xhdr_CQPF['CATEGORY'] = 'C';
      break;

    case 'NONASSISTED':
    case 'NON-ASSISTED':
    case 'UNASSISTED':
      $_xhdr_CQPF['ASSISTED'] = 'N';
      break;

    case 'ASSISTED':
    case 'UNLIMITED':
      $_xhdr_CQPF['ASSISTED'] = 'Y';
      break;

    default:
      break;
  }
}

// _xhdr_cabSoapbox
// Scan SOAPBOX for CQP categories

function _xhdr_cabSoapbox($l) {
  global $_xhdr_CQPF;

  $l = strtoupper($l);
  
  if (preg_match("/[\s|-|(]YL[\s|-|$]/", $l)) {
    $_xhdr_CQPF['YL'] = 'Y';
  }

  if (preg_match("/MOBILE/", $l)) {
    $_xhdr_CQPF['MOBILE'] = 'Y';
  }

  if (preg_match("/SCHOOL/", $l)) {
    $_xhdr_CQPF['SCHOOL'] = 'Y';
  }
  if (preg_match("/YOUTH/", $l)) {
    $_xhdr_CQPF['YOUTH'] = 'Y';
  }
  if (preg_match("/NEW[\s|-]CONTESTER/", $l)) {
    $_xhdr_CQPF['NEWC'] = 'Y';
  }
}

//
// _xhdr_init()
//
// Initialize field array
//

function _xhdr_init() {
  global $_xhdr_CQPF;

  $_xhdr_CQPF = array(
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
}


//
// XHDRcrack
//
// Processes a Cabrillo log by calling _xhdr_getl to process lines
// of the log.
//
// Returns NULL if errors are encountered, otherwise returns a pointer
// to $_xhdr_CQPF
//

function XHDRcrack() {
  global $_xhdr_CQPF;
  
  // Initialise global data
  _xhdr_init(); 
  
  // Validate this is a Cabrillo file - version 2 or 3 - doesn't matter
  $l = _xhdr_getl();
  
  if (!preg_match("/^START-OF-LOG: /", $l)) {
    error_log("$argv[1]:  Not a Cabrillo file\n");
    return (array());
  }
  
  // Scan the headers and determine what we can
  while (!preg_match("/^QSO:/", $l)) {
    // Chop new line from the line
    $l = substr($l, 0, -1);
  
    if (preg_match("/^CATEGORY/", $l)) {
      _xhdr_cabCategory($l);
    } elseif (preg_match("/^SOAPBOX:/", $l)) {
      _xhdr_cabSoapbox($l);
    } elseif (preg_match("/^CLUB:/", $l)) {
      // Club record...
      $l = preg_replace("/^CLUB: /", '', $l);
      $l = preg_replace("/\s{2,}/", '', $l);
      $l = preg_replace("/,/", '-', $l);
      $_xhdr_CQPF['CLUB'] = trim($l);
    } elseif (preg_match("/CLAIMED-SCORE:/", $l)) {
      $l = preg_replace("/^CLAIMED-SCORE: /", '', $l);
      $l = preg_replace("/\s{2,}/", '', $l);
      $l = trim($l);
      $_xhdr_CQPF['C_SCORE'] = $l;
    }
  
    $l = _xhdr_getl();
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
      error_log("$argv[1]:  INVALID number of fields in QSO record #1 - should be 11\n");
      return(array());
    }
    $_xhdr_CQPF['CALL'] = trim($f[5]);
    $_xhdr_CQPF['QTH'] = trim($f[7]);
  }
  
  // If we get this far, we should have all the information we can extract
  // so return the results
  
  print_r($_xhdr_CQPF);
  return($_xhdr_CQPF);

}  
?>
