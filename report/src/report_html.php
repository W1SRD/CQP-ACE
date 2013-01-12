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
  protected $columnwidths = [39, 8, 8, 8, 8, 15, 14];
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
      if ($i == 0) {
	$alignment = "left";
      }
      else if ($i == 6) {
	$alignment = "center";
      }
      else {
	$alignment = "right";
      }
      $str .= ("  <th align=\"" . $alignment . 
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
    if ($this->GetStringWidth($testresult) <= 0.93*$this->columnwidths[0]/100*72*7.5) {
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
    $this->WriteHTML("<p>L = Low Power<br>\nQ = QRP<br>\nM/M = Multi-Multi<br>\nM/S = Multi-Single<br>\nYL = YL Operator<br>\nM = Mobile</p>");
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

