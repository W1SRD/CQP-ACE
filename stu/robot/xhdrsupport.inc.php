<?php


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
