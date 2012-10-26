<?php
//=============================================================
// File name: summary_lib.php
// Begin:     2012-09-29
// 
// File to generate NCCC contest reports using TCPDF
// Copyright (C) 2012 Thomas Epperly ns6t@arrl.net
//
//=============================================================
require_once('../3rdparty/tcpdf/tcpdf.php');
require_once('category.php');


function PhoneTotal($ent) {
  return $ent->GetNumPH();
}

function CWTotal($ent) {
  return $ent->GetNumCW();
}

class NCCCSummaryPDF extends TCPDF {
  const LEFTMARGIN = 54;
  const RIGHTMARGIN = 252;
  const PAGEWIDTH = 504;
  const TOPMARGIN = 80;
  const LINEWIDTH = 5;
  protected $borders = 0;
  protected $header_height = 22;
  protected $baseline_skip = 12;
  protected $fontsize = 7.5;

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
    if ($this->GetStringWidth($testresult,'helvetica','B', $this->fontsize) <=
	0.93*$colwidth) {
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
    return "Time: " . $endtime->format("H:i") . $interval->format(" (%hhr %imin)");
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
    $str .= "<tr><td colspan=\"4\" width=\"100%\">&nbsp;</td></tr>";
    $str .= "<tr><th colspan=\"4\" align=\"center\" style=\"background-color:#ccffcc;\">" . $world->GetName() . "</th></tr>
<tr><th align=\"center\" width=\"". $widths[0]."%\">#</th><th align=\"center\"width=\"". $widths[1]."%\">Callsign</th><th align=\"center\"width=\"". $widths[2]."%\">QTH</th><th align=\"center\"width=\"". $widths[3]."%\">Score</th></tr>
";
    $this->ShowRightEntries($str,$world->GetEntries());
    $str .= "<tr><td colspan=\"4\" width=\"100%\">&nbsp;</td></tr>";
    $str .= "<tr><th colspan=\"4\" align=\"center\" style=\"background-color:#fde9d9; border: 1pt solid black;\">Top Club Entries (CA and non-CA)</th></tr>\n";
    foreach ($clubs as $club) {
      $str .= ("<tr><td width=\"". $widths[0]."%\">&nbsp;</td><td width=\"". $widths[1]."%\">" . $club->GetName() . "</td><td width=\"". $widths[2]."%\">" .
	       strval($club->GetNumLogs()) .
	       " logs</td><td align=\"right\" width=\"". $widths[3]."%\">" . $this->strformat($club->GetScore()) . 
	       "</td></tr>\n");
    }
    $str .= "<tr><td colspan=\"4\" width=\"100%\">&nbsp;</td></tr>";
    $str .= "<tr><th colspan=\"4\" align=\"center\" style=\"background-color:#fde9d9; border: 1pt solid black;\">First to 58 Mults (CA and non-CA)</th></tr>\n";
    foreach ($besttimes as $bt) {
      $str .= ("<tr><td width=\"". $widths[0]."%\">&nbsp;</td><td width=\"".
	       $widths[1]."%\">" . $bt->GetCallsign() . "</td><td colspan=\"2\" width=\"". 
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
    $colwidth = $this->ColumnWidth($widths[1]);
    foreach ($cat->GetEntries() as $ent) {
      $colorstr =  ($ent->GetNewRecord() ? " style=\"color:#FF0000;\" " : 
		    "");
      $boldcolorstr =  ($ent->GetNewRecord() ? " style=\"font-weight:bold; color:#FF0000;\" " : 
		    " style=\"font-weight:bold;\" ");
      $str .= "<tr>";
      if ($cat->GetNumbered()) {
	$str .= ("<td width=\"".strval($widths[0]) ."%\">".strval($count). "</td>");
      }
      else {
	$str .= ("<td width=\"".strval($widths[0]) ."%\">&nbsp;</td>");
      }
      list ($stationcall, $extraline) = $this->StationAndOps($ent, $colwidth);
      $str .= ("<td align=\"left\" width=\"".strval($widths[1]) ."%\" " . $boldcolorstr . ">" . 
	       $stationcall . 
	       "</td><td align=\"left\" width=\"".strval($widths[2]) ."%\"" . $colorstr . ">" . 
	       $ent->GetLocation() .
	       "</td><td align=\"right\" width=\"".strval($widths[3]) ."%\"" . $colorstr . ">" .
	       strval($ent->GetNumMult()) . 
	       "</td><td align=\"right\" width=\"".strval($widths[4]) ."%\"" . $colorstr . ">" .
	       $this->strformat($ent->GetNumCW()).
	       "</td><td align=\"right\" width=\"".strval($widths[5]) ."%\"" . $colorstr . ">" .
	       $this->strformat($ent->GetNumPH()).
	       "</td><td align=\"right\" width=\"".strval($widths[6]) ."%\"" . $colorstr . ">" .
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

  public function ColumnWidth($percent) {
      $margins = $this->GetMargins();
      $totalwidth = (8.5*72 - $margins["right"]) - $margins["left"];
      return 0.98*$percent*$totalwidth/100.0;
  }

  public function WriteMobile(&$str, $widths, $mobile) {
    if (isset($mobile) and $mobile->IsValid()) {
      $colwidth = $this->ColumnWidth($widths[1]);
      $ent = $mobile->GetEntry();
      $str .= "<tr>";
      $str .= "<th width=\"".$widths[0]."%\">&nbsp;</th>";
      $str .= "<th style=\"background-color:#fde9d9;\" border=\"1\" width=\"".$widths[1]."%\" align=\"left\">Mobile with most QSOs</th>";
      $str .= "<th style=\"background-color:#fde9d9;\" border=\"1\" width=\"".$widths[2]."%\" align=\"left\">California</th>";
      $str .= "<th style=\"background-color:#fde9d9;\" border=\"1\" width=\"".strval($widths[3]+$widths[4]+$widths[5]+
				    $widths[6]) .
	"%\" colspan=\"4\">Results</th></tr>\n";
      $str .= "<tr" . 
	($mobile->GetNewRecord() ? " style=\"color:#ff0000;\"" : "") . ">";
      $str .= "<td width=\"" . $widths[0] . "%\">&nbsp;</td>";
      list ($stationcall, $extraline) = 
	$this->StationAndOps($ent, $colwidth);
      $str .= ("<td align=\"left\" width=\"".strval($widths[1]) ."%\" style=\"font-weight:bold;\">" . 
	       $stationcall . 
	       "</td>");
      $str .= ("<td align=\"left\" width=\"".strval($widths[2]) ."%\">Various Counties</td>");
      $str .= ("<td align=\"left\" width=\"" .
	       strval($widths[3]+$widths[4]+$widths[5]+$widths[6]) .
               "%\">" . sprintf("%d QSOs (%d counties)",
				$mobile->GetNumQSO(),
				$mobile->GetNumCounty()) .
	       "</td>");
      $str .= "</tr>\n";
      if (strcmp($extraline, "") != 0) {
	$str .= ("<tr><td width=\"".strval($widths[0]) .
		 "%\">&nbsp;</td><td colspan=\"6\" align=\"left\" width=\"".
		 strval(100-$widths[0]) ."%\" style=\"font-style: italics;\">" . $extraline .
		 "</td></tr>\n");
      }
    }
  }

  public function WriteMostQSOs(&$str, $title, $entries, $func, $widths) {
    $colwidth = $this->ColumnWidth($widths[1]);
    $str .= "<tr>";
    $str .= "<th width=\"".$widths[0]."%\">&nbsp;</th>";
    $str .= "<th style=\"background-color:#fde9d9;\" colspan=\"2\" border=\"1\" width=\"".
      strval($widths[1]+$widths[2])."%\" align=\"left\">Single-Op - Most " . $title . " QSOs (CA and non-CA)</th>";
    $str .= "<th style=\"background-color:#fde9d9;\" border=\"1\" width=\"".
      strval($widths[3]+$widths[4]+$widths[5]+ $widths[6]) .
      "%\" colspan=\"4\">Results</th></tr>\n";
    foreach ($entries as $ent) {
      $str .= "<tr" . 
	($ent->GetNewRecord() ? " style=\"color:#ff0000;\"" : "") . ">";
      $str .= "<td width=\"" . $widths[0] . "%\">&nbsp;</td>";
      list ($stationcall, $extraline) = 
	$this->StationAndOps($ent, $colwidth);
      $str .= ("<td align=\"left\" width=\"".strval($widths[1]) ."%\" style=\"font-weight:bold;\">" . 
	       $stationcall . 
	       "</td>");
      $str .= ("<td align=\"left\" width=\"".strval($widths[2]) ."%\">".
	       $ent->GetLocation() . "</td>");
      $str .= ("<td align=\"left\" width=\"" .
	       strval($widths[3]+$widths[4]+$widths[5]+$widths[6]) .
               "%\">" . sprintf("%d QSOs", 
				call_user_func($func, $ent)) .
	       "</td>");
      $str .= "</tr>\n";
      if (strcmp($extraline, "") != 0) {
	$str .= ("<tr><td width=\"".strval($widths[0]) .
		 "%\">&nbsp;</td><td colspan=\"6\" align=\"left\" width=\"".
		 strval(100-$widths[0]) ."%\" style=\"font-style: italics;\">" . $extraline .
		 "</td></tr>\n");
      }
    }
  }

  public function WriteCategories($cats, $widths, $mobile, $ssb, $cw) {
    $this->SetFont('helvetica','', $this->fontsize);
    $str = "<table border=\"0\" width=\"100%\" cellpadding=\"1\">\n";
    foreach ($cats as $cat) {
      $this->WriteCatHeading($str, $cat, $widths);
      $this->WriteCatBody($str, $cat, $widths);
    }
    $this->WriteMobile($str, $widths, $mobile);
    $this->WriteMostQSOs($str, "SSB", $ssb, "PhoneTotal", $widths);
    $this->WriteMostQSOs($str, "CW", $cw, "CWTotal", $widths);
    $str .= "</table>";
    $str .= "<p style=\"color:#ff0000;\"><sup>*</sup>New Record</p>";
    $this->WriteHTML($str);
  }

  public function LeftColumn($cats, $mobile, $ssb, $cw) {
    $this->SetMargins(0.75*72, NCCCSummaryPDF::TOPMARGIN, 
		      (8.5-5)*72+NCCCSummaryPDF::LINEWIDTH);
    $this->SetY(NCCCSummaryPDF::TOPMARGIN-10);
    $widths = array(4,36,24,8,8,8,12);
    $this->WriteCategories($cats, $widths, $mobile, $ssb, $cw);
  }


  public function Footer() {
  }

  public function __construct($title = "Draft CQP Report") {
    parent::__construct('P','pt','LETTER',true,'UTF-8',false,false);
    $this->SetAutoPageBreak(true, 36);
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
