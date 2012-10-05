<?php
//=============================================================
// File name: summary.php
// Begin:     2012-09-29
// 
// File to generate NCCC contest reports using TCPDF
// Copyright (C) 2012 Thomas Epperly ns6t@arrl.net
//
//=============================================================
require_once('../3rdparty/tcpdf/tcpdf.php');
require_once('category.php');


class NCCCSummaryPDF extends TCPDF {
  const LEFTMARGIN = 54;
  const RIGHTMARGIN = 252;
  const PAGEWIDTH = 504;
  const TOPMARGIN = 80;
  const LINEWIDTH = 5;
  protected $borders = 0;
  protected $header_height = 22;
  protected $baseline_skip = 12;
  protected $fontsize = 8;

  public function Header() {
    $this->SetFillColorArray(array(204, 255, 204));
    $image_file = dirname(__FILE__)."/images/nccc_generic.png";
    $this->Image($image_file, 36 /* x */, 36 /* y */, 0 /* w */, 
		 $this->header_height /* h */,
    		 'PNG' /* type */, '' /* link */, 'T' /* align */,
    		 true /* resize */, 300 /* dpi */, '' /* palign */); 
    $this->SetFont('helvetica', 'B', 16);
    $this->Cell(10);
    $this->Cell(480,$this->header_height, 
		$this->report_title, 1, 1, 'C', true, '', 0, false,
		'T', 'M');
    $this->SetLineWidth(NCCCSummaryPDF::LINEWIDTH+1);
    $x = 5*72-0.5*NCCCSummaryPDF::LINEWIDTH;
    $this->Line($x,NCCCSummaryPDF::TOPMARGIN-10.5,$x,
		11*72-NCCCSummaryPDF::TOPMARGIN);
  }

  private function strformat($ival) {
    $separator = ",";
    // $separator = "\xe2\x80\x89";
    $str = strval($ival);
    $result = "";
    for ($count = 0,$i = strlen($str)-1; $i >= 0; $i--,$count++) {
      if (($count > 0) and (($count % 3) == 0)) {
	$result = $separator . $result;
      }
      $result = $str[$i] . $result;
    }
    return $result;
  }

  private function StationAndOps($entry, $colwidth) {
    if (strcmp($entry->GetFootnote(), "") != 0) {
      $this->footnotes[] = $entry->GetFootnote();
      $footnotestr = "<sup>*</sup>";
    }
    else {
      $footnotestr = "";
    }
    $ops = $entry->GetOperators();
    $len = count($ops);
    if (($len == 0) or (($len == 1) and 
			(strcmp($entry->GetCallsign(), $ops[0]) == 0))) {
      if ((strcmp($entry->GetStationCall(),"") == 0) or
	  (strcmp($entry->GetStationCall(),$entry->GetCallsign()) == 0)) {
	return array($entry->GetCallsign() . $footnotestr,
		     ""); /* simple case */
      }
      else {
	return array($entry->GetCallsign() . $footnotestr . " (@" .
		     $entry->GetStationCall() .")", "");
      }
    }
    $includesstation = false;
    $opslist = "";
    $joiner = "";
    foreach ($ops as $op) { 
      if (strcmp($op,$entry->GetCallSign())!=0) {
	$opslist = $opslist . $joiner . $op;
	$joiner = ", ";
      }
      else {
	$includesstation = true;
      }
    }
    if ($len == 1) {
      $testresult = $entry->GetCallSign() . " (" . $opslist . " op";
      $result = $entry->GetCallSign() . $footnotestr . " (" . 
	$opslist . " op";
      if ((strcmp($entry->GetStationCall(),"")!=0) and
	  (strcmp($entry->GetStationCall(),$entry->GetCallSign()) != 0)) {
	$testresult .= (" @" . $entry->GetStationCall());
	$result .= (" @" . $entry->GetStationCall());
      }
      $testresult .= ")";
      $result .= ")";
    }
    else {
      $testresult = $entry->GetCallSign() . " (" . 
	($includesstation ? "+ " : "") .
	$opslist . 
	(((strcmp($entry->GetStationCall(),"") != 0) and
	  (strcmp($entry->GetStationCall(),$entry->GetCallSign()) != 0))
	 ? (" @" . $entry->GetStationCall()) : "") .
	")";
      $result = $entry->GetCallSign() . $footnotestr .
	" (" . 
	($includesstation ? "+ " : "") .
	$opslist . 
	((strcmp($entry->GetStationCall(),"") != 0  and
	  (strcmp($entry->GetStationCall(),$entry->GetCallSign()) != 0))
	 ? (" @" . $entry->GetStationCall()) : "") .
	")";
    } // 220
    $margins = $this->getMargins();
    if ($this->GetStringWidth($testresult) <= $colwidth) {
      return array($result, "");
    }
    return array($entry->GetCallSign().$footnotestr.
		 (((strcmp($entry->GetStationCall(),"") != 0 ) and
		   (strcmp($entry->GetStationCall(),$entry->GetCallSign()) != 0))
		  ? (" (@" . $entry->GetStationCall() . ")") : ""),
		 " Ops = " .
		 ($includesstation ? ($entry->GetCallSign() . ", ") : "") . 
		 $opslist);
  }

  public function ShowRightEntries(&$str, $entries) {
    $this->SetFont('helvetica','', $this->fontsize);
    $count = 1;
    foreach ($entries as $ent) {
      $str .= ("<tr>\n<td align=\"center\">" .
	       strval($count++)."</td>\n<td>" .
	       $ent->GetCallsign() . "</td>\n<td>" .
	       $ent->GetQTH() . "</td>\n<td align=\"right\">" .
	       $this->strformat($ent->GetTotalScore()) . "</td>\n</tr>\n");
    }
  }

  public function TimeReport($ent) {
    $starttime = new DateTime("2011-10-01 16:00:00");
    $endtime = $ent->GetAllMultipliers();
    $interval = $endtime->diff($starttime);
    return "Time: " . $endtime->format("H:i") . $interval->format(" (%h hr %i min)");
  }

  public function RightColumn($california, $world, $clubs, $besttimes) {
    $widths = array(10, 33, 25, 32);
    $this->SetMargins(5*72, NCCCSummaryPDF::TOPMARGIN,
		      0.75*72);
    $this->SetY(NCCCSummaryPDF::TOPMARGIN-10);
    $str = "<table width=\"100%\" border=\"1\" cellpadding=\"1\">
<tr><th colspan=\"4\" align=\"center\" style=\"background-color:#fde9d9; font-weight:bold;\">*** CQP Wine Winners ***</th></tr>
<tr><th colspan=\"4\" align=\"center\" style=\"background-color:#ccffcc;\">" . $california->GetName() . "</th></tr>
<tr><th align=\"center\" width=\"". $widths[0]."%\">#</th><th align=\"center\"width=\"". $widths[1]."%\">Callsign</th><th align=\"center\"width=\"". $widths[2]."%\">QTH</th><th align=\"center\"width=\"". $widths[3]."%\">Score</th></tr>
";
    $this->ShowRightEntries($str,$california->GetEntries());
    $str .= "</table>";
    $this->WriteHTML($str);
    $str = "<table width=\"100%\" border=\"1\" cellpadding=\"1\">
<tr><th colspan=\"4\" align=\"center\" style=\"background-color:#ccffcc;\">" . $world->GetName() . "</th></tr>
<tr><th align=\"center\" width=\"". $widths[0]."%\">#</th><th align=\"center\"width=\"". $widths[1]."%\">Callsign</th><th align=\"center\"width=\"". $widths[2]."%\">QTH</th><th align=\"center\"width=\"". $widths[3]."%\">Score</th></tr>
";
    $this->ShowRightEntries($str,$world->GetEntries());
    $str .= "</table>\n";
    $this->WriteHTML($str);
    $str = "<table width=\"100%\" border=\"0\" cellpadding=\"1\">
<tr><th colspan=\"4\" align=\"center\" style=\"background-color:#fde9d9; border: 1pt solid black;\">Top Club Entries (CA and non-CA)</th></tr>\n";
    foreach ($clubs as $club) {
      $str .= ("<tr><td width=\"". $widths[0]."%\">&nbsp;</td><td width=\"". $widths[1]."%\">" . $club->GetName() . "</td><td width=\"". $widths[2]."%\">" .
	       strval($club->GetNumLogs()) .
	       " logs</td><td align=\"right\" width=\"". $widths[3]."%\">" . $this->strformat($club->GetScore()) . 
	       "</td></tr>\n");
    }
    $str .= "</table>";
    $this->WriteHTML($str);
    $str = "<table width=\"100%\" border=\"0\" cellpadding=\"1\">
<tr><th colspan=\"3\" align=\"center\" style=\"background-color:#fde9d9; border: 1pt solid black;\">First to 58 Mults (CA and non-CA)</th></tr>\n";
    foreach ($besttimes as $bt) {
      $str .= ("<tr><td width=\"". $widths[0]."%\">&nbsp;</td><td width=\"".
	       $widths[1]."%\">" . $bt->GetCallsign() . "</td><td width=\"". 
	       ($widths[2]+$widths[3])."%\">" .
	       $this->TimeReport($bt) . "</td></tr>\n");
    }
    $str .= "</table>";
    $this->WriteHTML($str);
   
  }

  public function WriteCatHeading(&$str, $cat, $widths) {
    $str .= "<tr><th width=\"". strval($widths[0]) . "%\">&nbsp;</th>";
    if (strcmp($cat->GetLocation(), "") == 0) {
      $str .= ("<th style=\"background-color:#fde9d9;\" border=\"1\" colspan=\"2\" width=\"" . 
	       strval($widths[1] + $widths[2]) . "%\">" .
	       $cat->GetName() . "</th>");
    }
    else {
      $str .= ("<th style=\"background-color:#fde9d9;\" border=\"1\" align=\"left\" width=\"" .
	       strval($widths[1]) . "%\">" .
	       $cat->GetName() . "</th>");
      $str .= ("<th style=\"background-color:#fde9d9;\" border=\"1\" align=\"left\" width=\"" .
	       strval($widths[2]) . "%\">" .
	       $cat->GetLocation() . "</th>");
    }
    $str .= ("<th  style=\"background-color:#fde9d9;\" border=\"1\" align=\"center\" width=\"" . 
	     strval($widths[3]) . "%\">Mults</th>");
    $str .= ("<th  style=\"background-color:#fde9d9;\" border=\"1\" align=\"center\" width=\"" . 
	     strval($widths[4]) . "%\">CW</th>");
    $str .= ("<th  style=\"background-color:#fde9d9;\" border=\"1\" align=\"center\" width=\"" . 
	     strval($widths[5]) . "%\">PH</th>");
    $str .= ("<th  style=\"background-color:#fde9d9;\" border=\"1\" align=\"center\" width=\"" . 
	     strval($widths[6]) . "%\">Score</th>");
    $str .= "</tr>\n";
  }

  public function WriteCatBody(&$str, $cat, $widths) {
    $count = 1;
    $margins = $this->GetMargins();
    $totalwidth = (8.5*72 - $margins["right"]) - $margins["left"];
    $colwidth = 0.98*$widths[1]*$totalwidth/100.0;
    foreach ($cat->GetEntries() as $ent) {
      $str .= "<tr>";
      if ($cat->GetNumbered()) {
	$str .= ("<td width=\"".strval($widths[0]) ."%\">".strval($count). "</td>");
      }
      else {
	$str .= ("<td width=\"".strval($widths[0]) ."%\">&nbsp;</td>");
      }
      list ($stationcall, $extraline) = $this->StationAndOps($ent, $colwidth);
      $str .= ("<td align=\"left\" width=\"".strval($widths[1]) ."%\">" . 
	       $stationcall . 
	       "</td><td align=\"left\" width=\"".strval($widths[2]) ."%\">" . 
	       $ent->GetLocation() .
	       "</td><td align=\"right\" width=\"".strval($widths[3]) ."%\">" .
	       strval($ent->GetNumMult()) . 
	       "</td><td align=\"right\" width=\"".strval($widths[4]) ."%\">" .
	       $this->strformat($ent->GetNumCW()).
	       "</td><td align=\"right\" width=\"".strval($widths[5]) ."%\">" .
	       $this->strformat($ent->GetNumPH()).
	       "</td><td align=\"right\" width=\"".strval($widths[6]) ."%\">" .
	       $this->strformat($ent->GetTotalScore()) .
	       "</td></tr>\n");
      if (strcmp($extraline, "") != 0) {
	$str .= ("<tr><td width=\"".strval($widths[0]) .
		 "%\">&nbsp;</td><td colspan=\"6\" align=\"left\" width=\"".
		 strval(100-$widths[0]) ."%\" style=\"font-style: italics;\">" . $extraline .
		 "</td></tr>\n");
      }
      $count++;
    }
  }

  public function WriteCategories($cats, $widths) {
    $this->SetFont('helvetica','', $this->fontsize);
    $str = "<table border=\"0\" width=\"100%\" cellpadding=\"1\">\n";
    foreach ($cats as $cat) {
      $this->WriteCatHeading($str, $cat, $widths);
      $this->WriteCatBody($str, $cat, $widths);
    }
    $str .= "</table>";
    $this->WriteHTML($str);
  }

  public function LeftColumn($cats, $mobile, $ssb, $cw) {
    $this->SetMargins(0.75*72, NCCCSummaryPDF::TOPMARGIN, 
		      (8.5-5)*72+NCCCSummaryPDF::LINEWIDTH);
    $this->SetY(NCCCSummaryPDF::TOPMARGIN-10);
    $widths = array(4,36,24,8,8,8,12);
    $this->WriteCategories($cats, $widths);
  }


  public function Footer() {
    $this->SetFont('times','B',12);
    $this->SetY(-36);
    $this->Cell(0, 14, "Page ". $this->getPage(), 0, 1, 'C', false, '', 
		0, false, 'B', 'B');
  }

  public function __construct($title = "Draft CQP Report") {
    parent::__construct('P','pt','LETTER',true,'UTF-8',false,false);
    $this->SetCreator(PDF_CREATOR);
    $this->SetAuthor('Northern California Contest Club');
    $this->SetTitle($title);
    $this->SetSubject('Contest summary published by the NCCC');
    $this->SetKeywords('NCCC, ham radio, CQP, contest, radiosport, results');
    $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
    //set margins
    $this->SetMargins(NCCCSummaryPDF::LEFTMARGIN,
		      NCCCSummaryPDF::TOPMARGIN,
		      NCCCSummaryPDF::RIGHTMARGIN);
    $this->SetHeaderMargin(0);
    $this->SetFooterMargin(0);
    $this->last_line = 11*72 - 36 - 2*$this->baseline_skip;

    $this->report_title = $title;
    $this->AddPage();
  }

  
}

$pdf = new NCCCSummaryPDF("9999 California QSO Party (CQP) - Draft Summary Report");

$california = array();
$world = array();
$topsingle_ca = array();
$topsingle_world = array();
$topms = array(); // CA and world combined
$topmm_ca = array();
$top_single_expedition = array();
$top_ms_expedition = array();
$top_mm_expedition = array();
$top_single_low_ca = array();
$top_single_low_world = array();
$top_single_qrp = array();
$top_single_yl = array();
$top_youth = array();
$top_dx = array();
$top_school = array();
$top_rookie = array();
$top_mobile = array();
$top_single_ssb = array();
$top_single_cw = array();

$clubs = array();
$besttimes = array();
$ent = new Entry("W6YI", array("N6MJ"), 1129, 1916, 58, 418847, "", "SDIE", "San Diego");
$ent->SetStationCall("W6YI");
$ent->SetNewRecord();
$ent->AddFootnote("New San Diego record");
$topsingle_ca[] = $ent;
$california[] = $ent;
$top_single_ssb[] = $ent;

$ent = new Entry("K6LA",array(),1266,1105, 58, 348551, "", "LANG", "Los Angeles");
$california[] = $ent;
$topsingle_ca[] = $ent;

$ent = new Entry("K6XX", array(), 1114, 1319, 58, 346898, "", "SCRU", "Santa Cruz");
$california[] = $ent;
$topsingle_ca[] = $ent;

$ent = new Entry("WC6H", array(), 1010, 1297, 58, 326337, "", "CALA", "Calaveras");
$california[] = $ent;

$ent = new Entry("K6NA", array("N6ED"), 915, 1341, 58, 314824, "", "SDIE", "San Diego");
$california[] = $ent;

$ent = new Entry("AE6Y", array(), 962, 1310, 56, 308420, "", "AMAD", "Amador");
$california[] = $ent;

$ent = new Entry("W6TK", array(), 921, 1149, 58, 293683, "", "SLUI", "San Luis Obispo");
$california[] = $ent;

$ent = new Entry("W6UE", array(), 1007, 992, 58, 290348, "", "LANG", "Los Angeles");
$california[] = $ent;

$ent = new Entry("KF6T", array(), 1127, 706, 57, 273201, "", "PLAC", "Placer");
$california[] = $ent;

$ent = new Entry("NO6F", array("K2RD"), 919, 945, 57, 264936, "", "SMAT", "San Mateo");
$california[] = $ent;

$ent = new Entry("N6TV", array(), 1559, 0, 56, 261996, "", "SCLA", "Santa Clara");
$california[] = $ent;
$top_single_ca[] = $ent;
$top_single_cw[] = $ent;

$ent = new Entry("AA6PW", array(), 743, 1121, 57, 254932, "", "ORAN", "Orange");
$california[] = $ent;

$ent = new Entry("K6RIM", array(), 1000, 694, 58, 254591, "", "MARN", "Marin");
$california[] = $ent;

$ent = new Entry("W6ML", array("W6KC"), 824, 893, 58, 247109, "E", "MONO", "Mono");
$california[] = $ent;
$top_single_expedition[] = $ent;

$ent = new Entry("W6XU", array(), 855, 813, 57, 239029, "", "SONO", "Sonoma");
$california[] = $ent;

$ent = new Entry("N6NZ", array(), 465, 1278, 58, 229158, "", "MARP", "Mariposa");
$california[] = $ent;

$ent = new Entry("AD6E", array(), 849, 752, 56, 226912, "E", "TULA", "Tulare");
$ent->SetNewRecord();
$california[] = $ent;
$top_single_expedition[] = $ent;

$ent = new Entry("N6IE", array(), 659, 931, 57, 218965, "", "SONO", "Sonoma");
$california[] = $ent;

$ent = new Entry("WA6FGV", array(), 689, 787, 57, 207622, "", "SBAR", "Santa Barbara");
$california[] = $ent;

$ent = new Entry("K6LRN", array(), 1060, 192, 57, 203148, "", "ELDO", "El Dorado");
$california[] = $ent;

$ent = new Entry("N6JS", array(), 0, 1688, 58, 195808, "", "SOLA", "Solano");
$ent->SetAllMultipliers(new DateTime("2011-10-01 20:25:00"));
$besttimes[] = $ent;

// world
$ent = new Entry("W0BH", array(), 225, 952, 58, 149727, "L", "KS", "Kansas");
$ent->SetNewRecord();
$ent->SetAllMultipliers(new DateTime("2011-10-01 21:29:00"));
$world[] = $ent;
$besttimes[] = $ent;
$topsingle_world[] = $ent;

$ent = new Entry("6Y6U", array(), 302, 736, 58, 137924, "", "DX", "North America");
$ent->SetNewRecord();
$world[] = $ent;
$top_dx[] = $ent;
$topsingle_world[] = $ent;

$ent = new Entry("K4BAI", array(), 377, 581, 58, 132849, "", "GA", "Georgia");
$world[] = $ent;
$topsingle_world[] = $ent;

$ent = new Entry("N5JB",  array(), 313, 662, 58, 131109, "", "TX", "Texas");
$world[] = $ent;

$ent = new Entry("NR5M", array(), 0, 1080, 58, 125222, "", "TX", "Texas");
$world[] = $ent;
$top_single_ssb[] = $ent;

$ent = new Entry("VE3KZ", array(), 353, 522, 58, 121829, "", "ON", "Ontario");
$ent->SetNewRecord();
$world[] = $ent;

$ent = new Entry("KH6LC", array("AH6RE"), 291, 606, 58, 120785, "", "HI", "Hawaii");
$ent->SetNewRecord();
$world[] = $ent;

$ent = new Entry("WX4G", array(), 293, 566, 58, 116638, "", "NC", "North Carolina");
$ent->SetNewRecord();
$world[] = $ent;

$ent = new Entry("VE3RZ", array(), 394, 413, 58, 116406, "", "ON", "Ontario");
$world[] = $ent;

$ent = new Entry("NF4A", array(), 295, 509, 57, 108328, "", "FL", "Florida");
$world[] = $ent;

$ent = new Entry("N4PN", array(), 274, 508, 58, 106604, "", "GA", "Georgia");
$world[] = $ent;

$ent = new Entry("NA4K", array(), 309, 450, 58, 105908, "L", "TN", "Tennessee");
$world[] = $ent;
$top_single_low_world[] = $ent;

$ent = new Entry("K0JPL", array(), 310, 444, 58, 105357, "", "MO", "Missouri");
$world[] = $ent;

$ent = new Entry("K4RO", array(), 263, 520, 57, 104110, "", "TN", "Tennessee");
$world[] = $ent;

$ent = new Entry("N2MM", array(), 276, 463, 58, 101587, "", "NJ", "New Jersey");
$world[] = $ent;

$ent = new Entry("K4ZGB", array(), 292, 424, 57, 98125, "", "AL", "Alabama");
$ent->SetNewRecord();
$world[] = $ent;
$top_single_low_world[] = $ent;

$ent = new Entry("K7SV", array(), 339, 346, 56, 95704, "", "VA", "Virginia");
$world[] = $ent;
$top_single_low_world[] = $ent;

$ent = new Entry("N1CC", array(), 223, 488, 57, 93765, "L", "TX", "Texas");
$world[] = $ent;

$ent = new Entry("N4XL", array(), 258, 421, 57, 92026, "L", "SC", "South Carolina");
$ent->SetNewRecord();
$world[] = $ent;

$ent = new Entry("W7GKF", array(), 288, 359, 57, 90117, "", "WA", "Washington");
$world[] = $ent;

$c = new Club("MLDXCC", 24, 2908799);
$clubs[] = $c;
$c = new Club("CCO", 32, 886490);
$clubs[] = $c;

$ent = new Entry("K6QK", array("N7CW", "K6ZH", "NN6X", "N6EEG", "N6ERD"),
		 780, 1528, 58, 313026, "M/S E", "IMPE", "Imperial");
$ent->SetNewRecord();
$topms[] = $ent;
$top_ms_expedition[] = $ent;
$ent = new Entry("N2NT", array(), 343, 507, 58, 118407, "M/S", "NJ", "New Jersey");
$topms[] = $ent;

$ent = new Entry("N6O", array("K3EST", "K6AW", "N6BV", "N6RO", "WA6O"),
		 1679, 3275, 58, 672133, "M/M", "ALAM", "Alameda");
$ent->SetStationCall("N6RO");
$topmm_ca[] = $ent;

$ent = new Entry("K6Z", array("K6ZZ", "W6PH", "KI6VC", "K6VR", "W1MD", "WA1Z", "N6WIN", "N6KZ"), 1603, 3265, 58, 657749, "M/M E", "INYO", "Inyo");
$top_mm_expedition[] = $ent;

$ent = new Entry("KI6LZ", array(), 552, 874, 58, 197519, "L", "VENT", "Ventura");
$top_single_low_ca[] = $ent;

$ent = new Entry("WN6K", array(), 787, 254, 56, 160720, "L", "SDIE", "San Diego");
$top_single_low_ca[] = $ent;

$ent = new Entry("WA6KHK", array(), 626, 414, 56, 151676, "L", "RIVE", "Riverside");
$top_single_low_ca[] = $ent;

$ent = new Entry("W6JTI", array(), 485, 398, 56, 126112, "Q", "HUMB", "Humboldt");
$top_single_qrp[] = $ent;

$ent = new Entry("ND0C", array(), 222, 344, 56, 75824, "Q", "MN", "Minnesota");
$top_single_qrp[] = $ent;

$ent = new Entry("N6UWW", array(), 0, 595, 53, 63123, "L", "SDIE", "San Diego");
$top_single_yl[] = $ent;

$ent = new Entry("VA3YOJ", array(), 0, 572, 58, 66294, "YL", "ON", "Ontario");
$top_single_yl[] = $ent;

$ent = new Entry("KJ6NLD", array(), 0, 227, 32, 14560, "L", "ELDO", "El Dorado");
$top_youth[] = $ent;

$ent = new Entry("KC9MEA", array(), 159, 80, 46, 29233, "L", "WI", "Wisconsin");
$top_youth[] = $ent;

$ent = new Entry("W6YX", array("N7MH", "K6UFO", "K2YY", "ND2T", "AG6FU", "AA6XV", "NF1R", "K6GK"), 1738, 2242, 58, 562484, "M/M S", "SCLA", "Santa Clara");
$ent->SetNewRecord();
$top_school[] = $ent;

$ent = new Entry("K0VVY", array(), 0, 28, 17, 952, "S", "SD", "South Dakota");
$top_school[] = $ent;

$ent = new Entry("W6GMP", array(), 0, 429, 56, 48104, "L", "SONO", "Sonoma");
$top_rookie[] = $ent;

$ent = new Entry("K6AQL", array("K0DI"), 1254, 0, 23, 1000, "M", "SCLA", "Santa Clara");
$top_mobile[] = $ent;

$ent = new Entry("K1ZZI", array(), 480, 0, 54, 77679, "", "GA", "Georgia");
$top_single_cw[] = $ent;

$cats = array();
$cats[] = new EntryCategory("TOP 3 Single-Op", $topsingle_ca, true, "California");
$cats[] = new EntryCategory("TOP 3 Single-Op", $topsingle_world, true, "Non-California");
$cats[] = new EntryCategory("TOP Multi-Single", $topms, false, "CA and Non-CA");
$cats[] = new EntryCategory("TOP Multi-Multi", $topmm_ca, false, "California");
$cats[] = new EntryCategory("TOP 2 Single-Op Expeditions, California", $top_single_expedition, true, "");
$cats[] = new EntryCategory("TOP Multi-Single Expedition, California", $top_ms_expedition, true, "");
$cats[] = new EntryCategory("TOP Multi-Multi Expedition, California", $top_mm_expedition, true, "");
$cats[] = new EntryCategory("TOP 3 Single-Op Low Power, California", $top_single_low_ca, true, "");
$cats[] = new EntryCategory("TOP 3 Single-Op Low Power, Non-California", $top_single_low_world, true, "");
$cats[] = new EntryCategory("TOP Single-Op QRP", $top_single_qrp, false, "CA and Non-CA");
$cats[] = new EntryCategory("TOP Single-Op YL", $top_single_yl, false, "CA and Non-CA");
$cats[] = new EntryCategory("TOP Youth", $top_youth, false, "CA and Non-CA");
$cats[] = new EntryCategory("TOP DX", $top_dx, false, "");
$cats[] = new EntryCategory("TOP Schools", $top_school, false, "CA and Non-CA");
$cats[] = new EntryCategory("New Contester", $top_rookie, false, "California");

$pdf->LeftColumn($cats, $top_mobile, $top_single_ssb, $top_single_cw);

$pdf->RightColumn(new EntryCategory("California", $california, true), 
		  new EntryCategory("Non-California", $world, true),
		  $clubs, $besttimes);

$pdf->Output("summary.pdf", "F");
