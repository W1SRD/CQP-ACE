<?php

// Support function to access LOG table in CQPACE for report 
// generation.
//
// NOTE: For security purposes, you must set the database name, 
// user name and password by calling functions provided below.
//
// Because all this code is stored in a public GIT, make sure that
// you ALWAYS get this kind of data from environment variables set
// only on the server!
//

class CQPACE_LOG_REPORT {
}


class CQPACE_RPT_LOG_TABLE {

// Variable definitions
private $USER = '';			// Username for DB access
private $PASS = '';			// Password for DB access
private $HOST = '127.0.0.1';		// Host for the DB
private $DBNAME = 'CQPACE';		// Database to access

private $DBH;				// PDO database handle...

// SQL statements for prepare

private $ROW_SELECT = <<<ENDSQL
select
  CALLSIGN,
  OPERATOR_CATEGORY,
  POWER_CATEGORY,
  STATION_LOCATION,
  LAST_UPDATED
from LOG
order by CALLSIGN asc;
ENDSQL;



// Constructor
// 

function __construct($user, $pass, $host = '127.0.0.1', $db = 'CQPACE') {
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


// ROW R functions for LOG report table

function log_row_select($values) {
  // Set up the statement via prepare...
  try {
    $sqle = $this->DBH->prepare($this->ROW_SELECT);
    $obj = $sqle->execute($values);

    if ($obj) {
      $rows = $sqle->fetchall(PDO::FETCH_CLASS, 'CQPACE_LOG_REPORT');
      return ($rows);
    } else {
      return (array());
    }
  } catch (PDOException $e) {
    $msg = $e->getMessage();
   
    // throw an exception to our caller...
    throw new Exception($msg);
  }
}


} // Class

