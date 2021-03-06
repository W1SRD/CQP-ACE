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
define("VALIDQSO", "(QSO.GREEN_SCORE in ('OK', 'D1', 'BYE'))");
date_default_timezone_set("Europe/London");

// Calculate a report for this year's running of the CQP. This could
// be replaced by some other way of setting the year.
$thisyear = 2015;
$reportready = "draft";
$reportname = "DRAFT ";   // or empty string
// $reportname = "";

$link = mysql_connect('localhost', 'dbtest', 'dbtest') or die("Connect not connect to database: " . mysql_error());
mysql_select_db('CQPACE', $link) or die("Could not select database");

// Create a temporary table to hold stats needed for the reports
mysql_query("create temporary table SummaryStats (LOG_ID int, LOCATION VARCHAR(4), CACounties int, StatesAndProvinces int, Multipliers int, InState boolean, CWQSOs DECIMAL(10,1), PHQSOs DECIMAL(10,1), TotalScore DECIMAL(10,1), TimeForAllMultipliers DATETIME, VEProvince VARCHAR(32), PRIMARY KEY (LOG_ID, LOCATION))", $link)  or die("Cannot create SummaryStats: " . mysql_error());
// mysql_query("delete from SummaryStats") or die("Cannot delete");

// The purpose of this question is to select the logs that are:
// 1.  Part of the appropriate contest
// 2.  Part of this year's running
// 3.  Identify whether it's an in-state entry
$result = mysql_query("select distinct LOG.ID, SCORE.QTH, MULTIPLIER.TYPE = 'COUNTY', LOG.CALLSIGN, MULTIPLIER.DESCRIPTION from LOG, MULTIPLIER, SCORE where CONTEST_YEAR = " . $thisyear .
  " and CONTEST_NAME = 'CA-QSO-PARTY' and SCORE.QTH = MULTIPLIER.NAME and SCORE.LOG_ID = LOG.ID", $link);

function CanadaProvince($province, $callsign)
{
    if (strcmp($province, "Maritimes") == 0) {
        $prefix = substr($callsign, 0, 3);
        if (strcmp($prefix, "VY2") == 0) {
            return "Prince Edward Island (Maritimes)";
        } elseif (strcmp($prefix, "VE9") == 0) {
            return "New Brunswick (Maritimes)";
        } elseif (strcmp($prefix, "VO1") == 0) {
            return "Newfoundland (Maritimes)";
        } elseif (strcmp($prefix, "VO2") == 0) {
            return "Labrador (Maritimes)";
        } elseif ((strcmp($prefix, "VA1") == 0) || (strcmp($prefix, "VE1") == 0)) {
            return "Nova Scotia (Maritimes)";
        }
        else {
            return "Unknown";
        }
    }
    else {
        return $province;
    }
}

if ($result) {
  while ($line = mysql_fetch_row($result)) {
      mysql_query("insert INTO SummaryStats (LOG_ID, LOCATION, InState, VEProvince) VALUES (". $line[0] . ", \"" . $line[1] . "\", " . $line[2] . ", \"" . CanadaProvince($line[4], $line[3]) . "\")", $link)   or die("Cannot insert into SummaryStats: " . mysql_error());
  }
}
else {
  print "Query failed: " . mysql_error() . "\n";
}


// The state count does not include California yet, so add one if at least
// 1 CA county was worked.
// mysql_query("update SummaryStats set StatesAndProvinces = StatesAndProvinces + 1 where CACounties > 0")    or die("Cannot fixed state count: " . mysql_error());
//mysql_query("update SummaryStats set Multipliers = StatesAndProvinces where InState")    or die("Cannot calculate multipliers: " . mysql_error());
//mysql_query("update SummaryStats set Multipliers = CACounties where not InState") or die("Cannot calculate multipliers: " . mysql_error());;

$res = mysql_query("select SummaryStats.LOG_ID, LOCATION, CLAIMED_CW_Q - D2_CW - 0.5 * D1_CW as CW_Q, CLAIMED_PH_Q - D2_PH - 0.5 * D1_PH, CHECKED_SCORE, CHECKED_MULT from SummaryStats, SCORE where SCORE.LOG_ID = SummaryStats.LOG_ID and SCORE.QTH = LOCATION") or die("Cannot query SCORE: " . mysql_error());
if ($res) {
  while ($line = mysql_fetch_row($res)) {
    mysql_query("update SummaryStats set Multipliers = " . $line[5] . ", CWQSOs = " . $line[2] . ", PHQSOs = " . $line[3] . ", TotalScore = " . $line[4] . " where LOG_ID = " . $line[0] . " and LOCATION = \"" . $line[1] . "\" limit 1") or die("Unable to update SCORE table: " . mysql_error());
  }
}

$res = mysql_query("select SummaryStats.LOG_ID, LOCATION, T2_58 from SummaryStats, SCORE where SCORE.LOG_ID = SummaryStats.LOG_ID and SCORE.QTH = LOCATION and T2_58 is not NULL") or die("Cannot query SCORE: " . mysql_error());
if ($res) {
  while ($line = mysql_fetch_row($res)) {
    mysql_query("update SummaryStats set TimeForAllMultipliers = \"" . $line[2] . "\" where LOG_ID = " . $line[0] . " and LOCATION = \"" . $line[1] . "\" limit 1");
  }
}



function EntryClassStr($line) {
  $ecs = "";
  if (strcmp($line[1], "MULTI-MULTI") == 0) {
    $ecs = "M/M";
  }
  else if (strcmp($line[1], "MULTI-SINGLE") == 0) {
    $ecs = "M/S";
  }
  else if (strcmp($line[1], "SINGLE-OP") == 0) {
     $ecs = "SO";
  }
  else if (strcmp($line[1], "SINGLE-OP-ASSIST") == 0) {
     $ecs = "SOA";
  }
  else if (strcmp($line[1], "CHECK") == 0) {
     $ecs = "C"; 
  }
  if (strcmp($line[7],"LOW") == 0) {
    $ecs = $ecs . " L";
  }
  else if (strcmp($line[7], "QRP") == 0) {
    $ecs = $ecs . " Q";
  }
  if (strcmp($line[9], "CCE") == 0) {
    $ecs = $ecs . " E";
  }
  if (strcmp($line[9], "MOBILE") == 0) {
    $ecs = $ecs . " M";
  }
  if ($line[10] != 0) {
    $ecs = $ecs . " YL";
  }
  return trim($ecs);
}

define("ENTRY_QUERY_STRING", "select SummaryStats.LOCATION, LOG.OPERATOR_CATEGORY, TotalScore, LOG.CALLSIGN, CWQSOs, PHQSOs, Multipliers, POWER_CATEGORY, MULTIPLIER.DESCRIPTION, STATION_CATEGORY, OVERLAY_YL, LOG.ID, TimeForAllMultipliers, STATION_OWNER_CALLSIGN, MULTIPLIER.NAME");

function NewRegionalRecord($id, $loc)
{
  $regquery = mysql_query("select REGIONAL.ID from LOG, MULTIPLIER, REGIONAL where LOG.ID=" . $id . " and LOG.OPERATOR_CATEGORY <> 'CHECK' and REGIONAL.LOG_ID = LOG.ID and REGIONAL.MULT_ID = MULTIPLIER.ID and MULTIPLIER.NAME = '" . $loc . "' limit 1");
  if ($regquery) {
    while ($line = mysql_fetch_row($regquery)) {
      return true;
    }
  }
  return false;
}

function RegionalClass($opclass, $power)
{
  if (strcmp($opclass, "SINGLE-OP") == 0) {
    $result = "SO ";
  }
  else if (strcmp($opclass, "SINGLE-OP-ASSIST") == 0) {
    $result = "SOA ";
  }
  else if (strcmp($opclass, "MULTI-SINGLE") == 0) {
    $result = "M/S ";
  }
  else {
    $result = "M/M ";
  }
  if (strcmp($power, "LOW") == 0) {
    $result = $result . "LP";
  }
  else if (strcmp($power, "HIGH") == 0) {
    $result = $result . "HP";
  }
  else {
    $result = $result . "QRP";
  }
  return $result;
}

function CheckRecords(&$ent, &$row)
{
  $newrecord = false;
  if (NewRegionalRecord($row[11], $row[14])) {
    $ent->AddFootnote(RegionalClass($row[1], $row[7]) . " " . $row[8]);
    $newrecord = true;
  }
  $res = mysql_query("select NAME from SPECIAL where LOG_ID = " . 
		    $row[11]);
  while ($line = mysql_fetch_row($res)) {
    $ent->AddFootnote($line[0]);
    $newrecord = true;
  }
  if ($newrecord) {
    $ent->SetNewRecord();
  }
}


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
  if (isset($row[12])) {
    $ent->SetAllMultipliers(new DateTime($row[12]));
  }
  CheckRecords($ent, $row);
  return $ent;
}

function GetCheckLogs($year, $reporttype)
{
  $checklogs = array();
  $chklog = mysql_query("select LOG.CALLSIGN, LOG.STATION_OWNER_CALLSIGN from MULTIPLIER, LOG left outer join SCORE on LOG.ID = SCORE.LOG_ID where SCORE.LOG_ID is NULL and LOG.OPERATOR_CATEGORY = 'CHECK' and LOG.STATION_LOCATION = MULTIPLIER.NAME and LOG.CONTEST_YEAR = " . $year . " and (" . 
			$reporttype . ") order by LOG.CALLSIGN asc");
  if ($chklog) {
    while ($ckline = mysql_fetch_row($chklog)) {
        if ($ckline[1]) {
            $checklogs[] = $ckline[0] . " (@" . $ckline[1] . ")";
        }
        else {
            $checklogs[] = $ckline[0];
        }
    }
  }
  else {
    print "Report query failed: " . mysql_error() . "\n";
  }
  return $checklogs;
}



$cats = array();
$prevcat = '';
$res = mysql_query(ENTRY_QUERY_STRING . " from SummaryStats, LOG, MULTIPLIER where LOG.ID = SummaryStats.LOG_ID  and MULTIPLIER.NAME = SummaryStats.LOCATION and MULTIPLIER.TYPE = 'COUNTY' order by MULTIPLIER.DESCRIPTION asc, LOG.OPERATOR_CATEGORY desc, TotalScore desc");
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
  $pdf = new NCCCReportPDF($thisyear . " California QSO Party (CQP)\n" . $reportname . "US Results (CA)");
  $pdf->ReportCategories($cats, GetCheckLogs($thisyear, "MULTIPLIER.TYPE = 'COUNTY'"));
  $pdf->Output("CA_report_" . $reportready . ".pdf", "F");
}
else {
  print "Report query failed: " . mysql_error() . "\n";
}

$cats = array();
$prevcat = '';
$res = mysql_query(ENTRY_QUERY_STRING . " from SummaryStats, LOG, MULTIPLIER where LOG.ID = SummaryStats.LOG_ID and MULTIPLIER.NAME = SummaryStats.LOCATION and MULTIPLIER.TYPE = 'STATE' order by MULTIPLIER.DESCRIPTION asc, LOG.OPERATOR_CATEGORY desc, TotalScore desc");
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
  $pdf = new NCCCReportPDF($thisyear . " California QSO Party (CQP)\n" . $reportname . "US Results (US)");
  $pdf->ReportCategories($cats, GetCheckLogs($thisyear, "MULTIPLIER.TYPE = 'STATE'"));
  $pdf->Output("US_report_" . $reportready . ".pdf", "F");
}


$cats = array();
$prevcat = '';
$res = mysql_query(ENTRY_QUERY_STRING . ", VEProvince from SummaryStats, LOG, MULTIPLIER where LOG.ID = SummaryStats.LOG_ID and MULTIPLIER.NAME = SummaryStats.LOCATION and MULTIPLIER.TYPE = 'PROVINCE' order by VEProvince  asc, LOG.OPERATOR_CATEGORY desc, TotalScore desc");
if ($res) {
  while ($line = mysql_fetch_row($res)) {
    if (strcmp($line[15], $prevcat)) {
      if (isset($cat)) {
	$cats[] = $cat;
      }
      $prevcat = $line[15];
      $cat = new EntryCategory($line[15]);
    }
    $ent = EntryFromRow($line);
    $cat->AddEntry($ent);
  }
  if (isset($cat)) {
    $cats[] = $cat;
    unset($cat);
  }
  $pdf = new NCCCReportPDF($thisyear . " California QSO Party (CQP)\n" . $reportname . "Canadian Results");
  $pdf->ReportCategories($cats, GetCheckLogs($thisyear, "MULTIPLIER.TYPE = 'PROVINCE'"));
  $pdf->Output("Canadian_report_" . $reportready . ".pdf", "F");
}

$cats = array();
$prevcat = '';
$res = mysql_query(ENTRY_QUERY_STRING . ", CONTINENT from SummaryStats, LOG, MULTIPLIER, ENTITY where LOG.ID = SummaryStats.LOG_ID and MULTIPLIER.NAME = SummaryStats.LOCATION and MULTIPLIER.TYPE = 'Country' and LOG.ENTITY=ENTITY.ID order by CONTINENT asc, LOG.OPERATOR_CATEGORY desc, TotalScore desc");
if ($res) {
  while ($line = mysql_fetch_row($res)) {
    if (strcmp($line[15], $prevcat)) {
      if (isset($cat)) {
	$cats[] = $cat;
      }
      $prevcat = $line[15];
      $cat = new EntryCategory($line[15]);
    }
    $ent = EntryFromRow($line);
    $cat->AddEntry($ent);
  }
  if (isset($cat)) {
    $cats[] = $cat;
    unset($cat);
  }
  $pdf = new NCCCReportPDF($thisyear . " California QSO Party (CQP)\n" . $reportname . "DX Results");
  $pdf->ReportCategories($cats, GetCheckLogs($thisyear, "MULTIPLIER.TYPE = 'COUNTRY'"));
  $pdf->Output("DX_report_" . $reportready . ".pdf", "F");
}

require_once('summary_lib.php');

function QuerySummaryCat(&$cat, $querystr)
{
  $res = mysql_query(ENTRY_QUERY_STRING . " from SummaryStats, LOG, MULTIPLIER where LOG.ID = SummaryStats.LOG_ID and OPERATOR_CATEGORY <> 'CHECK' and MULTIPLIER.NAME = SummaryStats.LOCATION " . $querystr);
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

function QueryBestMobile()
{
  $mobile=NULL;
  $res = mysql_query(ENTRY_QUERY_STRING . ", SUM(SummaryStats.PHQSOs + SummaryStats.CWQSOs) as TotalQSO from SummaryStats, LOG, MULTIPLIER where LOG.ID = SummaryStats.LOG_ID and OPERATOR_CATEGORY <> 'CHECK' and SummaryStats.LOCATION = MULTIPLIER.NAME and LOG.STATION_CATEGORY='MOBILE' group by LOG.CALLSIGN order by TotalQSO desc limit 1");
  if ($res) {
    while ($line = mysql_fetch_row($res)) {
      $ent = EntryFromRow($line);
      $ncresult = mysql_query("select count(*) from SummaryStats where SummaryStats.LOG_ID = " . $line[11]);
      $numcounty = mysql_fetch_row($ncresult);
      $mobile = new MobileEntry($ent, intval($line[15]), intval($numcounty[0]));
    }
  }
  else {
    print "Mysql error: " . mysql_error() . "\n";
  }
  return $mobile;
}

function QueryBestTimes($instate)
{
  $res = mysql_query(ENTRY_QUERY_STRING . " from SummaryStats, LOG, MULTIPLIER where LOG.ID = SummaryStats.LOG_ID and TimeForAllMultipliers is not NULL and LOG.OPERATOR_CATEGORY in ('SINGLE-OP', 'SINGLE-OP-ASSIST') and SummaryStats.LOCATION = MULTIPLIER.NAME and " . $instate . " order by TimeForAllMultipliers asc limit 1");
  if ($res) {
    while ($line = mysql_fetch_row($res)) {
      $ent = EntryFromRow($line);
      return $ent;
    }
  }
  else {
    print "Mysql error: " . mysql_error() . "\n";
  }
}

function CalculateBestTimes()
{
  $result = array();
  $ent = QueryBestTimes("MULTIPLIER.TYPE = 'COUNTY'");
  if ($ent) {
    $result[] = $ent;
  }
  $ent =  QueryBestTimes("MULTIPLIER.TYPE <> 'COUNTY'");
  if ($ent) {
    $result[] = $ent;
  } 
  return $result;
}

function ParseClubResults($res, &$clubs)
{
  if ($res) {
    while ($line = mysql_fetch_row($res)) {
        $club = new Club($line[1], intval($line[3]), $line[2], 0);
        // strcmp($line[4], "CA") == 0); // GROSS HACK!!!!
      $clubs[] = $club;
    }
  }
  else {
    die("Club query failed: " . mysql_error());
  }
}

function QueryClubs($extraconst, $limit) 
{
  return mysql_query("select CLUB.ID, CLUB.NAME, ROUND(SUM(CHECKED_SCORE*CLUB_ALLOCATION)) as SCORE, COUNT(DISTINCT LOG.ID), CLUB.LOCATION from CLUB, OPERATOR, LOG, SummaryStats, SCORE where CLUB.ID = OPERATOR.CLUB_ID and SummaryStats.LOG_ID=LOG.ID and OPERATOR.LOG_ID = LOG.ID and OPERATOR_CATEGORY <> 'CHECK' and LOG.ID = SCORE.LOG_ID and CLUB.ELIGIBLE " . $extraconst . " GROUP BY CLUB.ID ORDER BY SCORE DESC" . $limit);
}

function CalculateBestClubs()
{
  $result = array();
  $res = QueryClubs("and CLUB.LOCATION=\"CA\"", " limit 1");
  ParseClubResults($res, $result);
  $res = QueryClubs("and CLUB.LOCATION=\"OCA\"", " limit 1");
  ParseClubResults($res, $result);
  return $result;
  
}

function FindCountry($ent) {
  $res = mysql_query("select ENTITY.NAME from ENTITY, LOG where ENTITY.ID=LOG.ENTITY and LOG.CALLSIGN = \"" . $ent->GetCallsign() . "\" and STATION_LOCATION=\"" . $ent->GetQTH() . "\" limit 1");
  if ($res and ($line = mysql_fetch_row($res))) {
    $ent->SetLocation($line[0]);
  }
}

function ReplaceLocWithCountry($cat) {
  foreach ($cat->GetEntries() as $ent) {
    FindCountry($ent);
  }
}

$caclubs = array();
ParseClubResults(QueryClubs("and CLUB.LOCATION=\"CA\"", " limit 3"), $caclubs);
$ocaclubs = array();
ParseClubResults(QueryClubs("and CLUB.LOCATION=\"OCA\"", " limit 3"), $ocaclubs);
$pdf = new NCCCReportPDF($thisyear . " California QSO Party (CQP)\n" . $reportname . "Club Results");
$pdf->ReportClubs($caclubs, "California Clubs");
$pdf->ReportClubs($ocaclubs, "Non-California Clubs");
$pdf->Output("Club_report_" . $reportready . ".pdf", "F");


$cats = array();
$cat = new EntryCategory("TOP 3 Single-Op High Power", array(), true, "California", "all time high CA");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY='SINGLE-OP' and POWER_CATEGORY='HIGH' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 3");
$cat = new EntryCategory("TOP 3 Single-Op High Power", array(), true, "Non-California", "all time high non-CA");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE<>'County' and OPERATOR_CATEGORY='SINGLE-OP' and POWER_CATEGORY='HIGH' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 3");
$cat = new EntryCategory("TOP Multi-Single", array(), false, "CA and Non-CA", "multi-single");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY='MULTI-SINGLE' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");
QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE<>'County' and OPERATOR_CATEGORY='MULTI-SINGLE' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");
$cat = new EntryCategory("TOP Multi-Multi", array(), false, "California", "multi-multi");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY='MULTI-MULTI' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");

$cat = new EntryCategory("TOP 2 Single-Op Expeditions, California", array(), false, "", "S/O expedition");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY in ('SINGLE-OP','SINGLE-OP-ASSIST') and LOG.STATION_CATEGORY='CCE' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 2");
$cat = new EntryCategory("TOP Multi-Single Expedition, California", array(), false, "", "M/S expedition");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY='MULTI-SINGLE' and LOG.STATION_CATEGORY='CCE' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");
$cat = new EntryCategory("TOP Multi-Multi Expedition, California", array(), false, "", "M/M expedition");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY='MULTI-MULTI' and LOG.STATION_CATEGORY='CCE' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");
$cat = new EntryCategory("TOP 3 Single-Op Low Power, California", array(), true, "", "low power CA");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY='SINGLE-OP' and POWER_CATEGORY='LOW' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 3");
$cat = new EntryCategory("TOP 3 Single-Op Low Power, Non-California", array(), true, "", "low power");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE<>'County' and OPERATOR_CATEGORY='SINGLE-OP' and POWER_CATEGORY='LOW' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 3");

$cat = new EntryCategory("TOP Single-Op QRP", array(), false, "CA and Non-CA", "QRP");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY='SINGLE-OP' and POWER_CATEGORY='QRP' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");
QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE<>'County' and OPERATOR_CATEGORY='SINGLE-OP' and POWER_CATEGORY='QRP' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");

$cat = new EntryCategory("TOP Single-Op YL", array(), false, "CA and Non-CA", "YL");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY in ('SINGLE-OP', 'SINGLE-OP-ASSIST') and OVERLAY_YL ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");
QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE<>'County' and OPERATOR_CATEGORY in ('SINGLE-OP', 'SINGLE-OP-ASSIST') and OVERLAY_YL ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");

$cat = new EntryCategory("TOP Single-Op Youth", array(), false, "CA and Non-CA", "single-op youth");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY in ('SINGLE-OP', 'SINGLE-OP-ASSIST') and OVERLAY_YOUTH ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");
QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE<>'County' and OPERATOR_CATEGORY in ('SINGLE-OP', 'SINGLE-OP-ASSIST') and OVERLAY_YOUTH ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");

$cat = new EntryCategory("TOP DX", array(), false, "");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='Country' and OPERATOR_CATEGORY in ('SINGLE-OP', 'SINGLE-OP-ASSIST') ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");
ReplaceLocWithCountry($cat);

$cat = new EntryCategory("TOP Schools", array(), false, "CA and Non-CA", "school");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and STATION_CATEGORY='SCHOOL' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");
QuerySummaryCat($cat,
		" and MULTIPLIER.TYPE<>'County' and STATION_CATEGORY='SCHOOL' ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");

$cat = new EntryCategory("TOP New Contester", array(), false, "California");
$cats[] = QuerySummaryCat($cat,
			  " and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY in ('SINGLE-OP','SINGLE-OP-ASSIST') and OVERLAY_NEW_CONTESTER ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 1");

$mostssb = new EntryCategory("Single-Op - Most SSB", array(), false, "", "most SSB QSOs");
QuerySummaryCat($mostssb,
		" and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY in ('SINGLE-OP', 'SINGLE-OP-ASSIST')  ORDER BY PHQSOs desc, LOG.CALLSIGN asc LIMIT 1");
QuerySummaryCat($mostssb,
		" and MULTIPLIER.TYPE<>'County' and OPERATOR_CATEGORY in ('SINGLE-OP', 'SINGLE-OP-ASSIST')  ORDER BY PHQSOs desc, LOG.CALLSIGN asc LIMIT 1");

$mostcw = new EntryCategory("Single-Op - Most CW", array(), false, "", "most CW QSOs");
QuerySummaryCat($mostcw,
		" and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY in ('SINGLE-OP', 'SINGLE-OP-ASSIST')  ORDER BY CWQSOs desc, LOG.CALLSIGN asc LIMIT 1");
QuerySummaryCat($mostcw,
		" and MULTIPLIER.TYPE<>'County' and OPERATOR_CATEGORY in ('SINGLE-OP', 'SINGLE-OP-ASSIST')  ORDER BY CWQSOs desc, LOG.CALLSIGN asc LIMIT 1");

$topca = new EntryCategory("California", array(), true);
QuerySummaryCat($topca,
		" and MULTIPLIER.TYPE='County' and OPERATOR_CATEGORY IN ('SINGLE-OP', 'SINGLE-OP-ASSIST') ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 20");

$topnonca = new EntryCategory("Non-California", array(), true);
QuerySummaryCat($topnonca,
		" and MULTIPLIER.TYPE<>'County' and OPERATOR_CATEGORY IN ('SINGLE-OP', 'SINGLE-OP-ASSIST') ORDER BY TotalScore desc, LOG.CALLSIGN asc LIMIT 20");

$besttimes = CalculateBestTimes();

$clubs = CalculateBestClubs();

$pdf = new NCCCSummaryPDF($thisyear . " California QSO Party (CQP) \xe2\x80\x93 " . $reportname . "Summary Report");
$pdf->LeftColumn($cats, QueryBestMobile(), $mostssb->GetEntries(), $mostcw->GetEntries());
$pdf->RightColumn($topca, $topnonca, $clubs, $besttimes);

$pdf->Output("summary_" . $reportready . ".pdf", "F");
// At this point, the intent is that every element of SummaryStats has its
// correct value.

mysql_close($link);

