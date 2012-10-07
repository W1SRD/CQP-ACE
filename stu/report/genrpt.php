#!/usr/bin/php
<?php

// Generate the HTML file for the Logs received page for CQP
//

require_once('logrpt.inc.php');

// Set default timezone
date_default_timezone_set('UTC');

// Globals

$HEAD = file_get_contents("rpthead.html");
$HEAD = preg_replace("/Logs Received Page/", "Logs Received - " . gmdate("d-M-Y  H:i:s e"), $HEAD);


$BODY = '';
$TAIL = file_get_contents("rptend.html");

$ACEUSER = getenv('ACEUSER');
$ACEPASS = getenv('ACEPASS');

// Utility function

// pd - diag print
//
// Time stamped message with newline appended to STDOUT
//

function pd($l) {
  print(gmdate("d-M-Y_H:i:s") . " UTC: ". $l. "\n");
}


function convertCatPower($cat, $pwr) {
  $catpwr = '';

  switch ($cat) {
    case 'CHECK':
      $catpwr = 'CHK-';
      break;

    case 'SINGLE-OP':
      $catpwr = 'SO-';
      break;

    case 'MULTI-SINGLE':
      $catpwr = 'MS-';
      break;

    case 'MULTI-MULTI':
      $catpwr = 'MM-';
      break;
  }

  switch ($pwr) {
    case 'HIGH':
      $catpwr .= 'HP';
      break;

    case 'LOW':
      $catpwr .= 'LP';
      break;

    case 'QRP':
      $catpwr .= 'QRP';
      break;
  }

  return ($catpwr);
}
  
function ProcessTS($ts) {
  $utc = strtotime($ts);
  return (date("M-d H:i", strtotime($ts)));
}


// Open a datbase connection and get the log table entries

try {
  $logt = new CQPACE_RPT_LOG_TABLE($ACEUSER, $ACEPASS);

  $rows = new CQPACE_LOG_REPORT();
  $rows = $logt->log_row_select(array());


  // We have all the entries...  a little math..
  $nlogs = count($rows);
  $nte = (integer)(($nlogs + 1) / 2);

  for ($i = 0; $i < $nte && $nlogs != 0; $i++) {
    $col = $i;

    $BODY .= "<tr>\n";
      $BODY .= '<td bgcolor="#FFFFCC">' . 
               $rows[$col]->CALLSIGN . "</td>\n";

      $BODY .= '<td bgcolor="#FFFFCC">' . 
               convertCatPower($rows[$col]->OPERATOR_CATEGORY,
                               $rows[$col]->POWER_CATEGORY) .
               "</td>\n";

      $BODY .= '<td bgcolor="#FFFFCC">' . 
               $rows[$col]->STATION_LOCATION . "</td>\n";

      $BODY .= '<td bgcolor="#FFFFCC">' . 
               ProcessTS($rows[$col]->LAST_UPDATED) . "</td>\n";

      $BODY .= '<td width="10">' . "&nbsp;</td>\n";

    if (($nte + $i) >= $nlogs) {
      $BODY .= '<td bgcolor="#FFFFCC">' . "&nbsp;</td>\n";
      $BODY .= '<td bgcolor="#FFFFCC">' . "&nbsp;</td>\n";
      $BODY .= '<td bgcolor="#FFFFCC">' . "&nbsp;</td>\n";
      $BODY .= '<td bgcolor="#FFFFCC">' . "&nbsp;</td>\n";
    } else {
      $col = $nte + $i;

      $BODY .= '<td bgcolor="#FFFFCC">' .
               $rows[$col]->CALLSIGN . "</td>\n";

      $BODY .= '<td bgcolor="#FFFFCC">' .
               convertCatPower($rows[$col]->OPERATOR_CATEGORY,
                               $rows[$col]->POWER_CATEGORY) .
               "</td>\n";

      $BODY .= '<td bgcolor="#FFFFCC">' .
               $rows[$col]->STATION_LOCATION . "</td>\n";

      $BODY .= '<td bgcolor="#FFFFCC">' .
               ProcessTS($rows[$col]->LAST_UPDATED) . "</td>\n";
    }
    $BODY .= "</tr>\n";
  }

  file_put_contents("report.html", $HEAD . $BODY . $TAIL);
  pd("Report Generated");
  exit(0);

} catch (Exception $e) {
  pd("  ** GENERATION FAILED **");
  pd($e->getMessage());
  exit(1);
}


?>
