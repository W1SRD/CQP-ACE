<?php
//=============================================================
// File name: category.php
// Begin:     2012-09-29
// 
// File to generate NCCC contest reports using TCPDF
// Copyright (C) 2012 Thomas Epperly ns6t@arrl.net
//
// Define categories which are groupings of entries.
//=============================================================

/**
 * @file
 * This is a PHP to represent a grouping of contest entries. For the
 * CQP, each county is an entry category for CA entries. For US entries,
 * each state is an entry category.
 */
class EntryCategory {
  protected $name = "";
  protected $location = "";
  protected $entries = array();
  protected $numbered = false;
  
  
  public function __construct($name, $entries=array(), $numbered=false, $location="") {
    $this->name = $name;
    $this->entries = $entries;
    $this->numbered = $numbered;
    $this->location = $location;
  }

  public function AddEntry($entry) {
    $this->entries[] = $entry; 	/* Add entry to end of this. */
  }

  public function GetName() { return $this->name; }

  public function GetEntries() { return $this->entries; }

  public function GetNumbered() { return $this->numbered; }

  public function GetLocation() { return $this->location; }
}

/**
 * This class represents an entry into the contest. It's a station, a list
 * of operators, and the various statistics.
 */
class Entry {
  protected $callsign = "";
  protected $stationcall = "";
  protected $operators = array();
  protected $num_CW = 0;
  protected $num_PH = 0;
  protected $num_mult = 0;
  protected $total_score = 0;
  protected $entry_class = "";
  protected $new_record = false; /* true means that this entry is a
				    new record for the category. */
  protected $footnote = array();
  protected $loc_abbrev = "";
  protected $loc_full = "";
  protected $all_mult_time = NULL;

  public function __construct($callsign, $operators = array(),
			      $num_CW=0, $num_PH=0, $num_mult = 0,
			      $total_score = 0, $entry_class = '',
			      $qth_abbrev='', $qth_full='') {
    $this->callsign = strtoupper($callsign);
    $this->operators = $operators;
    $this->num_CW = $num_CW;
    $this->num_PH = $num_PH;
    $this->num_mult = $num_mult;
    $this->total_score = $total_score;
    $this->entry_class = $entry_class;
    $this->loc_abbrev = strtoupper($qth_abbrev);
    $this->loc_full = $qth_full;
    $this->all_multi_time = NULL;
  }

  public function SetNewRecord() {
    $this->new_record = true;
  }

  public function SetStationCall($stationcall) {
    $this->stationcall = $stationcall;
  }

  public function AddFootnote($footnote) {
    $this->footnote[] = $footnote;
  }

  public function SetAllMultipliers($timedate) {
    $this->all_multi_time = $timedate;
  }

  public function GetCallsign() { return $this->callsign; }
  public function GetStationCall() { return $this->stationcall; }
  public function GetOperators() { return $this->operators; }
  public function GetNumCW() { return $this->num_CW; }
  public function GetNumPH() { return $this->num_PH; }
  public function GetNumMult() { return $this->num_mult; }
  public function GetTotalScore() { return $this->total_score; }
  public function GetEntryClass() { return $this->entry_class; }
  public function GetNewRecord() { return $this->new_record; } 
  public function GetFootnote() { 
    if (empty($this->footnote)) {
      return "";
    }
    else {
      $result = "New record for ";
      $notes = $this->footnote;
      $lastelem = array_pop($notes);
      if (empty($notes)) {
	return $result . $lastelem;
      }
      else {
	return $result . implode(", ", $notes) . " and " . $lastelem . ".";
      }
    }
  }
  public function GetQTH() { return $this->loc_abbrev; }
  public function GetLocation() { return $this->loc_full; }
  public function GetAllMultipliers() { return $this->all_multi_time; }
}

class Club {
  protected $name = "";
  protected $numlogs = 0;
  protected $totalscore = 0;

  public function __construct($name, $logs, $score) {
    $this->name = $name;
    $this->numlogs = $logs;
    $this->totalscore = $score;
  }

  public function GetName() { return $this->name; }
  public function GetNumLogs() { return $this->numlogs; }
  public function GetScore() { return $this->totalscore; }
}

class MobileEntry {
  protected $entry;
  protected $num_total_qsos = 0;
  protected $num_counties = 0;
  protected $newrecord = false;
  public function __construct($entry=NULL, $qsos=0, $counties=0, $newrecord=false) {
    $this->entry = $entry;
    $this->num_total_qsos = $qsos;
    $this->num_counties = $counties;
    $this->newrecord = $newrecord;
  }
  public function GetEntry() { return $this->entry; }
  public function GetNumQSO() { return $this->num_total_qsos; }
  public function GetNumCounty() { return $this->num_counties; }
  public function GetNewRecord() { return $this->newrecord; }
  public function IsValid() {
    return isset($this->entry);
  }
}
