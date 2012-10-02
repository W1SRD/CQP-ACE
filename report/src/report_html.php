<?php
//=============================================================
// File name: report.php
// Begin:     2012-09-29
// 
// File to generate NCCC contest reports using TCPDF
// Copyright (C) 2012 Thomas Epperly ns6t@arrl.net
//
//=============================================================
require_once('../3rdparty/tcpdf/tcpdf.php');
require_once('category.php');


class NCCCReportPDF extends TCPDF {
  const LEFTMARGIN = 54;
  const RIGHTMARGIN = 54;
  const PAGEWIDTH = 504;
  const TOPMARGIN = 80;
  protected $borders = 0;
  protected $header_height = 22;
  protected $report_title = '';
  protected $baseline_skip = 16;
  protected $fontsize = 12;
  //  protected $columnwidths = [250, 45, 45, 45, 45, 65, 45];
  protected $columnwidths = [41, 8, 8, 8, 8, 15, 12];
  protected $columnheadings = ["", "CW", "PH", "Total", "Mult", "Score", "Type"];
  protected $footnotes = array();

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

  private function CategoryHeader(&$str, $category) {
    $str .= "<thead>
<tr style=\"font-weight:bold;\">
";
    $hdrs = $this->columnheadings;
    $hdrs[0] = $category->GetName();
    for ($i = 0; $i <= 6 ; $i++) {
      $str .= ("  <th align=\"" . (($i > 0) ? "center" : "left") . 
	       "\" width=\"" . $this->columnwidths[$i] . "%\">" .
	       $hdrs[$i] . "</th>\n");
    }
    $str .= "</tr>\n</thead>\n";
  }

  private function WriteFootnotes() {
    if (count($this->footnotes) > 0) {
      $str = "<p style=\"color:#ff0000;\">\n";
      $i = 1;
      foreach ($this->footnotes as $footnote) {
	$str .= (" <sup>" . strval($i) . "</sup>" . $footnote . "<br>\n");
	$i++;
      }
      $str .= "</p>";
      $this->WriteHtml($str);
    }
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
      if (strcmp($entry->GetStationCall(),"")!=0) {
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
	(strcmp($entry->GetStationCall(),"") != 0 
	 ? (" @" . $entry->GetStationCall() . " ") : "") .
	")";
      $result = $entry->GetCallSign() . $footnotestr .
	" (" . 
	($includesstation ? "+ " : "") .
	$opslist . 
	(strcmp($entry->GetStationCall(),"") != 0 
	 ? (" @" . $entry->GetStationCall() . " ") : "") .
	")";
    } // 220
    if ($this->GetStringWidth($testresult) <= 0.98*$this->columnwidths[0]/100*72*7.5) {
      return array($result, "");
    }
    return array($entry->GetCallSign().$footnotestr.
		 (strcmp($entry->GetStationCall(),"") != 0 
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

  private function SkipLine() {
    $this->Cell(1, $this->baseline_skip, "", 0, 1);
  }

  public function ReportCategories($categories) {
    foreach ($categories as $category) {
      $catstr = "<table width=\"100%\">\n";
      $this->CategoryHeader($catstr,$category);
      $this->CategoryEntries($catstr,$category->GetEntries());
      $catstr .= "</table>\n";
      $this->WriteHTML($catstr);
    }
    $this->WriteHTML("<p>L = Low Power<br>\nQ = QRP<br>\nM/M = Multi-Multi<br>\nM/S = Multi-Single<br>\nYL = YL Operator<br>\n</p>");
    $this->WriteFootnotes();
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
    $this->SetSubject('Contest results published by the NCCC');
    $this->SetKeywords('NCCC, ham radio, contest, radiosport, results');
    $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
    //set margins
    $this->SetMargins(NCCCReportPDF::LEFTMARGIN,
		      NCCCReportPDF::TOPMARGIN,
		      NCCCReportPDF::RIGHTMARGIN);
    $this->SetHeaderMargin(0);
    $this->SetFooterMargin(0);
    $this->last_line = 11*72 - 36 - 2*$this->baseline_skip;

    $this->report_title = $title;
    $this->AddPage();
  }

  
}

$pdf = new NCCCReportPDF("9999 California QSO Party (CQP) \xe2\x80\x93  US Draft Results (Non-CA)");

$cats = array();
$cat = new EntryCategory("Alabama");
$ent = new Entry("K4ZBG", array(), 292, 424, 57,98125,"L");
$ent->SetNewRecord();
$ent->AddFootnote("New Alabama Record");
$cat->AddEntry($ent);
$ent = new Entry("K4ZBG", array(), 292, 424, 57,98125,"L");
$ent->SetNewRecord();
$ent->SetStationCall("NS6T");
$ent->AddFootnote("New Alabama Record");
$cat->AddEntry($ent);
$ent = new Entry("WA1FCN",array(),131,302,57,56829,"L");
$ent->SetStationCall("NS6T");
$cat->AddEntry($ent);
$cat->AddEntry(new Entry("K4HAL",array(),209,148,54,49842,""));
$cat->AddEntry(new Entry("N4UC", array(),232,108,52,47372,"L"));
$cat->AddEntry(new Entry("KX4X", array(),43, 162, 56, 25368, ""));
$cat->AddEntry(new Entry("KJ4LTA", array(),0, 216, 54, 23328, "L"));
$cat->AddEntry(new Entry("K3IE", array(),90, 54, 44, 16588, ""));
$cat->AddEntry(new Entry("KG4CUY", array(), 126, 0, 41, 15436, "L"));
$cat->AddEntry(new Entry("W4NBS", array(), 43, 23, 35, 6125, "L"));
$cat->AddEntry(new Entry("KF4IRC", array(), 0, 16, 14, 448, "L"));
$cat->AddEntry(new Entry("AJ1F", array(), 0, 7, 6, 84, "L"));
$cats[] = $cat; 

$cat = new EntryCategory("Arizona");
$ent = new Entry("KE2VB", array(), 128, 462, 58, 75864, "");
$ent->SetNewRecord();
$ent->AddFootnote("New Arizona Record");
$cat->AddEntry($ent);
$ent = new Entry("KE2VB", array(), 128, 462, 58, 75864, "");
$ent->SetStationCall("NS6T");
$ent->SetNewRecord();
$ent->AddFootnote("New Arizona Record");
$cat->AddEntry($ent);
$cat->AddEntry(new Entry("N6MA", array(), 411, 0, 53, 65349, ""));
$cat->AddEntry(new Entry("NI7R", array(), 179, 84, 54, 37935, "L"));
$cat->AddEntry(new Entry("K7JQ", array(), 89, 166, 54, 32292, ""));
$cat->AddEntry(new Entry("W2AJW", array(), 0, 192, 56, 21448, "L"));
$cat->AddEntry(new Entry("W7ON", array(), 46, 169, 50, 19200, ""));
$cat->AddEntry(new Entry("W0PAN", array(), 0, 137, 49, 13426,  "L"));
$cat->AddEntry(new Entry("KC7V", array(), 102, 0, 43, 13093,  "L"));
$cat->AddEntry(new Entry("N7MAL", array(), 102, 0, 39, 11934,  "L"));
$cat->AddEntry(new Entry("W6ZQ", array(), 10, 79, 42, 7833,  "L"));
$cat->AddEntry(new Entry("WU9B", array(), 39, 0, 27, 3118,  "L"));
$cat->AddEntry(new Entry("KD7OED", array(), 0, 28, 22, 1210,  "L"));
$cat->AddEntry(new Entry("KY7M", array(), 12, 8, 14, 728,  "L"));
$cat->AddEntry(new Entry("KF7PKL", array(), 0, 22, 16, 704," "));
$cat->AddEntry(new Entry("WB7TPH", array(), 0, 21, 16, 672,  "L"));
$cat->AddEntry(new Entry("KF6GUG", array(), 0, 1, 2, 4,  "L"));
$cat->AddEntry(new Entry("AA7V", array(), 317, 0, 51, 48501,  "M/S"));
$cat->AddEntry(new Entry("N7LR", array("N7LR", "W9CF"), 170, 165, 57, 47880, "M/S"));
$ent = new Entry("N7LR", array("N7LR", "W9CF"), 170, 165, 57, 47880, "M/S");
$ent->SetStationCall("NS6T");
$cat->AddEntry($ent);
$cats[] = $cat;

$cat = new EntryCategory("Hawaii");
$ent = new Entry("KH6LC", array("AH6RE"), 291, 606, 58, 120785, "");
$ent->SetNewRecord();
$ent->AddFootnote("New Hawaii Record");
$cat->AddEntry(new Entry("KH7Y", array(), 221, 369, 56, 78372, ""));
$cat->AddEntry(new Entry("WH7GG", array(), 0, 7, 7, 98,  "L"));
$cats[] = $cat;

$cat = new EntryCategory("San Diego");
$ent = new Entry("W6YI", array("N6MJ"), 1129, 1916, 58, 418847, "");
$ent->SetNewRecord();
$ent->AddFootnote("New San Diego record");
$cat->AddEntry($ent);
$ent = new Entry("W6YI", array("N6MJ"), 1129, 1916, 58, 418847, "");
$ent->SetNewRecord();
$ent->SetStationCall("NS6T");
$ent->AddFootnote("New San Diego record");
$cat->AddEntry($ent);
$cat->AddEntry(new Entry("K6NA", array("N6ED"), 915, 1341, 58, 314824));
$cat->AddEntry(new Entry("WN6K", array(), 787, 254, 56, 160720, "L"));
$cat->AddEntry(new Entry("NN3V", array(), 270, 209, 56, 68908,  "L"));
$cat->AddEntry(new Entry("N6UWW", array(), 0, 595, 53, 63123,  "L"));
$cat->AddEntry(new Entry("AF6WF", array(), 0, 423, 56, 47432,""));
$cat->AddEntry(new Entry("N6NC", array(), 291, 0, 47, 41031, ""));
$cat->AddEntry(new Entry("WB8YQJ", array(), 230, 2, 49, 34006,  "L"));
$cat->AddEntry(new Entry("W6KY", array(), 196, 28, 40, 25760,  "L"));
$cat->AddEntry(new Entry("K2RP", array(), 144, 62, 44, 24508, ""));
$cat->AddEntry(new Entry("W6ASP", array(), 0, 204, 40, 16320,  "L"));
$cat->AddEntry(new Entry("AA6EE", array(), 150, 0, 34, 15300,  "L"));
$cat->AddEntry(new Entry("KC6MIE", array(), 0, 165, 39, 12909,  "L"));
$cat->AddEntry(new Entry("N5ZO", array(), 80, 0, 30, 7200, ""));
$cat->AddEntry(new Entry("W6YOO", array(), 0, 128, 24, 6144, ""));
$cat->AddEntry(new Entry("N6VH", array(), 1, 103, 26, 5434,  "L"));
$cat->AddEntry(new Entry("KJ6KGI", array(), 0, 79, 34, 5406,  "L"));
$cat->AddEntry(new Entry("K6DEX", array(), 25, 56, 20, 3740,  "L"));
$cat->AddEntry(new Entry("N3PV", array(), 0, 64, 24, 3072,  "L"));
$cat->AddEntry(new Entry("WE6CW", array(), 0, 98, 14, 2758,  "L"));
$cat->AddEntry(new Entry("WA3YTI", array(), 0, 57, 23, 2645,  "L"));
$cat->AddEntry(new Entry("KJ6VX", array("K6NR", "K6RBS", "NR7E", "AF6GL", "AD6OI", "NJ6N", "NR6E"),
			 0, 47, 23, 2185,  "L"));
$ent = new Entry("KJ6VX", array("K6NR", "K6RBS", "NR7E", "AF6GL", "AD6OI", "NJ6N", "NR6E"),
		 0, 47, 23, 2185,  "L");
$ent->SetStationCall("NS6T");
$cat->AddEntry($ent);
$cat->AddEntry(new Entry("KI6LAV", array(), 0, 54, 16, 1744,  "L"));
$cat->AddEntry(new Entry("N6KI", array("N6KI", "W2PWS", "K6GO", "N6OHS","K5RQ", "AF6WF", "K6KAL", "K4RB", "N6CY"), 878, 1099, 57, 275566, "M/S E"));
$cat->AddEntry(new Entry("N6XT", array("N6XT", "KE6PY", "N6EP", "WA2IHV", "AF6WF", "K6KAL", "K6GO", "N6OHS"), 501, 947, 58, 197171, "M/S"));
$ent = new Entry("N6XT", array("N6XT", "KE6PY", "N6EP", "WA2IHV", "AF6WF", "K6KAL", "K6GO", "N6OHS"), 501, 947, 58, 197171, "M/S");
$ent->SetStationCall("NS6T");
$cat->AddEntry($ent);
$cat->AddEntry(new Entry("AE6IC", array("AE6IC", "KJ6JUS"), 235, 362, 54, 77247, "M/S L"));
$cat->AddEntry(new Entry("W6NWG", array("W5NYV", "KB5MU"), 0, 276, 42, 23184, "M/S L"));
$cat->AddEntry(new Entry("KK6TV", array(), 29, 77, 32, 7760,  "M/S L"));
$cat->AddEntry(new Entry("W6ABE", array(), 0, 94, 20, 3780,  "M/S L"));
$cats[] = $cat;

$pdf->ReportCategories($cats);

$pdf->Output("report.pdf", "F");