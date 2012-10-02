<?php
//
// Email Robot for CQP
// - Email support routines to access an IMAP email account
//   to pull and thread logs for CQP.
//

require_once('/usr/local/lib/php/Mail.php');

// Global Data

// Queue for message moves before we logout
$MOVELIST = array();

// Handle for IMAP resource
$IMAP = '';

// IMAP account information

// Decleared in robot.php and grabed from command lien
global $USER, $PASS;

// Hard coded here...

$SERVER = 'imap.gmail.com';
$SMTP = 'smtp.gmail.com';

// Define for retry count
define('IMAPRETRY', '5');

//
// erLogin - login our email account
//

function erLogin() {
  global $IMAP, $MOVELIST, $USER, $PASS, $SERVER;

  $MOVELIST = array();
  imap_timeout(IMAP_OPENTIMEOUT, 5);
  imap_timeout(IMAP_READTIMEOUT, 5);
  imap_timeout(IMAP_WRITETIMEOUT, 5);

  $IMAP = imap_open('{' . "$SERVER" . ":993/imap/ssl" . '}' . "INBOX",
                    $USER, $PASS, NIL, IMAPRETRY);

  return ($IMAP ? FALSE : imap_last_error());
}


//
// erLogout - logout and delete any messages marked for deletion
// Move any messages to the appropriate folder before we logout

function erLogout() {
  global $IMAP, $MOVELIST;

  foreach ($MOVELIST as $mv) {
    imap_mail_copy($IMAP, $mv['msg'], $mv['folder'], CP_MOVE);
    imap_delete($IMAP, $mv['msg']);
  }

  imap_expunge($IMAP);
  imap_close($IMAP, CL_EXPUNGE);
}



//
// erNewCount - returns an array of message numbers of messages
// marked as UNSEEN by the server
//

function erNewCount() {
  global $IMAP;

  $msgs = imap_search($IMAP, "UNSEEN");
  if (!$msgs) {
    $msgs = array();
  }
  return $msgs;
}


// 
// erDelete - deletes the message specified
//

function erDelete($msg) {
  global $IMAP;

  imap_delete($IMAP, $msg);
}


//
// erMove - move the message to the specified mailbox
//

function erMoveMessage($msg, $mbox) {
  global $IMAP, $SERVER;
  global $MOVELIST;

  $MOVELIST[] = array('msg' => $msg, 'folder' => $mbox);
  return;
  
  // $fullMbox = $mbox;
  // imap_mail_move($IMAP, "$msg:$msg", $fullMbox);
}


//
// erSendMessage
//
// Sends a message 
//

function erSendMessage($from, $to, $subject, $body) {
  global $SMTP, $USER, $PASS;

  $headers = array(
    'From' => $from,
    'To'   => $to,
    'Subject' => $subject,
    'MIME-Version' => '1.',
    'Content-Type' => 'text/plain',
  );

  $smtp = Mail::factory(
    'smtp',
    array (
      'host' => "ssl://$SMTP",
      'port' => 465,		// SSL
      'auth' => TRUE,
      'username' => $USER,
      'password' => $PASS,
    ));

    $mail = $smtp->send($to, $headers, $body);
    
    if (PEAR::isError($mail)) {
      pd("    Error Sending email to $to");
      pd("    " . $mail->getMessage());
      exit(1);
    }
}



//
// erGetMessage - returns an array holding the contests of
// the selected message number
//
// The array is as follows:
// array (
//  'FROM' => 	  // From email address
//  'TO'   => 	  // To email address 
//  'DATE' =>     // Time stamp of email
//  'SUBJECT' =>  // Subject of message
//  'BODY' =>     // Email body
//  'HEADER' =>   // Full RFC-822 headers
//  'ATTACHMENTS' => array (
//                     'FILENAME' => // User supplied file name - no path
//                     'FILE'     => // Contents of file
//                   )
// );
//
// Note - the ATTACHMENTS array may be empty if no attachments are present
// on the message.
//
// Any filename is stripped of any path (with either / or \ delimiters).
//

function erGetMessage($msg) {
  global $IMAP;

  // Get the message and its contents
  $header = imap_headerinfo($IMAP, $msg);
  $structure = imap_fetchstructure($IMAP, $msg);
  $attachments = array();

  // If there are attachments on this message, grab them and decode
  // as necessary

  // print_r($structure);

  if(isset($structure->parts) && count($structure->parts)) {
    for($i = 0; $i < count($structure->parts); $i++) {

      $attachments[$i] = array(
        'is_attachment' => false,
        'filename' => '',
        'name' => '',
        'attachment' => ''
      );
    
      if($structure->parts[$i]->ifdparameters) {
        foreach($structure->parts[$i]->dparameters as $object) {
          if(strtolower($object->attribute) == 'filename') {
            $attachments[$i]['is_attachment'] = true;
            $attachments[$i]['filename'] = $object->value;
          }
        }
      }
    
      if($structure->parts[$i]->ifparameters) {
        foreach($structure->parts[$i]->parameters as $object) {
          if(strtolower($object->attribute) == 'name') {
             $attachments[$i]['is_attachment'] = true;
             $attachments[$i]['name'] = $object->value;
          }  
        }
      }
    
      if($attachments[$i]['is_attachment']) {
        $attachments[$i]['attachment'] = imap_fetchbody($IMAP, $msg, $i+1);
        if($structure->parts[$i]->encoding == 3) { // 3 = BASE64
          $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
        }
        elseif($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
          $attachments[$i]['attachment'] = imap_qprint($attachments[$i]['attachment']);
        }  
      }
    }
  }

  $m = array();
  $m['FROM'] = $header->from[0]->mailbox . '@' . $header->from[0]->host;
  $m['TO'] =   $header->to[0]->mailbox . '@' . $header->to[0]->host;  
  $m['SUBJECT'] = $header->subject ? $header->subject : '';
  $m['DATE'] = $header->date;
  $m['BODY'] = erGetBody($msg, $structure);
  $m['HEADERS'] = imap_fetchheader($IMAP, $msg);

  // Process attachments

  $att = array();

  foreach ($attachments as $a) {
    if ($a['is_attachment']) {
      // Remove any path info on the filename
      $fname = $a['filename'];

      $fname = preg_replace("/^.*\//", '', $fname);
      $fname = preg_replace("/^.*\\\/", '', $fname);

      $att[] = array('FILENAME' => $fname,
                     'FILE' => $a['attachment']);
    }
  }

  $m['ATTACHMENTS'] = $att;
  // print_r($m);
  return ($m);
}


//
// erGetBody($msg, $s)
//
// Gets the body for $msg depending on its structure in $s
//

function erGetBody($msg, $s) {
  global $IMAP;

  $body = '';

  // Check for the simple case...
  if ($s->subtype == 'PLAIN' || !$s->ifparameters) {
    $body = imap_fetchbody($IMAP, $msg, 1);
    $encoding = $s->encoding;
  } else {
    // This could be an HTML/TEXT encoded body...
    if ($s->subtype == 'ALTERNATIVE') {
      if(isset($s->parts) && count($s->parts)) {
        for($i = 0; $i < count($s->parts); $i++) {
          if ($s->parts[$i]->subtype == 'PLAIN') {
            break;
          }
        }

        if ($i != count($s->parts)) {
          // We have found the PLAIN part - retrieve it
          $body = imap_fetchbody($IMAP, $msg, $i+1);
          $encoding = $s->parts[$i]->encoding;
        } else {
          $body = '';
          $encoding = 0;
        }
      }
    }
  }

  if (!$body) {
    // No body that we can find - return the raw one...
    return(imap_body($IMAP, $msg));
  }

  switch ($encoding) {
    case 3:  // Base 64
      $body = base64_decode($body);
      break;

    case 4:  // Quoted printable
      $body = imap_qprint($body);
      break;
    
    default:
      break;
  }

  return($body);
}

?>
