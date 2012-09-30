<?php

// Interface between the robot and the SQL database
//
// CQPACEUpdateDB($CQPF, $fullfname)
//
// Checks to see if this entry is already in the database
// by doing a select to see if the entry is present
// based on callsign.
//
// If it is, we attempt an UPDATE operation.
// otherwise, we attempt an INSERT.
//
// If anything other than the select fails, we will
// rename the file by appending '.ace-err' on the end
// of the file name.
//
// The MOST likely error is that the Club stated in the
// log isn't one we recognize and have no valid alias.
//
// This means that the submitter will get an email back
// but won't see their log in the log report table.
//
// This will likely end up with either a resubmission
// that will probably fail in the same way or an email
// to the cqp-chair.
//
// This corner case will get highlighted in the robot 
// faq - this requires human intervention and will not
// be immediate
//
// 9/29/2012 V1.0 K6TU
// Initial version

require_once('log.inc.php');

//
// Utility function
//

//
// CQPACERenameFile($fullfname)
//
// Append ".ace-err" to the file name and rename
//

function CQPACERenameFile($fullfname) {
  $newfname = $fullfname . ".ace-err";
  rename($fullfname, $newfname);
}



function CQPACEUpdateDB($CQPF, $fullfname) {
  // Grab user and password from the environment
  $USER = getenv('ACEUSER');
  $PASS = getenv('ACEPASS');

  // $CQPF contains the full information we need to
  // update the DB and perhaps some we don't - so
  // unset those fields...

  unset($CQPF['ASSISTED']);
  unset($CQPF['CEXP']);
  unset($CQPF['MOBILE']);
  unset($CQPF['SCHOOL']);

  // Get database handle
  try {
    $logt = new CQPACE_LOG_TABLE($USER, $PASS);
  } catch (Exception $e) {
    pd("  CQP-ACE ERROR NEW:");
    pd("  " . $e->getMessage());
    CQPACERenameFile($fullfname);
    return;
  }

  // Attempt a select on the callsign to see if a
  // record exists..
  try {
    $row = $logt->log_row_select(array(':callsign' => $CQPF[':callsign']));
  } catch (Exception $e) {
    // This shouldn't happen... but just in case...
    pd("  CQP-ACE ERROR SELECT:");
    pd("  " . $e->getMessage());
    CQPACERenameFile($fullfname);
    return;
  }

  // Ok, we get this far, we know whether we have a record in
  // the DB or not...  in either event, insert or update based on
  // whether $row is TRUE

  try {
    if ($row) {
      // A row already exists - this is an update
      $logt->log_row_update($CQPF);
      pd("    DB record updated for " . $CQPF[':callsign']);
      return "update";
    } else {
      // No record for this callsign in the DB - insert
      $logt->log_row_create($CQPF);
      pd("    DB record created for " . $CQPF[':callsign']);
      return "create";
    }
  } catch (Exception $e) {
    // This shouldn't happen... but just in case...
    pd("    CQP-ACE ERROR CREATE/UPDATE:");
    pd("  " . $e->getMessage());
    CQPACERenameFile($fullfname);
    return('');
  }
}

?>
