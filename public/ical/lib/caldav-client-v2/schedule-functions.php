<?php
/**
 * Functions for handling CalDAV Scheduling.
 *
 * @package   davical
 * @subpackage   caldav
 * @author    Andrew McMillan <andrew@morphoss.com>
 * @copyright Morphoss Ltd - http://www.morphoss.com/
 * @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later version
 */

require_once('vCalendar.php');
require_once('WritableCollection.php');
require_once('RRule.php');

/**
 * Entry point for scheduling on DELETE, for which there are thee outcomes:
 *  - We don't do scheduling (disabled, no organizer, ...)
 *  - We are an ATTENDEE declining the meeting.
 *  - We are the ORGANIZER canceling the meeting.
 *
 * @param DAVResource $deleted_resource The resource which has already been deleted
 */
function do_scheduling_for_delete(DAVResource $deleted_resource ) {
  // By the time we arrive here the resource *has* actually been deleted from disk
  // we can only fail to (de-)schedule the activity...
  global $request, $c;

  if ( !isset($request) || (isset($c->enable_auto_schedule) && !$c->enable_auto_schedule) ) return true;
  if ( $deleted_resource->IsInSchedulingCollection() ) return true;

  $caldav_data = $deleted_resource->GetProperty('dav-data');
  if ( empty($caldav_data) ) return true;

  $vcal = new vCalendar($caldav_data);
  $organizer = $vcal->GetOrganizer();
  if ( $organizer === false || empty($organizer) ) {
    dbg_error_log( 'schedule', 'Event has no organizer - no scheduling required.' );
    return true;
  }
  if ( $vcal->GetScheduleAgent() != 'SERVER' ) {
    dbg_error_log( 'schedule', 'SCHEDULE-AGENT=%s - no scheduling required.', $vcal->GetScheduleAgent() );
    return true;
  }
  $organizer_email = preg_replace( '/^mailto:/i', '', $organizer->Value() );

  if ( $request->principal->email() == $organizer_email ) {
    return doItipOrganizerCancel( $vcal );
  }
  else {
    if ( isset($_SERVER['HTTP_SCHEDULE_REPLY']) && $_SERVER['HTTP_SCHEDULE_REPLY'] == 'F') {
      dbg_error_log( 'schedule', 'Schedule-Reply header set to "F" - no scheduling required.' );
      return true;
    }
    return doItipAttendeeReply( $vcal, 'DECLINED', $request->principal->email());
  }

}


/**
* Do the scheduling adjustments for a REPLY when an ATTENDEE updates their status.
* @param vCalendar $vcal The resource that the ATTENDEE is writing to their calendar
* @param string $partstat The participant status being replied
*/
//function do_scheduling_reply( vCalendar $vcal, vProperty $organizer ) {
function doItipAttendeeReply( vCalendar $resource, $partstat ) {
  global $request;

  $organizer = $resource->GetOrganizer();
  $organizer_email = preg_replace( '/^mailto:/i', '', $organizer->Value() );
  $organizer_principal = new Principal('email',$organizer_email );

  if ( !$organizer_principal->Exists() ) {
    dbg_error_log( 'schedule', 'Unknown ORGANIZER "%s" - unable to notify.', $organizer->Value() );
    //TODO: header( "Debug: Could maybe do the iMIP message dance for organizer ". $organizer->Value() );
    return true;
  }

  $sql = 'SELECT caldav_data.dav_name, caldav_data.caldav_data FROM caldav_data JOIN calendar_item USING(dav_id) ';
  $sql .= 'WHERE caldav_data.collection_id IN (SELECT collection_id FROM collection WHERE is_calendar AND user_no =?) ';
  $sql .= 'AND uid=? LIMIT 1';
  $uids = $resource->GetPropertiesByPath('/VCALENDAR/*/UID');
  if ( count($uids) == 0 ) {
    dbg_error_log( 'schedule', 'No UID in VCALENDAR - giving up on REPLY.' );
    return true;
  }
  $uid = $uids[0]->Value();
  $qry = new AwlQuery($sql, $organizer_principal->user_no(), $uid);
  if ( !$qry->Exec('schedule',__LINE__,__FILE__) || $qry->rows() < 1 ) {
    dbg_error_log( 'schedule', 'Could not find original event from organizer - giving up on REPLY.' );
    return true;
  }
  $row = $qry->Fetch();
  $collection_path = preg_replace('{/[^/]+$}', '/', $row->dav_name );
  $segment_name = str_replace($collection_path, '', $row->dav_name );
  $vcal = new vCalendar($row->caldav_data);

  $attendees = $vcal->GetAttendees();
  foreach( $attendees AS $v ) {
    $email = preg_replace( '/^mailto:/i', '', $v->Value() );
    if ( $email == $request->principal->email() ) {
      $attendee = $v;
      break;
    }
  }
  if ( empty($attendee) ) {
    dbg_error_log( 'schedule', 'Could not find ATTENDEE in VEVENT - giving up on REPLY.' );
    return true;
  }

  $attendee->SetParameterValue('PARTSTAT', $partstat);
  $attendee->SetParameterValue('SCHEDULE-STATUS', '2.0');
  $vcal->UpdateAttendeeStatus($request->principal->email(), clone($attendee) );

  $organizer_calendar = new WritableCollection(array('path' => $collection_path));
  $organizer_inbox = new WritableCollection(array('path' => $organizer_principal->internal_url('schedule-inbox')));

  $schedule_reply = GetItip(new vCalendar($vcal->Render(null, true)), 'REPLY', $attendee->Value(), array('CUTYPE'=>true, 'SCHEDULE-STATUS'=>true));
  $schedule_request = GetItip(new vCalendar($row->caldav_data), 'REQUEST', null);

  dbg_error_log( 'schedule', 'Writing ATTENDEE scheduling REPLY from %s to %s', $request->principal->email(), $organizer_principal->email() );

  $response = '3.7'; // Organizer was not found on server.
  if ( !$organizer_calendar->Exists() ) {
    if ( doImipMessage('REPLY', $organizer_principal->email(), $vcal) ) {
      $response = '1.1'; // Scheduling whoosit 'Sent'
    }
    else {
      dbg_error_log('ERROR','Default calendar at "%s" does not exist for user "%s"',
      $organizer_calendar->dav_name(), $schedule_target->username());
      $response = '5.2'; // No scheduling support for user
    }
  }
  else {
    if ( ! $organizer_inbox->HavePrivilegeTo('schedule-deliver-reply') ) {
      $response = '3.8'; // No authority to deliver replies to organizer.
    }
    $response = '1.2'; // Scheduling reply delivered successfully
    if ( $organizer_calendar->WriteCalendarMember($vcal, false, false, $segment_name) === false ) {
      dbg_error_log('ERROR','Could not write updated calendar member to %s', $attendee_calendar->dav_name() );
      trace_bug('Failed to write scheduling resource.');
    }
    $organizer_inbox->WriteCalendarMember($schedule_reply, false, false, $request->principal->username().$segment_name);
  }


  dbg_error_log( 'schedule', 'Status for organizer <%s> set to "%s"', $organizer->Value(), $response );
  $organizer->SetParameterValue( 'SCHEDULE-STATUS', $response );
  $resource->UpdateOrganizerStatus($organizer);   // Which was passed in by reference, and we're updating it here.

  // Now we loop through the *other* ATTENDEEs, updating them on the status of the ATTENDEE DECLINE/ACCEPT
  foreach( $attendees AS $attendee ) {
    $email = preg_replace( '/^mailto:/i', '', $attendee->Value() );
    if ( $email == $request->principal->email() || $email == $organizer_principal->email() ) continue;

    $agent = $attendee->GetParameterValue('SCHEDULE-AGENT');
    if ( !empty($agent) && $agent != 'SERVER' ) continue;

    $schedule_target = new Principal('email',$email);
    if ( $schedule_target->Exists() ) {
      $attendee_calendar = new WritableCollection(array('path' => $schedule_target->internal_url('schedule-default-calendar')));
      if ( !$attendee_calendar->Exists() ) {
        dbg_error_log('ERROR','Default calendar at "%s" does not exist for user "%s"',
                                              $attendee_calendar->dav_name(), $schedule_target->username());
        continue;
      }
      else {
        $attendee_inbox = new WritableCollection(array('path' => $schedule_target->internal_url('schedule-inbox')));
        if ( ! $attendee_inbox->HavePrivilegeTo('schedule-deliver-invite') ) continue;

        if ( $attendee_calendar->WriteCalendarMember($vcal, false) === false ) {
          dbg_error_log('ERROR','Could not write updated calendar member to %s', $attendee_calendar->dav_name());
          trace_bug('Failed to write scheduling resource.');
        }
        $attendee_inbox->WriteCalendarMember($schedule_request, false);
      }
    }
    else {
      //TODO: header( "Debug: Could maybe do the iMIP message dance for attendee ". $email );
    }
  }

  return true;
}

function GetItip( VCalendar $vcal, $method, $attendee_value, $clear_attendee_parameters = null ) {
  $iTIP = $vcal->GetItip($method, $attendee_value, $clear_attendee_parameters );
  $components = $iTIP->GetComponents();
  foreach( $components AS $comp ) {
    $comp->AddProperty('REQUEST-STATUS','2.0');
    $properties = array();
    foreach( $comp->GetProperties() AS $k=> $property ) {
      switch( $property->Name() ) {
        case 'DTSTART':
        case 'DTEND':
        case 'DUE':
          $when = new RepeatRuleDateTime($property);
          $new_prop = new vProperty($property->Name());
          $new_prop->Value($when->UTC());
          $properties[] = $new_prop;
          break;
        default:
          $properties[] = $property;
      }
    }
    $comp->SetProperties($properties);
  }
  return $iTIP;
}


/**
 * Handles sending the iTIP CANCEL messages to each ATTENDEE by the ORGANIZER.
 * @param vCalendar $vcal  What's being cancelled.
 */
function doItipOrganizerCancel( vCalendar $vcal ) {
  global $request;

  $attendees = $vcal->GetAttendees();
  if ( count($attendees) == 0 && count($old_attendees) == 0 ) {
    dbg_error_log( 'schedule', 'Event has no attendees - no scheduling required.', count($attendees) );
    return true;
  }

  dbg_error_log( 'schedule', 'Writing scheduling resources for %d attendees', count($attendees) );
  $scheduling_actions = false;

  $iTIP = GetItip($vcal, 'CANCEL', null);

  foreach( $attendees AS $attendee ) {
    $email = preg_replace( '/^mailto:/i', '', $attendee->Value() );
    if ( $email == $request->principal->email() ) {
      dbg_error_log( 'schedule', "not delivering to owner '%s'", $request->principal->email() );
      continue;
    }

    $agent = $attendee->GetParameterValue('SCHEDULE-AGENT');
    if ( $agent && $agent != 'SERVER' ) {
      dbg_error_log( 'schedule', "not delivering to %s, schedule agent set to value other than server", $email );
      continue;
    }
    $schedule_target = new Principal('email',$email);
    if ( !$schedule_target->Exists() ) {
      if ( doImipMessage('CANCEL', $email, $vcal) ) {
        $response = '1.1'; // Scheduling whoosit 'Sent'
      }
      else {
        $response = '3.7';
      }
    }
    else {
      $attendee_inbox = new WritableCollection(array('path' => $schedule_target->internal_url('schedule-inbox')));
      if ( ! $attendee_inbox->HavePrivilegeTo('schedule-deliver-invite') ) {
        dbg_error_log( 'schedule', "No authority to deliver invite to %s", $schedule_target->internal_url('schedule-inbox') );
        $response = '3.8';
      }
      else {
        $attendee_calendar = new WritableCollection(array('path' => $schedule_target->internal_url('schedule-default-calendar')));
        $response = processItipCancel( $vcal, $attendee, $attendee_calendar, $schedule_target );
        deliverItipCancel( $iTIP, $attendee, $attendee_inbox );
      }
    }
    dbg_error_log( 'schedule', 'Status for attendee <%s> set to "%s"', $attendee->Value(), $response );
    $attendee->SetParameterValue( 'SCHEDULE-STATUS', $response );
    $scheduling_actions = true;
  }

  return true;
}


/**
 * Does the actual processing of the iTIP CANCEL message on behalf of an ATTENDEE,
 * which generally means writing it into the ATTENDEE's default calendar.
 *
 * @param vCalendar $vcal The message.
 * @param vProperty $attendee
 * @param WritableCollection $attendee_calendar
 */
function processItipCancel( vCalendar $vcal, vProperty $attendee, WritableCollection $attendee_calendar, Principal $attendee_principal ) {
  global $request;

  dbg_error_log( 'schedule', 'Processing iTIP CANCEL to %s', $attendee->Value());
  //TODO: header( "Debug: Could maybe do the iMIP message dance for attendee ". $attendee->Value() );
  if ( !$attendee_calendar->Exists() ) {
    if ( doImipMessage('CANCEL', $attendee_principal->email(), $vcal) ) {
      return '1.1'; // Scheduling whoosit 'Sent'
    }
    else {
      dbg_error_log('ERROR', 'Default calendar at "%s" does not exist for attendee "%s"',
                                                  $attendee_calendar->dav_name(), $attendee->Value());
      return '5.2';  // No scheduling support for user
    }
  }

  $sql = 'SELECT caldav_data.dav_name FROM caldav_data JOIN calendar_item USING(dav_id) ';
  $sql .= 'WHERE caldav_data.collection_id IN (SELECT collection_id FROM collection WHERE is_calendar AND user_no =?) ';
  $sql .= 'AND uid=? LIMIT 1';
  $uids = $vcal->GetPropertiesByPath('/VCALENDAR/*/UID');
  if ( count($uids) == 0 ) {
    dbg_error_log( 'schedule', 'No UID in VCALENDAR - giving up on CANCEL processing.' );
    return '3.8';
  }
  $uid = $uids[0]->Value();
  $qry = new AwlQuery($sql, $attendee_principal->user_no(), $uid);
  if ( !$qry->Exec('schedule',__LINE__,__FILE__) || $qry->rows() < 1 ) {
    dbg_error_log( 'schedule', 'Could not find ATTENDEE copy of original event - not trying to DELETE it!' );
    return '1.2';
  }
  $row = $qry->Fetch();

  if ( $attendee_calendar->actualDeleteCalendarMember($row->dav_name) === false ) {
    dbg_error_log('ERROR', 'Could not delete calendar member %s for %s',
                                    $row->dav_name(), $attendee->Value());
    trace_bug('Failed to write scheduling resource.');
    return '5.2';
  }

  return '1.2';  // Scheduling invitation delivered successfully

}


/**
 * Delivers the iTIP CANCEL message to an ATTENDEE's Scheduling Inbox Collection.
 *
 * This is pretty simple at present, but could be extended in the future to do the sending
 * of e-mail to a remote attendee.
 *
 * @param vCalendar $iTIP
 * @param vProperty $attendee
 * @param WritableCollection $attendee_inbox
 */
function deliverItipCancel( vCalendar $iTIP, vProperty $attendee, WritableCollection $attendee_inbox ) {
  $attendee_inbox->WriteCalendarMember($iTIP, false);
  //TODO: header( "Debug: Could maybe do the iMIP message dance canceling for attendee: ".$attendee->Value());
}

require_once('Multipart.php');
require_once('EMail.php');

/**
 * Send an iMIP message since they look like a non-local user.
 *
 * @param string $method The METHOD parameter from the iTIP
 * @param string $to_email The e-mail address we're going to send to
 * @param vCalendar $vcal The iTIP part of the message.
 */
function doImipMessage($method, $to_email, vCalendar $itip) {
  global $c, $request;

  dbg_error_log( 'schedule', 'Sending iMIP %s message to %s', $method, $to_email );
  $mime = new MultiPart();
  $mime->addPart( $itip->Render(), 'text/calendar; charset=UTF-8; method='.$method );

  $friendly_part = isset($c->iMIP->template[$method]) ? $c->iMIP->template[$method] : <<<EOTEMPLATE
This is a meeting ##METHOD## which your e-mail program should be able to
import into your calendar.  Alternatively you could save the attachment
and load that into your calendar instead.
EOTEMPLATE;

  $components = $itip->GetComponents( 'VTIMEZONE',false);

  $replaceable = array( 'METHOD', 'DTSTART', 'DTEND', 'SUMMARY', 'DESCRIPTION', 'URL' );
  foreach( $replaceable AS $pname ) {
    $search = '##'.$pname.'##';
    if ( strstr($friendly_part,$search) !== false ) {
      $property = $itip->GetProperty($pname);
      if ( empty($property) )
        $property = $components[0]->GetProperty($pname);

      if ( empty($property) )
         $replace = '';
      else {
        switch( $pname ) {
          case 'DTSTART':
          case 'DTEND':
            $when = new RepeatRuleDateTime($property);
            $replace = $when->format('c');
            break;
          default:
            $replace = $property->GetValue();
        }
      }
      $friendly_part  = str_replace($search, $replace, $friendly_part);
    }
  }
  $mime->addPart( $friendly_part, 'text/plain' );

  $email = new EMail();
  $email->SetFrom($request->principal->email());
  $email->AddTo($to_email);

  $email->SetSubject( $components[0]->GetPValue('SUMMARY') );
  $email->SetBody($mime->getMimeParts());

  if ( isset($c->iMIP->pretend_email) ) {
    $email->Pretend($mime->getMimeHeaders());
  }
  else if ( !isset($c->iMIP->send_email) || !$c->iMIP->send_email) {
    $email->PretendLog($mime->getMimeHeaders());
  }
  else {
    $email->Send($mime->getMimeHeaders());
  }
}
