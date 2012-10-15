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
define("VALIDQSO","(QSO.QSO_STATUS = 'OK')");

// Calculate a report for this year's running of the CQP. This could
// be replaced by some other way of setting the year.
$thisyear = date("Y");		/* getting the current year */

$link = mysql_connect('localhost', 'dbtest', 'dbtest') or die("Connect not connect to database: " . mysql_error());
mysql_select_db('CQPACE_test', $link) or die("Could not select database");

// Create a temporary table to hold stats needed for the reports
// mysql_query("create temporary table SummaryStats (LOG_ID int primary key, CACounties int, StatesAndProvinces int, Multipliers int, InState boolean, CWQSOs int, PHQSOs int, TotalScore int, TimeForAllMultipliers DATETIME)", $link)  or die("Cannot create SummaryStats: " . mysql_error());
mysql_query("delete from SummaryStats") or die("Cannot delete");

// The purpose of this question is to select the logs that are:
// 1.  Part of the appropriate contest
// 2.  Part of this year's running
// 3.  Identify whether it's an in-state entry
$result = mysql_query("select LOG.ID, MULTIPLIER.TYPE = 'COUNTY' from LOG, MULTIPLIER where CONTEST_YEAR = " . $thisyear .
  " and CONTEST_NAME = 'CA-QSO-PARTY' and OPERATOR_CATEGORY <> 'CHECKLOG' and LOG.STATION_LOCATION = MULTIPLIER.NAME", $link);


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

$res = mysql_query("select LOG.CALLSIGN, LOG.STATION_LOCATION, CACounties, StatesAndProvinces, Multipliers, InState, CWQSOs, PHQSOs, TotalScore, TimeForAllMultipliers from LOG, SummaryStats where LOG.ID = SummaryStats.LOG_ID order by TotalScore desc");
while ($line = mysql_fetch_row($res)) {
  fputcsv(STDOUT,$line);
}

// At this point, the intent is that every element of SummaryStats has its
// correct value.

mysql_close($link);

