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
private $DBUSER = '';			// Username for DB access
private $DBPASS = '';			// Password for DB access
private $DBHOST = '';   		// Host for the DB
private $DBNAME = '';	                // Database to access

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

function __construct($user, $pass, $db, $host) {
  // Set up the local variables and create the connection to the DB

  $this->DBUSER = $user;  
  $this->DBPASS = $pass;
  $this->DBNAME = $db;
  $this->DBHOST = $host;  

  $dsn = "mysql:dbname=" . $this->DBNAME . ";host=" . $this->DBHOST. ";charset=utf8";

  try {
    $this->DBH = new PDO($dsn, $this->DBUSER, $this->DBPASS);
    
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

