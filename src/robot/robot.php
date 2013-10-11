#!/usr/bin/php
<?php

// Mail robot for CQP
//
// A simple minded robot to process the incoming logs for CQP
//
// - Periodically checks the GMAIL inbox for incoming message
// - Processes each message for attached files
// - A cabrillo log may be a file or in the body of the message
// - A non-Cabrillo log maybe attcahed to the message
//
// Each file is checked to see if it begins with START-OF-LOG
// - if it does, we process and generate a reply back to the 
//   originator confirming their log submission together with
//   our view of their entry class/category etc.
//
// Messages with NON-Cabrillo logs are moved into a separate
// folder for Human processing and re-submission
//
// Processed messages are moved into an archive folder before
// the reply is sent
//
// Version 1: 9/8/2012 K6TU
// Initial version
//
// Version 1.1: 9/20/2012 K6TY
// Moved Email username/password from hardcoded to command line
// values.
//


// Set default timezone
date_default_timezone_set('UTC');

require_once('email.inc.php');
require_once('robot.inc.php');
require_once('errors.inc.php');
require_once('cqpace.inc.php');

// Global data

// Required Gmail folder names to handle moved messages
$HUMAN = 'Human';		// Human assistance required
$DONE = 'Processed';		// Robot processed messages
$JUNK = 'CQP Spam';             // Stuff we think might be SPAM

// Path names for data storage
$CABLOGS = '/home/sdyer/logs/2013';
$OTHER = '.';

// Defines
define('WAITFORMORE', '300');	// 10 minutes
define('IMAPWAIT', '60');	// 1 minute

define('CQPSTART', strtotime('2011-10-01'));
define('CQPEND',   strtotime('2013-10-07'));


// MAIN

// Grab user name and password for the account from environment variables... be sure to escape @ sign.
$USER = getenv('CQPUSER');
$PASS = getenv('CQPPASS');
//print $USER . "\n";
//print $PASS . "\n";

if (!$USER || !$PASS) {
  print "Can't find username or password in the ENV\n";
  exit(1);
}

// Main processing loop - SLEEP delay is at the bottom

while (1) {
   // Login and see how many new messages have arrived
   if (!erLogin()) {
     pd("Mail Account - LOGIN FAIL");
     sleep(IMAPWAIT);
     continue;
   }

  pd("Mail Account - LOGIN");

  $msgs = erNewCount();
  pd("  Message count: ". count($msgs));

  foreach ($msgs as $m) {
    // We are invincible!...  Don't need no stinkin' humans!
    $needHuman = FALSE;

    do {
      // Get the message...
      pd("    Get message - $m");
      $msg = erGetMessage($m);
 
      // Check for SPAM
      if (Spam($msg)) {
        pd("      !! Probable SPAM !!");
        erMoveMessage($m, $JUNK);
        break;
      }

      // Check for attachments. If there are attachments - there should only be one and we ignore the file body
      if (count($msg['ATTACHMENTS'])) {
        // If there is more than one - we need human help...
        if (count($msg['ATTACHMENTS']) != 1) {
          pd("      From: " . $m['FROM'] . ' - ' . 
             count($msg['ATTACHMENTS']) . " attachments");
          pd("        -> HUMAN");
 

          $needHuman = TOOMANYATTACHMENTS;
          break;
        }
  
        // Cabrillo check...
        if (!CabFileCheck($msg)) {
          // Not Cabrillo - whistle up the help
          pd("      From: " . $msg['FROM'] . ' - ' .  "NON-CABRILLO LOG");
          pd("        -> HUMAN");

          $needHuman = NOCABRILLOATTACHMENT;
          break;
        }
  
        // Cabrillo file - whoopee! - Save to disk
        $log = $msg['ATTACHMENTS'][0]['FILE'];
  
      } else { // No attachments... scan the body for the log...
        if (!($log = CabBodyCheck($msg))) {
          // Not Cabrillo - whistle up the help
          pd("      From: " . $msg['FROM'] . ' - ' .  "NON-CABRILLO MESSAGE BODY");
          pd("        -> HUMAN");

          $needHuman = NOCABRILLOBODY;
          break;
        } else {
            $wf   = WebformCheck($msg);
            $log = CabInsertWFInfo($log, $wf);
        }
      }

      // Check this is a log for CQP
      if (!CheckContest($log)) {
        pd("      From: " . $msg['FROM'] . ' - ' .  "Missing CONTEST field or NOT CQP log");
        pd("        -> HUMAN");
          
        $needHuman = NOTCQPCONTEST;
        break;
      }

      if (!CabCheckQDates($log, CQPSTART, CQPEND)) {
        // Dammed if the dates in the log aren't within the window of this year's contest...
        $needHuman = CQPDATEERROR;
        break;
      }

      $call = CabGetCall($log);
      $fname = MakeLogName($call);

      if (!file_put_contents("$CABLOGS/$fname", $log)) {
        pd("  - Error writing $CABLOGS/$fname - aborting!");
        exit(1);
      }
      
      // Change the mode on the file to prevent accidental deletion. 
      chmod("$CABLOGS/$fname", 0400);

      pd("    From: " . $msg['FROM']);
      pd("      Call: $call      File: $fname");

      // Ok - the file is a Cabrillo log and has been written
      // to disk.  The only thing left that could be wrong
      // is that despite claiming to be a CQP log, the Cabrillo
      // doesn't have enough fileds in the QSO record to be
      // for CQP...  we are almost done.

      $CQPF = CabCrack($msg['FROM'], $fname, $log);
      if (!$CQPF) {
        $needHuman = NOTCQPCONTEST;
        break;
      }

      // Write the record to the database for this Carillo
      // file.  In theory, the only reason for a failure here
      // would be caused by having a record for a club name
      // that we have never seen before (or a novel way of
      // identifiying an existing club)

      $dbres = CQPACEUpdateDB($CQPF, "$CABLOGS/$fname");

      SendResponse($msg, $CQPF, $fname, $dbres); 

      // Move the message to the DONE folder
      erMoveMessage($m, $DONE);
      pd("      Response sent");

      break;

    } while (1);

    if ($needHuman) {
      // Guess the robot is not so invincible after all...
      // Move the message to the HUMAN folder
      pd("    Message $m moved to $HUMAN");
      erMoveMessage($m, $HUMAN);

      // Send the user a message that we have flagged their submission
      NonComprendez($msg, $needHuman);
    }
  } 


  // Done processing messages... wait for more after a logout
  erLogout();

  sleep(WAITFORMORE);
}

?>
