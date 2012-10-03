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
  protected $borders = 0;
  protected $header_height = 22;
  protected $baseline_skip = 12;
  protected $fontsize = 10;

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

  private function StationAndOps($entry) {
    if (strcmp($entry->GetFootnote(), "") != 0) {
      $this->footnotes[] = $entry->GetFootnote();
      $footnotestr = "<sup>" . count($this->footnotes) . "</sup>";
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
    if ($this->GetStringWidth($testresult) <= 0.98*$this->columnwidths[0]/100*72*7.5) {
      return array($result, "");
    }
    return array($entry->GetCallSign().$footnotestr.
		 (((strcmp($entry->GetStationCall(),"") != 0 ) and
		   (strcmp($entry->GetStationCall(),$entry->GetCallSign()) != 0))
		  ? (" (@" . $entry->GetStationCall() . ")") : ""),
		 $entry->GetCallSign() . " ops = " .
		 ($includesstation ? ($entry->GetCallSign() . ", ") : "") . 
		 $opslist);
  }

  private function CategoryEntries(&$str, $entries) {
    $this->SetFont('helvetica','', $this->fontsize);
    foreach ($entries as $entry) {
      list ($sign, $extraline) = $this->StationAndOps($entry);

      $str .= ("<tr " . ($entry->GetNewRecord() ? "style=\"color:#FF0000;\"" : 
			 "") . ">\n");
      $str .= ("  <td width=\"" . $this->columnwidths[0] .
	       "%\">" . $sign . "</td>\n");
      $str .= ("  <td align=\"right\" width=\"" . $this->columnwidths[1] .
	       "%\">" . $this->strformat($entry->GetNumCW()) .
	       "</td>\n");
      $str .= ("  <td align=\"right\" width=\"" . $this->columnwidths[2] .
	       "%\">" . $this->strformat($entry->GetNumPH()) .
	       "</td>\n");
      $str .= ("  <td align=\"right\" width=\"" . $this->columnwidths[3] .
	       "%\">" . 
	       $this->strformat($entry->GetNumPH()+$entry->GetNumCW()) .
	       "</td>\n");
      $str .= ("  <td align=\"right\" width=\"" . $this->columnwidths[4] .
	       "%\">" . 
	       $this->strformat($entry->GetNumMult()) .
	       "</td>\n");
      $str .= ("  <td align=\"right\" width=\"" . $this->columnwidths[5] .
	       "%\">" . 
	       $this->strformat($entry->GetTotalScore()) .
	       "</td>\n");
      $str .= ("  <td align=\"center\" width=\"" . $this->columnwidths[6] .
	       "%\">" . 
	       $entry->GetEntryClass() .
	       "</td>\n");
      $str .= "</tr>\n";
      if (strcmp("", $extraline) != 0) {
	$str .= ("<tr><td colspan=\"7\" style=\"font-style:italics;\">" .
		 $extraline . "</td></tr>\n");
      }
    }
  }

  public function ShowRightEntries(&$str, $entries) {
  }


  public function RightColumn($california, $world, $clubs, $besttimes) {
    $this->SetMargins(5*72, NCCCSummaryPDF::TOPMARGIN,
		      0.75*72);
    $this->SetY(NCCCSummaryPDF::TOPMARGIN);
    $str = "<table width=\"100%\" border=\"1\">
<tr><th colspan=\"4\" align=\"center\" style=\"background-color:#fde9d9; font-weight:bold;\">*** CQP Wine Winners ***</th></tr>
<tr><th colspan=\"4\" align=\"center\" style=\"background-color:#ccffcc;\">" . $california->GetName() . "</th></tr>
";
    $this->ShowRightEntries($str,$california->GetEntries());
    $str .= "</table>\n";
    $str .= "<table width=\"100%\" border=\"1\">
<tr><th colspan=\"4\" align=\"center\" style=\"background-color:#ccffcc;\">" . $world->GetName() . "</th></tr>
";
    $this->ShowRightEntries($str,$world->GetEntries());
    $str .= "</table>\n";
    $this->WriteHTML($str);
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
$top_single_ca[] = $ent;
$california[] = $ent;
$top_single_ssb[] = $ent;

$ent = new Entry("K6LA",array(),1266,1105, 58, 348551, "", "LANG", "Los Angeles");
$california[] = $ent;
$top_single_ca[] = $ent;

$ent = new Entry("K6XX", array(), 1114, 1319, 58, 346898, "", "SCRU", "Santa Cruz");
$california[] = $ent;
$top_single_ca[] = $ent;

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
$top_single_world[] = $ent;

$ent = new Entry("6Y6U", array(), 302, 736, 58, 137924, "", "DX", "North America");
$ent->SetNewRecord();
$world[] = $ent;
$top_dx[] = $ent;
$top_single_world[] = $ent;

$ent = new Entry("K4BAI", array(), 377, 581, 58, 132849, "", "GA", "Georgia");
$world[] = $ent;
$top_single_world[] = $ent;

$ent = new Entry("N5JB",  array(), 313, 662, 58, 131109, "", "TX", "Texas");
$world[] = $ent;

$ent = new Entry("NR5M", array(), 0, 1080, 58, 125222, "", "TX", "Texas");
$world[] = $ent;

$ent = new Entry("VE3KZ", array(), 353, 522, 58, 121829, "", "ON", "Ontario");
$ent->SetNewRecord();
$world[] = $ent;

$ent = new Entry("KH6LC", array("AH6RE"), 291, 606, 58, 120785, "", "HI", "Hawaii");
$ent->SetNewRecord();
$world[] = $ent;

$c = new Club("MLDXCC", 24, 2908799);
$clubs[] = $c;
$c = new Club("CCO", 32, 886490);
$clubs[] = $c;



$pdf->RightColumn(new EntryCategory("California", $california), 
		  new EntryCategory("Non-California", $world),
		  $clubs, $besttimes);

$pdf->Output("summary.pdf", "F");
