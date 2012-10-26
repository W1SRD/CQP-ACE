<?php
//=============================================================
// File name: summary.php
// Begin:     2012-09-29
// 
// File to test NCCC contest reports using TCPDF
// Copyright (C) 2012 Thomas Epperly ns6t@arrl.net
//
//=============================================================
require_once('summary_lib.php');

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
# $top_mobile = new MobileEntry($ent, 1679, 21, false);
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
$top_mobile = new MobileEntry($ent, 1254, 24, false);

$ent = new Entry("K1ZZI", array(), 480, 0, 54, 77679, "", "GA", "Georgia");
$ent->SetNewRecord();
$top_single_cw[] = $ent;

$cats = array();
$cats[] = new EntryCategory("TOP 3 Single-Op", $topsingle_ca, true, "California");
$cats[] = new EntryCategory("TOP 3 Single-Op", $topsingle_world, true, "Non-California");
$cats[] = new EntryCategory("TOP Multi-Single", $topms, false, "CA and Non-CA");
$cats[] = new EntryCategory("TOP Multi-Multi", $topmm_ca, false, "California");
$cats[] = new EntryCategory("TOP 2 Single-Op Expeditions, California", $top_single_expedition, false, "");
$cats[] = new EntryCategory("TOP Multi-Single Expedition, California", $top_ms_expedition, false, "");
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
