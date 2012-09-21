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


require_once('email.inc.php');
require_once('robot.inc.php');
require_once('errors.inc.php');

// Global data

// Folder names to handle moved messages
$HUMAN = 'Human';		// Human assistance required
$DONE = 'Processed';		// Robot processed messages
$JUNK = "SPAMWonderfulSPAM";    // Stuff we think might be SPAM

// Path names for data storage
$CABLOGS = '/mnt/ec2-user/CQPlogs';
$OTHER = '.';

// Defines
define('WAITFORMORE', '300');	// 10 minutes
define('IMAPWAIT', '60');	// 1 minute


// MAIN
// Grab user name and password for the account from
// environment variables...

$USER = getenv('CQPUSER');
$PASS = getenv('CQPPASS');

if (!$USER || !$PASS) {
  print "Can't find username or password in the ENV\n";
  exit(1);
}

// Set default timezone
date_default_timezone_set('UTC');

// Main processing loop - SLEEP delay is at the bottom

while (1) {
  // Login and see how many new messages have arrived

  if (($err = erLogin())) {
    pd("Mail Account - LOGIN FAIL");
    pd("  $err");
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
 
      if (Spam($msg)) {
        pd("      !! Probable SPAM !!");
        erMoveMessage($m, $JUNK);
        break;
      }

      // If there are attachments - there should only be one...
      // ... and if there are attachments, we ignore the file body
  
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
          pd("      From: " . $msg['FROM'] . ' - ' . 
             "NON-CABRILLO LOG");
          pd("        -> HUMAN");

          $needHuman = NOCABRILLOATTACHMENT;
          break;
        }
  
        // Cabrillo file - whoopee! - Save to disk
        $log = $msg['ATTACHMENTS'][0]['FILE'];
  
      } else {
        // No attachments... scan the body for the log...
        if (!($log = CabBodyCheck($msg))) {
          // Not Cabrillo - whistle up the help
          pd("      From: " . $msg['FROM'] . ' - ' .
             "NON-CABRILLO MESSAGE BODY");
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
        pd("      From: " . $msg['FROM'] . ' - ' .
           "Missing CONTEST field or NOT CQP log");
        pd("        -> HUMAN");
          
        $needHuman = NOTCQPCONTEST;
        break;
      }

      $call = CabGetCall($log);
      $fname = MakeLogName($call);

      if (!file_put_contents("$CABLOGS/$fname", $log)) {
        pd("  - Error writing $CABLOGS/$FNAME - aborting!");
        exit(1);
      }

      pd("    From: " . $msg['FROM']);
      pd("      Call: $call      File: $fname");

      // Generate a response back to the submitter
      if (!SendResponse($msg, $fname, $log)) {
        $needHuman = NOTCQPCONTEST;
        break;
      }

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
