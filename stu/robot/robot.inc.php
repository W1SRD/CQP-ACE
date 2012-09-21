<?php


require_once('xhdr.inc.php');
require_once('errors.inc.php');

// Support functions called from robot.php

// pd - diag print
//
// Time stamped message with newline appended to STDOUT
//

function pd($l) {
  print(gmdate("d-M-Y_H:i:s") . " UTC: ". $l. "\n");
}

//
// Spam($msg)
//
// We can still get spam forwarded to us because of the logs@cqp.org
// mail alias.  We check here for the subject line to be something
// passable as a submitted log...
//

function Spam($msg) {
  if (preg_match("/^CQP 2012 Log/", $msg['SUBJECT'])) {
    return FALSE;
  }

  if (preg_match("/^Received-SPF: (\w+?) /ms", $msg['HEADERS'], $m) &&
      $m[1] == 'fail') {
    // Google flagged as junk...
    return TRUE;
  }

  $fields = split("/[\s\t.-]/", $msg['SUBJECT']);
  
  if (count($fields) > 4) {
    return (TRUE);
  }

  $call_rexp = "/([a-z]{1,2}|[0-9][a-z]|[a-z][0-9]|3da)[0-9]{1,3}[a-z]{1,3}/";

  if (count($fields) > 1 && !preg_match($call_rexp, $fields[1])) {
    return TRUE;
  }

  return FALSE;
}


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
// MakeLogName
//
// Append current date & time to the name provided
//

function MakeLogName($call) {
  return($call . '-' . gmdate("YmdHis"));
}


//
// WebformCheck($msg)
//
// Checks the body of the message passed to see if it
// contains a CQP webform header - if it does, returns
// it as a string (multiline).
//

function WebformCheck($msg) {
  // Grab the header if we can...
  $body = preg_replace("/\r/", '', $msg['BODY']);
  if (preg_match("/(^##CQP-WEBFORM.*?^##END-CQP-WEBFORM##\n).*$/ms", $body, $m)) {
    // $m[1] should be the header
    return($m[1]);
   } else {
    return ("");
   }
}


//
// CabFileCheck($msg)
//
// Checks the array passed as msg for a Cabrillo log as
// the only attachment
//
// Returns TRUE if Cabrillo, otherwise FALSE
//

function CabFileCheck($msg) {
  // See if we can do this in one go...
  // We grab the log contents as a copy
  // and then look for START-OF-LOG and END-OF-LOG - this is a simple
  // check - let's see if we can get away with it.
  $log = $msg['ATTACHMENTS'][0]['FILE'];
  return (preg_match("/^START-OF-LOG.*^END-OF-LOG:.*$/ms", $log));
}

//
// CabBodyCheck($msg)
//
// Scan the body of the message passed and see if there is a CAB
// log present - if so return it - otherwise, return FALSE
//

function CabBodyCheck($msg) {
  // Grab the log if we can...
  $log = $msg['BODY'];
  if (preg_match("/(^START-OF-LOG.*^END-OF-LOG:).*$/ms", $log, $m)) {
    // $m[1] should now be the log...
    return($m[1] . "\n");
  } else {
    return (FALSE);
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
    $q = preg_replace("/[\s|\t]{1,}/", ' ', $q);
    $f = explode(' ', $q);
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
// SendResponse($msg, $fname, $log)
//
// The $log in $msg has been found and written to $fname
//
// Compose a response back to the user and send it...
//

function SendResponse($msg, $fname, $log) {
  // Set up for calls to _xhdr_getl by...
  setup_xhdr_getl($log);

  // Crack the log into category, power etc.
  $CQPF = XHDRcrack();

  if (!$CQPF) {
    // Bust - likely not enough fields in the Cabrillo
    return (FALSE);
  }

  $CQPF['NQSO'] = CabgetQcount($log);

  // Format the reply message...
  $b =  "---------------------------------------------------------------------------\n";
  $b .= "## This is an automated response from the CQP Log Robot [ROBO-DEAN V1.0] ##\n";
  $b .= "## Please do not respond to this email address - send any questions to   ##\n";
  $b .= "## the CQP Chair at cqp-chair@cqp.org                                    ##\n";
  $b .= "---------------------------------------------------------------------------\n";
  $b .= "\n\n";
  $b .= "Thank you for submitting your log for CQP-2012!  We have received your\n";
  $b .= "log and determined the information below.  You may re-submit your log\n";
  $b .= "to correct any missing or incorrect information.\n";
  $b .= "\n";
  $b .= "Please read http://www.cqp.org/robo-dean-faq.html if you have any\n";
  $b .= "questions.\n";
  $b .= "\n";
  $b .= "Callsign           : " . $CQPF['CALL'] . "\n";
  $b .= "QTH                : " . $CQPF['QTH'] . "\n";
  $b .= "Category           : ";
  
  switch ($CQPF['CATEGORY']) {
    case 'S' :  $b .= "SINGLE-OP\n"; break;
    case 'MM':  $b .= "MULTI-MULTI\n"; break;
    case 'MS':  $b .= "MULTI-SINGLE\n"; break;
  }

  $b .= "Power              : ";

  switch ($CQPF['POWER']) {
    case 'L':  $b .= "LOW\n"; break;
    case 'H':  $b .= "HIGH\n"; break;
    case 'Q':  $b .= "QRP\n"; break;
  }

  $b .= "Assisted           : " . ($CQPF['ASSISTED'] == 'Y' ? "Yes\n" : "No\n");
  $b .= "Club               : " . ($CQPF['CLUB'] ? ($CQPF['CLUB'] . "\n") : "None\n");
  $b .= "Number of QSOs     : " . $CQPF['NQSO'] . "\n";
  $b .= "\n";
  $b .= "CQP OVERLAY CATEGORIES\n\n";
  $b .= "Youth              : " . ($CQPF['YOUTH'] == 'Y' ? "Yes\n" : "No\n");
  $b .= "YL                 : " . ($CQPF['YL'] == 'Y' ? "Yes\n" : "No\n");
  $b .= "New Contester      : " . ($CQPF['NEWC'] == 'Y' ? "Yes\n" : "No\n");
  $b .= "School             : " . ($CQPF['SCHOOL'] == 'Y' ? "Yes\n" : "No\n");
  $b .= "County Expedition  : " . ($CQPF['CEXP'] == 'Y' ? "Yes\n" : "No\n");
  $b .= "Mobile             : " . ($CQPF['MOBILE'] == 'Y' ? "Yes\n" : "No\n");

  $b .= "\n";
  $b .= "NOTE\n\n";
  $b .= "If you submitted a log in the MOBILE overlay category, the QTH field\n";
  $b .= "above reflects only the first CA county or State within which you\n";
  $b .= "operated.\n";
  $b .= "\n";
  $b .= "Thanks again and 73!\n";
  $b .= "The NCCC CQP Team\n";

  $subject = "[$fname] " . $msg['SUBJECT'];

  erSendMessage($msg['TO'], $msg['FROM'], $subject, $b);
  return (TRUE);
}


//
// NonComprendez($msg)
//
// Called when we have a submission that doesn't appear as
// junk but we don't undertand it - most likely because we
// didnt find a Cabrillo log or there are multiple attachments

function NonComprendez($msg, $needHuman) {
  // Format the reply message...
  $b =  "---------------------------------------------------------------------------\n";
  $b .= "## This is an automated response from the CQP Log Robot [ROBO-DEAN V1.0] ##\n";
  $b .= "## Please do not respond to this email address - send any questions to   ##\n";
  $b .= "## the CQP Chair at cqp-chair@cqp.org                                    ##\n";
  $b .= "---------------------------------------------------------------------------\n";
  $b .= "\n\n";
  $b .= "Thank you for your CQP-2012 submission!  We have received your\n";
  $b .= "submission but ROBO-DEAN doesn't understand what you sent.\n\n";

  $b .= "PROBABLE CAUSE\n\n";

  switch ($needHuman) {
    case TOOMANYATTACHMENTS:
      $b .= "We found more than one attachment on the message and so the robot\n";
      $b .= "can't figure out which is the right one.\n";
      break;

    case NOCABRILLOATTACHMENT:
      $b .= "We found an attachment but it doesn't look like it's in Cabrillo\n";
      $b .= "format.  No worries - my human boss will figure it out!\n";
      break;

    case NOCABRILLOBODY:
      $b .= "We didn't find an attachment and couldn't find a Cabrillo log in\n";
      $b .= "the message itself.\n";
      break;

    case NOTCQPCONTEST:
      $b .= "We found a Cabrillo log but either the CONTEST: header record is\n";
      $b .= "missing or it doesn't look like a log for CQP\n";
      break;

    case CABRILLOERROR:
      $b .= "We found a Cabrillo log but we had a problem checking it.  This\n";
      $b .= "because this isn't a log for CQP or because my human boss didn't\n";
      $b .= "get his coding right for all cases - he's only human after all.\n";
      break;

    default:
      $b .= "This problem is above my pay grade!  We'll leave this to the human.\n";
      break;
  }

  $b .= "\n";
  $b .= "Your submission has been flagged to the CQP Team for review.\n\n";
  $b .= "Please read http://www.cqp.org/robo-dean-faq.html if you have any\n";
  $b .= "questions.\n";
  $b .= "\n";
  $b .= "73!\n";
  $b .= "The NCCC CQP Team\n";

  $subject = "[CQP ROBO-DEAN] " . $msg['SUBJECT'];

  erSendMessage($msg['TO'], $msg['FROM'], $subject, $b);
}



//
// CabInsertWFInfo($log, $wf)
//
// If $wf contains a web form provided set of fields, map them into
// X-CQP-fieldname records and then insert all of these records before the
// first QSO record in the log.
//
// Returns the amended log as a multiline string.
//

function CabInsertWFInfo($log, $wf) {
  if (!$wf) {
    // Nothing to insert - return the log
    return $log;
  }

  // $wf contains one or more CQP Webform fields.  Technically there
  // should be at least two since there are two email fields that are
  // mandatory.
  // 
  // We have to handle the COMMENTS: field if present because it is
  // possibly a multi-line field

  // Split out the contests between the headers...
  preg_match("/^##CQP-WEBFORM-V10##\n(.*?)\n^##END-CQP-WEBFORM##.*$/ms",
             $wf, $m);

  // $m[1] should now contain all the fields...
  // Split off the comments field if present
  $xf = $m[1];

  if (preg_match("/(^.*?)\n(^COMMENTS:.*$)/ms", $m[1], $co)) {
    // $co[1] contains everything except the Comment field, $co[2]
    // the comments... add COMMENTS: to the start of each line
    // then re-build the webform contents for normal processing

    $col = explode("\n", $co[2]);
    $comment = $col[0] . "\n";
    for ($i=1; $i < count($col); $i++) {
      $comment .= "\nCOMMENTS: " . $col[$i];
    }

    $xf = $co[1] . "\n" . trim($comment);
  }

  $fs = explode("\n", $xf);
  $nfs = array();

  for ($i=0; $i < count($fs); $i++) {
    if ($fs[$i]) {
      $nfs[] = "X-CQP-" . $fs[$i];
    }
  }

  $nfs_as_s = implode("\n", $nfs) . "\n";

  // Now have a bunch of X-CQP- fields as a multiline string with a 
  // trailing newline

  // Find the first QSO: field at the start of a line and split the log
  // into two strings - everything before the first and everything after

  preg_match("/(^START-OF-LOG.*?\n)(^QSO:.*$)/ms", $log, $match);

  return($match[1] . $nfs_as_s . $match[2]);
}


// Support for XHDRCrack

$LINES = array();
$LN = 0;
$LNCNT = 0;
$LOG = '';


function setup_xhdr_getl($log) {
  global $LOG, $LINES, $LN, $LNCNT;

  // Copy the whole log for later retrieval
  $LOG = $log;

  // Split log into lines with no CR
  $lines = preg_replace("/\r/", '', $log);
  $LNCNT = preg_match_all("/(.*\n)/m", $lines, $LINES);
  $LN = 0;
}

function _xhdr_getl() {
  global $LINES, $LN, $LNCNT;

  if ($LN >= $LNCNT) {
    pd("    Premature EOF in _xhdr_get!");
    exit(1);
  }

  return($LINES[0][$LN++]);
}

function _xhdr_getlog() {
  global $LOG;
  return $LOG;
}


?>
