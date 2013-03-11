<?php

// PHP class for manipulating Cabrillo files
//
// If there weren't so many scripting languages...
//
// This PHP class provides a simple set of tools for manipulating
// Cabrillo format contest logs.  It feels like a re-invention of
// Trey's (N5KO) cabrillo.pm PERL module and too some degree it is...
//
// Sigh...
//
// Version 1.0 - Initial implementation
//


class Cabrillo {

// Variable definitions

private $LOG;					// Initialized by constructor
//
// CheckContest($log)
//
// Check this log to see that it is for CQP
//

function CheckContest($log) {
  // Extract the CONTEST Cabrillo record - there SHOULD be one
  if (preg_match("/^CONTEST:(.*?)\n.*$/ms", $log, $m)) {
    // $m[1] contains the CONTEST name
    if (preg_match("/CA|CQP|NCCC/i", $m[1])) {
      return TRUE;
    }
  }
  return FALSE;
}


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


// CabGetSoapbox
//
// Returns the soapbox if one exists as a multiline string

function CabGetSoapbox($log) {
  $log = preg_replace('/\r/', '', $log);

  if (preg_match_all('/^SOAPBOX.*$/m', $log, $m)) {
    return (implode("\n", $m[0]));
  } else {
    return('');
  }
}

// CabGetHeaders($log)
//
// Return all CAB headers as a multiline string

function CabGetHeaders($log) {
  $log = preg_replace('/\r/', '', $log);
  
  if (preg_match("/^START-OF-LOG.*?$\n(.*?)\n^QSO:.*$/ms", $log, $m)) {
    return ($m[1]);
  } else {
    return('');  // This should NEVER happen!
  }
}


//
// CabGetCall($log)
//
// Look for the Callsign in the log...  we try this two ways...
// 1. Look for the CALLSIGN: header - note: disabled for now, use 2.
// 2. If that fails, look for the first QSO record and extract the 4th field
// 
// Returns the call found...

function CabGetCall($log) {
  // Scan for the CALLSIGN: header
  // if (preg_match("/^CALLSIGN:[\s{0,}|\t{0,}]([\w|\W]+?)[\s|\n]/ms", $log, $match)) {
  //   return($match[1]);
  // }

  // Scan for the first QSO record...
  if (preg_match("/(QSO:.*\n)/ms", $log, $match)) {
    // Got a QSO record...  clean it up and split it into fields
    $q = $match[1];
    $q = CabCleanQRec($q);
    $f = explode(' ', $q);
    $f[5] = preg_replace('/\//', '-', $f[5]);
    return($f[5]);	// Cabrillo log should have this!
  }

  pd("    Can't find CALLSIGN for this log");
  exit(1);
}

// CabGetQcount($log)
//
// Returns a count of the number of QSO records in the log
//

function CabGetQcount($log) {
  return (preg_match_all("/(^QSO:.*?\n)/ms", $log, $m));
}



//
// CabCheckQDates($log,$start,$end)
//  
// Check that the first and last Q records in the Cabrillo are within
// the window of the START and END

function CabCheckQDates($log, $start, $end) {
  // Crack log into QSO records...
  if (!preg_match_all("/(^QSO:.*?\n)/ms", $log, $m)) {
    // no QSO records... return FALSE
    return FALSE;
  }

  // If we get here, $m contains at least two entries... the log and the first
  // (perhaps only) QSO record found - as an array of arrays....

  $first = $m[1][0];
  $last  = $m[1][count($m[1]) - 1];
  
  // Clean up both first and last
  $first = explode(' ', CabCleanQRec($first));
  $last  = explode(' ', CabCleanQRec($last));
 
  // Element 3 should contain the date of each Q...
  
  return ((strtotime($first[3]) >= $start) && 
           (strtotime($last[3]) <= $end && strtotime($last[3]) >= $start) 
         ) ? TRUE : FALSE;
}


//
// CabCrack($log)
//
// Returns an associative array
// of all the data required to populate a CQP-ACE log table
// database row...

function CabCrack($email, $fname, $log) {
  // Set up for calls to _xhdr_getl by...
  setup_xhdr_getl($log);

  // Crack the log into category, power etc.
  $CQPF = XHDRcrack();

  if (!$CQPF) {
    // Bust - likely not enough fields in the Cabrillo
    return (FALSE);
  }

  $CQPF[':number_qso_recs'] = CabgetQcount($log);
  $CQPF[':soapbox'] = CabGetSoapbox($log);
  $CQPF[':cabrillo_header'] = CabGetHeaders($log);
  $CQPF[':log_filename'] = $fname;
  $CQPF[':submission_date'] = gmdate("Y-m-d");
  $CQPF[':last_updated'] = gmdate("Y-m-d H:i:s");
  $CQPF[':email_address'] = trim($email);

  return $CQPF;
}



?>
