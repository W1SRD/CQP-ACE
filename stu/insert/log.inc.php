<?php

// Support functions to access and update the LOG table in CQP-ACE
//
// NOTE: For security purposes, you must set the database name, 
// user name and password by calling functions provided below.
//
// Because all this code is stored in a public GIT, make sure that
// you ALWAYS get this kind of data from environment variables set
// only on the server!
//

class CQPACE_LOG_TABLE {

// Variable definitions
private $USER = '';			// Username for DB access
private $PASS = '';			// Password for DB access
private $HOST = '127.0.0.1';		// Host for the DB
private $DBNAME = 'CQP-ACE';		// Database to access

private $DBH;				// PDO database handle...

// SQL statements for prepare

private $ROW_INSERT = <<<ENDSQL
insert into LOG
  set
  CALLSIGN = trim(:callsign),
  EMAIL_ADDRESS = trim(:email_address),
  STATION_LOCATION = trim(:station_location),
  OPERATOR_CATEGORY = :operator_category,
  POWER_CATEGORY = :power_category,
  STATION_CATEGORY = :station_category,
  TRANSMITTER_CATEGORY = :transmitter_category,
  CLUB = (select CLUB_NAME from CLUB_ALIAS where ALIAS = trim(:club) LIMIT 1),
  SUBMISSION_DATE = :submission_date,
  OVERLAY_YL = :overlay_yl,
  OVERLAY_YOUTH = :overlay_youth,
  OVERLAY_NEW_CONTESTER = :overlay_new_contester,
  CLAIMED_SCORE = trim(:claimed_score),
  LOG_FILENAME = :log_filename,
  SOAPBOX = :soapbox,
  CABRILLO_HEADER = :cabrillo_header,
  QSO_RECS_PRESENT = :qso_recs_present,
  NUMBER_QSO_RECS = :number_qso_recs,
  LAST_UPDATED = :last_updated;
ENDSQL;


private $ROW_UPDATE = <<<ENDSQL
update LOG 
  set
  EMAIL_ADDRESS = trim(:email_address),
  STATION_LOCATION = trim(:station_location),
  OPERATOR_CATEGORY = :operator_category,
  POWER_CATEGORY = :power_category,
  STATION_CATEGORY = :station_category,
  TRANSMITTER_CATEGORY = :transmitter_category,
  CLUB = (select CLUB_NAME from CLUB_ALIAS where ALIAS = trim(:club) LIMIT 1),
  SUBMISSION_DATE = :submission_date,
  OVERLAY_YL = :overlay_yl,
  OVERLAY_YOUTH = :overlay_youth,
  OVERLAY_NEW_CONTESTER = :overlay_new_contester,
  CLAIMED_SCORE = trim(:claimed_score),
  LOG_FILENAME = :log_filename,
  SOAPBOX = :soapbox,
  CABRILLO_HEADER = :cabrillo_header,
  QSO_RECS_PRESENT = :qso_recs_present,
  NUMBER_QSO_RECS = :number_qso_recs,
  LAST_UPDATED = :last_updated
  where CALLSIGN = :callsign;
ENDSQL;


private $ROW_DELETE = <<<ENDSQL
delete from LOG
  where CALL = trim(:callsign);
ENDSQL;


private $ROW_SELECT = <<<ENDSQL
select * from LOG where CALLSIGN = trim(:callsign);
ENDSQL;


// Constructor
// 

function __construct($user, $pass, $host = '127.0.0.1', $db = 'CQP-ACE') {
  // Set up the local variables and create the connection to the DB

  $this->USER = $user;  $this->PASS = $pass;
  $this->HOST = $host;  $this->DBNAME = $db;

  $dsn = "mysql:dbname=" . $this->DBNAME . ";host=" . $this->HOST. ";charset=utf8";

  try {
    $this->DBH = new PDO($dsn, $this->USER, $this->PASS);
    
    // Set attributes to help prevent SQL injections
    $this->DBH->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
    $this->DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch ( Exception $e) {
    $msg = $e->getMessage();
    
    // throw an exception to our caller...
    throw new Exception($msg);
  }
}


// ROW CRUD functions for LOG table

function log_row_create($values) {
  // Set up the statement via prepare...
  try { 
    $sqle = $this->DBH->prepare($this->ROW_INSERT);
    $obj = $sqle->execute($values);
    return($obj);
  } catch (PDOException $e) {
    $msg = $e->getMessage();
   
    // throw an exception to our caller...
    throw new Exception($msg);
  }
}

   
function log_row_update($values) {
  // Set up the statement via prepare...
  try {
    $sqle = $this->DBH->prepare($this->ROW_UPDATE);
    $obj = $sqle->execute($values);
    return($obj);
  } catch (PDOException $e) {
    $msg = $e->getMessage();
    
    // throw an exception to our caller...
    throw new Exception($msg);
  }
}
 

function log_row_select($values) {
  // Set up the statement via prepare...
  try {
    $sqle = $this->DBH->prepare($this->ROW_SELECT);
    $obj = $sqle->execute($values);

    if ($obj) {
      $row = $sqle->fetch(PDO::FETCH_ASSOC);
      return ($row);
    } else {
      return (array());
    }
  } catch (PDOException $e) {
    $msg = $e->getMessage();
   
    // throw an exception to our caller...
    throw new Exception($msg);
  }
}


function log_row_delete($values) {
  // Set up the statement via prepare...
  try {
    $sqle = $this->DBH->prepare($this->ROW_DELETE);
    $obj = $sqle->execute($values);
    return($obj);
  } catch (PDOException $e) {
    $msg = $e->getMessage();
   
    // throw an exception to our caller...
    throw new Exception($msg);
  }
}
 
} // Class

