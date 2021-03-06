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

// ASSUMES that the caller provides two routines:
// _xhdr_getl() 
// Returns the log to be processed line by line.
// 
// _xhdr_getlog()
// Returns the whole log as a multi-line string
// 
// Global data

$_xhdr_CQPF = '';


// Processing functions

// _xhdr_cabCategory
// process a Cab 2 or 3 category line with potentially many fields
// eg: CATEGORY: SO-LP EXPEDITION SCHOOL

function _xhdr_cabCategory($l) {
  // Old styple category record
  $l = preg_replace("/^CATEGORY:/", '', $l);
  $l = preg_replace("/^CATEGORY.*:/", '', $l);
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

  // Remove all spaces - we should have a single token perhaps with a leading
  // or trailing space
  $t = preg_replace("/ /",'', $t);

  switch ($t) {
    case 'MULTI-OP':
    case 'MULTI-SINGLE':
    case 'MULTI-ONE':
    case 'MULTI-SINGLE-OP':
    case 'MS':
      $_xhdr_CQPF[':operator_category'] = 'MULTI-SINGLE';
      break;

    case 'MULTI-MULTI':
      $_xhdr_CQPF[':operator_category'] = 'MULTI-MULTI';
      break;

    case 'ONE':
      $_xhdr_CQPF[':transmitter_category'] = 'ONE';
      break;

    case 'TWO':
      $_xhdr_CQPF[':transmitter_category'] = 'TWO';
      $_xhdr_CQPF[':operator_category'] = 'MULTI-MULTI';
      break;


    case 'SINGLE-OP':
    case 'SINGLE-OPERATOR':
    case 'SINGLE-':
    case 'SINGLE':
    case 'SO':
      $_xhdr_CQPF[':operator_category'] = 'SINGLE-OP';
      break;

    case 'SINGLE-OP-ASSISTED':
      $_xhdr_CQPF['ASSISTED'] = 'Y';
      $_xhdr_CQPF[':operator_category'] = 'MULTI-SINGLE';
      break;

    case 'SO-LP':
    case 'SOLP':
    case '(SO-LP)':
    case 'SINGLE-OPERATOR-LOW':
      $_xhdr_CQPF[':operator_category'] = 'SINGLE-OP';
      $_xhdr_CQPF[':power_category'] = 'LOW';
      break;

    case 'SOHP':
    case 'SO-HP':
    case 'SINGLE-OP-HIGH-MIXED':
      $_xhdr_CQPF[':operator_category'] = 'SINGLE-OP';
      $_xhdr_CQPF[':power_category'] = 'HIGH';
      break;
 
    case 'MSHP':
    case 'MS-HP':
    case '(MS-HP)':
      $_xhdr_CQPF[':operator_category'] = 'MULTI-SINGLE';
      $_xhdr_CQPF[':power_category'] = 'HIGH';
      break;

    case 'MSLP':
    case 'MS-LP':
      $_xhdr_CQPF[':operator_category'] = 'MULTI-SINGLE';
      $_xhdr_CQPF[':power_category'] = 'LOW';
      break;

    case 'HIGH':
    case 'HP':
      $_xhdr_CQPF[':power_category'] = 'HIGH';
      break;

    case 'LOW':
    case 'LO':
    case 'LP':
      $_xhdr_CQPF[':power_category'] = 'LOW';
      break;

    case 'SO-QRP':
      $_xhdr_CQPF[':power_category'] = 'SINGLE-OP';
      $_xhdr_CQPF[':power_category'] = 'QRP';
      break;

    case 'QRP':
      $_xhdr_CQPF[':power_category'] = 'QRP';
      break;

    case 'COUNTY-EXPEDITION':
    case 'EXPEDITION':
      $_xhdr_CQPF[':station_category'] = 'CCE';
      $_xhdr_CQPF['CEXP'] = 'Y';
      break;

    case 'SCHOOL':
    case 'SCHOOL-CLUB':
      $_xhdr_CQPF[':station_category'] = 'SCHOOL';
      $_xhdr_CQPF['SCHOOL'] = 'Y';
      break;
 
    case 'MOBILE':
    case 'ROVER':
      $_xhdr_CQPF[':station_category'] = 'MOBILE';
      $_xhdr_CQPF['MOBILE'] = 'Y';
      break;

    case 'CHECKLOG':
    case 'CHECK':
    case 'CHECK-LOG':
      $_xhdr_CQPF[':operator_category'] = 'CHECK';
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
    $_xhdr_CQPF[':overlay_yl'] = TRUE;
  }

  if (preg_match("/MOBILE/", $l)) {
    $_xhdr_CQPF[':station_category'] = 'MOBILE';
  }

  if (preg_match("/SCHOOL/", $l)) {
    $_xhdr_CQPF[':station_category'] = 'SCHOOL';
  }
  if (preg_match("/YOUTH/", $l)) {
    $_xhdr_CQPF[':overlay_youth'] = TRUE;
  }
  if (preg_match("/NEW[\s|-]CONTESTER/", $l)) {
    $_xhdr_CQPF[':overlay_new_contester'] = TRUE;
  }
}


// _xhdr_CQPspecial
// Process CQP special fields
//

function _xhdr_CQPspecial($l) {
  global $_xhdr_CQPF;

  // All CQP records are of the form X-CQP-
  // Ignnore the email info...
  if (preg_match("/^X-CQP-EMAIL/", $l)) {
    return;
  }

  // Split off the field information...
  preg_match("/^X-CQP-.*?:\s([\w\W\s]{1,}).*?$/", $l, $m);

  switch(strtoupper($m[1])) {
    case 'YOUTH':
      $_xhdr_CQPF[':overlay_youth'] = TRUE;;
      break;

    case 'COUNTY EXPEDITION':
      $_xhdr_CQPF[':station_category'] = 'CCE';
      break;

    case 'YL':
      $_xhdr_CQPF[':overlay_yl'] = TRUE;
      break;

    case 'SCHOOL':
      $_xhdr_CQPF[':station_category'] = 'SCHOOL';
      break;

    case 'MOBILE':
      $_xhdr_CQPF[':station_category'] = 'MOBILE';
      break;

    case 'NEW CONTESTER':
      $_xhdr_CQPF[':overlay_new_contester'] = TRUE;
      break;

    default:
      break;
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
    ':callsign'               => '', 		// Callsign for entry
    ':email_address'           => '', 		// Email address
    ':station_location'       => 'FIXED',    	// Location
    ':operator_category'      => 'SINGLE-OP', 	// Contest category (S|MS|MM)
    ':power_category'         => 'HIGH', 	// Power level (Q|L|H)
    ':station_category'       => 'FIXED',	// Station Category
    ':transmitter_category'   => 'ONE', 	// Transmitter cateogry (ONE|TWO)
    ':club'                   => 'NONE GIVEN',	// Club for club competition - may be blank
    ':submission_date'        => '',   		// SQL formatted date
    ':overlay_yl'             => FALSE,		// Overlay YL (Y|N|?)
    ':overlay_youth'          => FALSE,		// Overlay YOUTH (Y|N|?)
    ':overlay_new_contester'  => FALSE,		// Overlay NEW CONTESTER (Y|N|?)
    ':claimed_score'          => 0, 		// Claimed score
    ':log_filename'           => '',    	// Name of processed log file stripped of any path
    ':soapbox'		      => '',    	// Soapbox comments - multiline with SOAPBOX: headers
    ':cabrillo_header'        => '',    	// Cabrillo headers as multiline string	
    ':qso_recs_present'       => 0,     	// True if QSO records loaded in the QSO table
    ':last_updated'           => '',    	// SQL formatted time stamp
    'ASSISTED'                => 'N',   	// Assisted or not
    'CEXP'                    => 'N',   	// Overlay COUNTY EXPEDITION (Y|N|?)
    'MOBILE'                  => 'N',   	// Overlay MOBILE (Y|N|?)
    'SCHOOL'                  => 'N',   	// Overlay SCHOOL (Y|N)
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
  if (!preg_match("/^START-OF-LOG:/", $l)) {
    error_log("  Not a Cabrillo file\n");
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
    } elseif (preg_match("/^CLUB.*?:/", $l)) {
      // Club record...
      $l = preg_replace("/^CLUB.*?:\s{0,}/", '', $l);
      $l = preg_replace("/\s{2,}/", ' ', $l);
      $l = preg_replace("/[\",']/", '-', $l);
    
      // Check for a NULL club record
      $l = (!$l || preg_replace('/ /', '', $l) == '') ? "NONE GIVEN" : $l;

      $_xhdr_CQPF[':club'] = strtoupper(trim($l));
    } elseif (preg_match("/CLAIMED-SCORE:/", $l)) {
      $l = preg_replace("/^CLAIMED-SCORE: /", '', $l);
      $l = preg_replace("/\s{2,}/", '', $l);
      $l = trim($l);
      $_xhdr_CQPF[':claimed_score'] = $l;
    } elseif (preg_match("/^X-CQP-/", $l)) {
      // CQP special header
      _xhdr_CQPspecial($l);
    }
  
    $l = _xhdr_getl();
  }
  
  // Although we should break out here with the first QSO
  // record, we may have a log that has QSO records split
  // over two lines - for example, if it was processed with
  // NOTEPAD and then cut and pasted into the web form...
  // So, we process the entire log as a multi-line string to
  // chop out that first record...

  $log = _xhdr_getlog();

  if (preg_match("/(^QSO:.*?)^[QSO|END].*$/ms", $log, $m)) {
    // We arrive here with the full QSO record in $m[1] 
    // possibly as a mutliline string...
    $l = preg_replace("/[\r\n]/ms", ' ', $m[1]);
    // Replace any tab with spaces
    $l = preg_replace("/\t/", ' ', $l);
    // Make sure there is at least one space after the QSO:...
    $l = preg_replace("/QSO:/", 'QSO: ', $l);
    // Remove multiple spaces and chop into fields...
    $l = preg_replace("/\s{2,}/", ' ', $l);
    $f = explode(' ', trim($l));
  
    // We should have 11 or 12 fields including the QSO: at the front and
    // the optional transmitter number as an optional 12th field
    // If so, field 5 should be the sent callsign and field 7 the sent QTH...
    if (count($f) != 11 && count($f) != 12) {
      error_log("    INVALID number of fields in QSO record #1 - should be 11(12)\n");
      return(array());
    }
    // $f[5] = preg_replace("/\//",'-', $f[5]);
    $_xhdr_CQPF[':callsign'] = preg_replace("/ /", '', trim($f[5]));
    $_xhdr_CQPF[':station_location'] = preg_replace("/ /", '',  trim($f[7]));
  }
  
  // If we get this far, we should have all the information we can extract
  // so return the results
  
  return($_xhdr_CQPF);
}  
?>
