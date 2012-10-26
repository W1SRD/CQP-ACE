<?php
//=============================================================
// File name: report.php
// Begin:     2012-09-29
// 
// File to generate NCCC contest reports using TCPDF
// Copyright (C) 2012 Thomas Epperly ns6t@arrl.net
//
//=============================================================
// VALIDQSO should define all the requirements for a QSO to be considered
// valid for a multiplier or a QSO
require_once('report_html.php');
define("VALIDQSO","(QSO.QSO_STATUS = 'OK')");

// Calculate a report for this year's running of the CQP. This could
// be replaced by some other way of setting the year.
$thisyear = date("Y");		/* getting the current year */

$link = mysql_connect('localhost', 'dbtest', 'dbtest') or die("Connect not connect to database: " . mysql_error());
mysql_select_db('CQPACE_test', $link) or die("Could not select database");

// Create a temporary table to hold stats needed for the reports
mysql_query("create temporary table SummaryStats (LOG_ID int primary key, CACounties int, StatesAndProvinces int, Multipliers int, InState boolean, CWQSOs int, PHQSOs int, TotalScore int, TimeForAllMultipliers DATETIME)", $link)  or die("Cannot create SummaryStats: " . mysql_error());
// mysql_query("delete from SummaryStats") or die("Cannot delete");

// The purpose of this question is to select the logs that are:
// 1.  Part of the appropriate contest
// 2.  Part of this year's running
// 3.  Identify whether it's an in-state entry
$result = mysql_query("select LOG.ID, MULTIPLIER.TYPE = 'COUNTY' from LOG, MULTIPLIER where CONTEST_YEAR = " . $thisyear .
  " and CONTEST_NAME = 'CA-QSO-PARTY' and OPERATOR_CATEGORY <> 'CHECK' and LOG.STATION_LOCATION = MULTIPLIER.NAME", $link);


while ($line = mysql_fetch_row($result)) {
  mysql_query("insert INTO SummaryStats (LOG_ID, InState) VALUES (". $line[0] . ", " . $line[1] . ")", $link)   or die("Cannot insert into SummaryStats: " . mysql_error());
}

function CountMultipliers($multipliertest, $varname) {
  $result = mysql_query("select SummaryStats.LOG_ID, COUNT(distinct QSO.QTH_RECEIVED) FROM SummaryStats, LOG, QSO, MULTIPLIER where SummaryStats.LOG_ID = LOG.ID and QSO.LOG_ID = LOG.ID and MULTIPLIER.NAME = QSO.QTH_RECEIVED and " . $multipliertest . " and " . VALIDQSO . " group by SummaryStats.LOG_ID");
  while ($line = mysql_fetch_row($result)) {
    mysql_query("update SummaryStats set " . $varname . " = " . $line[1] . " where LOG_ID = " . $line[0] . " limit 1")   or die("Cannot change SummaryStats: " . mysql_error());
  }
  mysql_query("update SummaryStats set " . $varname . " = 0 where " . 
    $varname . " is null");
}

CountMultipliers("MULTIPLIER.TYPE = 'COUNTY'", "CACounties");
CountMultipliers("(MULTIPLIER.TYPE = 'STATE' or MULTIPLIER.TYPE = 'PROVINCE')",
		 "StatesAndProvinces");

// The state count does not include California yet, so add one if at least
// 1 CA county was worked.
mysql_query("update SummaryStats set StatesAndProvinces = StatesAndProvinces + 1 where CACounties > 0")    or die("Cannot fixed state count: " . mysql_error());

mysql_query("update SummaryStats set Multipliers = StatesAndProvinces where InState")    or die("Cannot calculate multipliers: " . mysql_error());
mysql_query("update SummaryStats set Multipliers = CACounties where not InState") or die("Cannot calculate multipliers: " . mysql_error());;

function GetModeCounts($mode) {
  $result = mysql_query("select SummaryStats.LOG_ID, COUNT(*) from SummaryStats, LOG, QSO where SummaryStats.LOG_ID = LOG.ID and LOG.ID = QSO.LOG_ID and QSO.MODE = '" . $mode . "' and " . VALIDQSO . " group by SummaryStats.LOG_ID");
  while ($line = mysql_fetch_row($result)) {
    mysql_query("update SummaryStats set " . $mode . "QSOs = " . $line[1] . " where LOG_ID = " . $line[0] . " limit 1");
  }
  mysql_query("update SummaryStats set " . $mode . "QSOs = 0 where " . $mode .
      "QSOs is null");
}

GetModeCounts("CW");
GetModeCounts("PH");

// Calculate the total score based on information in the table
mysql_query("update SummaryStats set TotalScore = Multipliers * (3*CWQSOs + 2*PHQSOs)") or die("Calculate total score failed:" .  mysql_error());


function BestTimeQuery($instatetest, $multipliertest) {
  // Calculate best time to 58 for class of stations
  $result = mysql_query("select SummaryStats.LOG_ID, QSO.QTH_RECEIVED, MIN(QSO.QSO_DATE) as DATE from SummaryStats, LOG, QSO, MULTIPLIER where SummaryStats.LOG_ID = LOG.ID and " . $instatetest . " and LOG.ID = QSO.LOG_ID and SummaryStats.Multipliers = 58 and " . VALIDQSO . " and QSO.QTH_RECEIVED = MULTIPLIER.NAME and " . $multipliertest . " GROUP BY SummaryStats.LOG_ID, QSO.QTH_RECEIVED ORDER BY SummaryStats.LOG_ID asc, DATE desc");

  $previd = -9999;

  while ($line = mysql_fetch_row($result)) {
    // because of the order by, the first line for each id is the last
    // multiplier to be found
    if ($line[0] != $previd) {
      $previd = $line[0];
mysql_query("update SummaryStats set TimeForAllMultipliers = '" . $line[2] . "' where LOG_ID = " . $line[0] . " limit 1") or die("Unable to update" . mysql_error());
    }
  }
}

BestTimeQuery("SummaryStats.InState",
	      "(MULTIPLIER.TYPE = 'STATE' or MULTIPLIER.TYPE = 'PROVINCE')");
BestTimeQuery("NOT SummaryStats.InState",
	      "MULTIPLIER.TYPE = 'COUNTY'");

function EntryClassStr($line) {
  $ecs = "";
  if (strcmp($line[1], "MULTI-MULTI") == 0) {
    $ecs = "M/M";
  }
  else if (strcmp($line[1], "MULTI-SINGLE") == 0) {
    $ecs = "M/S";
  }
  /* else if (strcmp($line[1], "SINGLE-OP") == 0) { */
  /*   $ecs = "S"; */
  /* } */
  /* else if (strcmp($line[1], "CHECK") == 0) { */
  /*   $ecs = "C"; */
  /* } */
  if (strcmp($line[7],"LOW") == 0) {
    $ecs = $ecs . " L";
  }
  else if (strcmp($line[7], "QRP") == 0) {
    $ecs = $ecs . " Q";
  }
  if (strcmp($line[9], "CCE") == 0) {
    $ecs = $ecs . " E";
  }
  if ($line[10] != 0) {
    $ecs = $ecs . " YL";
  }
  return trim($ecs);
}

define("ENTRY_QUERY_STRING", "select LOG.STATION_LOCATION, LOG.OPERATOR_CATEGORY, TotalScore, LOG.CALLSIGN, CWQSOs, PHQSOs, Multipliers, POWER_CATEGORY, MULTIPLIER.DESCRIPTION, STATION_CATEGORY, OVERLAY_YL, LOG.ID, TimeForAllMultipliers, STATION_OWNER_CALLSIGN");

function EntryFromRow($row)
{
  $operators = array();
  $opquery = mysql_query("select OPERATOR.CALLSIGN from OPERATOR where LOG_ID = " . $row[11]);
  while ($opline = mysql_fetch_row($opquery)) {
    $operators[] = $opline[0];
  }
  $ent = new Entry($row[3], $operators, intval($row[4]), intval($row[5]),
		   intval($row[6]), intval($row[2]), EntryClassStr($row), 
		   $row[0], $row[8]);
  if (isset($row[13])) {
    $ent->SetStationCall($row[13]);
  }
  return $ent;
}


$cats = array();
$prevcat = '';
$res = mysql_query(ENTRY_QUERY_STRING . " from SummaryStats, LOG, MULTIPLIER where LOG.ID = SummaryStats.LOG_ID and MULTIPLIER.NAME = LOG.STATION_LOCATION and MULTIPLIER.TYPE = 'COUNTY' order by LOG.STATION_LOCATION asc, LOG.OPERATOR_CATEGORY desc, TotalScore desc");
if ($res) {
  while ($line = mysql_fetch_row($res)) {
    if (strcmp($line[0], $prevcat)) {
      if (isset($cat)) {
	$cats[] = $cat;
      }
      $prevcat = $line[0];
      $cat = new EntryCategory($line[8]);
    }
    $ent = EntryFromRow($line);
    $cat->AddEntry($ent);
  }
  if (isset($cat)) {
    $cats[] = $cat;
    unset($cat);
  }
  $pdf = new NCCCReportPDF($thisyear . " California QSO Party (CQP) \xe2\x80\x93  US Draft Results (CA)");
  $pdf->ReportCategories($cats);
  $pdf->Output("CA_report_draft.pdf", "F");
}
else {
  print "Report query failed: " . mysql_error() . "\n";
}

$cats = array();
$prevcat = '';
$res = mysql_query(ENTRY_QUERY_STRING . " from SummaryStats, LOG, MULTIPLIER where LOG.ID = SummaryStats.LOG_ID and MULTIPLIER.NAME = LOG.STATION_LOCATION and MULTIPLIER.TYPE = 'STATE' order by MULTIPLIER.DESCRIPTION asc, LOG.OPERATOR_CATEGORY desc, TotalScore desc");
if ($res) {
  while ($line = mysql_fetch_row($res)) {
    if (strcmp($line[0], $prevcat)) {
      if (isset($cat)) {
	$cats[] = $cat;
      }
      $prevcat = $line[0];
      $cat = new EntryCategory($line[8]);
    }
    $ent = EntryFromRow($line);
    $cat->AddEntry($ent);
  }
  if (isset($cat)) {
    $cats[] = $cat;
    unset($cat);
  }
  $pdf = new NCCCReportPDF($thisyear . " California QSO Party (CQP) \xe2\x80\x93  US Draft Results (US)");
  $pdf->ReportCategories($cats);
  $pdf->Output("US_report_draft.pdf", "F");
}

$cats = array();
$prevcat = '';
$res = mysql_query(ENTRY_QUERY_STRING . " from SummaryStats, LOG, MULTIPLIER where LOG.ID = SummaryStats.LOG_ID and MULTIPLIER.NAME = LOG.STATION_LOCATION and MULTIPLIER.TYPE = 'PROVINCE' order by MULTIPLIER.DESCRIPTION  asc, LOG.OPERATOR_CATEGORY desc, TotalScore desc");
if ($res) {
  while ($line = mysql_fetch_row($res)) {
    if (strcmp($line[0], $prevcat)) {
      if (isset($cat)) {
	$cats[] = $cat;
      }
      $prevcat = $line[0];
      $cat = new EntryCategory($line[8]);
    }
    $ent = EntryFromRow($line);
    $cat->AddEntry($ent);
  }
  if (isset($cat)) {
    $cats[] = $cat;
    unset($cat);
  }
  $pdf = new NCCCReportPDF($thisyear . " California QSO Party (CQP) \xe2\x80\x93  Canadian Draft Results");
  $pdf->ReportCategories($cats);
  $pdf->Output("Canadian_report_draft.pdf", "F");
}

$cats = array();
$prevcat = '';
$res = mysql_query(ENTRY_QUERY_STRING . " from SummaryStats, LOG, MULTIPLIER where LOG.ID = SummaryStats.LOG_ID and MULTIPLIER.NAME = LOG.STATION_LOCATION and MULTIPLIER.TYPE = 'Country' order by MULTIPLIER.DESCRIPTION asc, LOG.OPERATOR_CATEGORY desc, TotalScore desc");
if ($res) {
  while ($line = mysql_fetch_row($res)) {
    if (strcmp($line[0], $prevcat)) {
      if (isset($cat)) {
	$cats[] = $cat;
      }
      $prevcat = $line[0];
      $cat = new EntryCategory($line[8]);
    }
    $ent = EntryFromRow($line);
    $cat->AddEntry($ent);
  }
  if (isset($cat)) {
    $cats[] = $cat;
    unset($cat);
  }
  $pdf = new NCCCReportPDF($thisyear . " California QSO Party (CQP) \xe2\x80\x93  DX Draft Results");
  $pdf->ReportCategories($cats);
  $pdf->Output("DX_report_draft.pdf", "F");
}

require_once('summary_lib.php');

function QuerySummaryCat(&$cat, $querystr)
{
  $res = mysql_query(ENTRY_QUERY_STRING . " from SummaryStats, LOG, MULTIPLIER where LOG.ID = SummaryStats.LOG_ID and MULTIPLIER.NAME = LOG.STATION_LOCATION " . $querystr);
  if ($res) {
    while ($line = mysql_fetch_row($res)) {
      $cat->AddEntry(EntryFromRow($line));
    }
  }
  else {
    print "Mysql error: " . mysql_error() . "\n";
  }
  return $cat;
}


$cats = array();
$cat = new EntryCategory("TOP 3 Single-Op", array(), true, "California");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY='SINGLE-OP' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 3");
$cat = new EntryCategory("TOP 3 Single-Op", array(), true, "Non-California");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE<>'County' and OPERATOR_CATEGORY='SINGLE-OP' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 3");
$cat = new EntryCategory("TOP Multi-Single", array(), false, "CA and Non-CA");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY='MULTI-SINGLE' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");
QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE<>'County' and OPERATOR_CATEGORY='MULTI-SINGLE' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");
$cat = new EntryCategory("TOP Multi-Multi", array(), false, "California");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY='MULTI-MULTI' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");

$cat = new EntryCategory("TOP 2 Single-Op Expeditions, California", array(), false, "");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY='SINGLE-OP' and LOG.STATION_CATEGORY='CCE' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 2");
$cat = new EntryCategory("TOP Multi-Single Expedition, California", array(), false, "");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY='MULTI-SINGLE' and LOG.STATION_CATEGORY='CCE' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");
$cat = new EntryCategory("TOP Multi-Multi Expedition, California", array(), false, "");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY='MULTI-MULTI' and LOG.STATION_CATEGORY='CCE' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");
$cat = new EntryCategory("TOP 3 Single-Op Low Power, Non-California", array(), true, "");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE<>'County' and OPERATOR_CATEGORY='SINGLE-OP' and POWER_CATEGORY='LOW' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 3");

$cat = new EntryCategory("TOP Single-Op QRP", array(), false, "CA and Non-CA");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY='SINGLE-OP' and POWER_CATEGORY='QRP' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");
QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE<>'County' and OPERATOR_CATEGORY='SINGLE-OP' and POWER_CATEGORY='QRP' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");

$cat = new EntryCategory("TOP Single-Op YL", array(), false, "CA and Non-CA");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY='SINGLE-OP' and OVERLAY_YL ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");
QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE<>'County' and OPERATOR_CATEGORY='SINGLE-OP' and OVERLAY_YL ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");

$cat = new EntryCategory("TOP Single-Op Youth", array(), false, "CA and Non-CA");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY='SINGLE-OP' and OVERLAY_YOUTH ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");
QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE<>'County' and OPERATOR_CATEGORY='SINGLE-OP' and OVERLAY_YOUTH ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");

$cat = new EntryCategory("TOP DX", array(), false, "");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='Country' and OPERATOR_CATEGORY='SINGLE-OP' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");

$cat = new EntryCategory("TOP Schools", array(), false, "CA and Non-CA");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and STATION_CATEGORY='SCHOOL' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");
QuerySummaryCat($cat,
		" and MULTIPLIER.TYPE<>'County' and STATION_CATEGORY='SCHOOL' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");

$cat = new EntryCategory("TOP New Contester", array(), false, "California");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY='SINGLE-OP' and OVERLAY_NEW_CONTESTER ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");

$mostssb = new EntryCategory("Single-Op - Most SSB", array(), false, "");
QuerySummaryCat($mostssb,
		" and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY='SINGLE-OP'  ORDER BY PHQSOs desc, LOG.CALLSIGN asc LIMIT 1");
QuerySummaryCat($mostssb,
		" and MULTIPLIER.TYPE<>'County' and OPERATOR_CATEGORY='SINGLE-OP'  ORDER BY PHQSOs desc, LOG.CALLSIGN asc LIMIT 1");

$mostcw = new EntryCategory("Single-Op - Most CW", array(), false, "");
QuerySummaryCat($mostcw,
		" and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY='SINGLE-OP'  ORDER BY CWQSOs desc, LOG.CALLSIGN asc LIMIT 1");
QuerySummaryCat($mostcw,
		" and MULTIPLIER.TYPE<>'County' and OPERATOR_CATEGORY='SINGLE-OP'  ORDER BY CWQSOs desc, LOG.CALLSIGN asc LIMIT 1");

$topca = new EntryCategory("California", array(), true);
QuerySummaryCat($topca,
		" and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY='SINGLE-OP' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 20");

$topnonca = new EntryCategory("Non-California", array(), true);
QuerySummaryCat($topnonca,
		" and MULTIPLIER.TYPE<>'County' and OPERATOR_CATEGORY='SINGLE-OP' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 20");

$pdf = new NCCCSummaryPDF("9999 California QSO Party (CQP) - Draft Summary Report");
$pdf->LeftColumn($cats, NULL, $mostssb->GetEntries(), $mostcw->GetEntries());
$pdf->RightColumn($topca, $topnonca, array(), array());

$pdf->Output("summary_draft.pdf", "F");
// At this point, the intent is that every element of SummaryStats has its
// correct value.

mysql_close($link);

