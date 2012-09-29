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
  protected $entries = array();
  
  
  public function __construct($name) {
    $this->name = $name;
  }

  public function AddEntry($entry) {
    $this->entries[] = $entry; 	/* Add entry to end of this. */
  }

  public function GetName() { return $this->name; }

  public function GetEntries() { return $this->entries; }
}

/**
 * This class represents an entry into the contest. It's a station, a list
 * of operators, and the various statistics.
 */
class Entry {
  protected $callsign = "";
  protected $operators = array();
  protected $num_CW = 0;
  protected $num_PH = 0;
  protected $num_mult = 0;
  protected $total_score = 0;
  protected $entry_class = "";
  protected $new_record = false; /* true means that this entry is a
				    new record for the category. */
  protected $footnote = "";

  public function __construct($callsign, $operators = array(),
			      $num_CW=0, $num_PH=0, $num_mult = 0,
			      $total_score = 0, $entry_class = '') {
    $this->callsign = $callsign;
    $this->operators = $operators;
    $this->num_CW = $num_CW;
    $this->num_PH = $num_PH;
    $this->num_mult = $num_mult;
    $this->total_score = $total_score;
    $this->entry_class = $entry_class;
  }

  public function SetNewRecord() {
    $this->new_record = true;
  }

  public function AddFootnote($footnote) {
    $this->footnote = $footnote;
  }

  public function GetCallsign() { return $this->callsign; }
  public function GetOperators() { return $this->operators; }
  public function GetNumCW() { return $this->num_CW; }
  public function GetNumPH() { return $this->num_PH; }
  public function GetNumMult() { return $this->num_mult; }
  public function GetTotalScore() { return $this->total_score; }
  public function GetEntryClass() { return $this->entry_class; }
  public function GetNewRecord() { return $this->new_record; } 
  public function GetFootnote() { return $this->footnote; }
}