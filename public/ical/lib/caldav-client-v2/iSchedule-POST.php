<?php
/**
* iScheduling POST handle remote iSchedule requests
*
* @package   davical
* @subpackage   iSchedule-POST
* @author    Rob Ostensen <rob@boxacle.net>
* @copyright Rob Ostensen
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once('iSchedule.php');
require_once('vComponent.php');
require_once('vCalendar.php');
require_once('WritableCollection.php');
include_once('freebusy-functions.php');


class FakeSession {
  function __construct($principal) {
    // Assign each field in the selected record to the object
    foreach( $principal AS $k => $v ) {
      $this->{$k} = $v;
    }
    $this->username = $principal->username();
    $this->user_no  = $principal->user_no();
    $this->principal_id = $principal->principal_id();
    $this->email = $principal->email();
    $this->dav_name = $principal->dav_name();
    $this->principal = $principal;

    $this->logged_in = true;

  }

  function AllowedTo($do_something) {
    return true;
  }
}

$d = new iSchedule ();
if ( $d->validateRequest ( ) ) {
  $ical = new vCalendar( $request->raw_post );
  $attendee = Array ();
  $addresses = Array ();
  $attendees = $ical->GetAttendees();
  foreach( $attendees AS $v ) {
    $email = preg_replace( '/^mailto:/i', '', $v->Value() );
    $addresses[] = $email;
  }
  $organizer = $ical->GetOrganizer();
  $addresses[] =  preg_replace( '/^mailto:/i', '', $organizer->Value() );
  $recipients = Array ();
  $attendees_ok = Array ();
  $attendees_fail = Array ();
  if ( strpos ( $_SERVER['HTTP_RECIPIENT'], ',' ) === false ) { // single recipient
    $recipients[] = $_SERVER['HTTP_RECIPIENT'];
  }
  else {
    $rcpt = explode ( ',', $_SERVER['HTTP_RECIPIENT'] );
    foreach ( $rcpt as $k => $v ) {
      $recipients[$k] = preg_replace( '/^mailto:/i', '', trim ( $v ) );
    }
  }
  if ( ! in_array ( preg_replace( '/^mailto:/i', '', $_SERVER['HTTP_ORIGINATOR'] ), $addresses ) ) { // should this be case sensitive?
    $request->DoResponse( 412, translate('sender must be organizer or attendee of event') );
  }
  foreach ( $recipients as $v ) {
    if ( ! in_array ( preg_replace( '/^mailto:/i', '', $v ), $addresses ) ) { // should this be case sensitive?
      dbg_error_log('ischedule','recipient missing from event ' . $v );
      $reply->XMLResponse( 403, translate('recipient must be organizer or attendee of event') . $v );
      continue;
    }
    $email = preg_replace( '/^mailto:/', '', $v );
    dbg_error_log('ischedule','recipient ' . $v );
    $schedule_target = new Principal('email',$email);
    if ( $schedule_target == false ){
      array_push ( $attendees_fail, $schedule_target );
      continue;
    }
    array_push ( $attendees_ok, $schedule_target );
    // TODO: add originator addressbook and existing event lookup as whitelist
  }
  $method =  $ical->GetPValue('METHOD');
  $content_type = explode ( ';', $_SERVER['CONTENT_TYPE'] );
  if ( $content_type[0] != 'text/calendar' )
    $reply->XMLResponse( 406, 'content must be text/calendar' );
  $content_parts = Array ();
  foreach ( $content_type as $v ) {
    list ( $a, $b ) = explode ( '=', trim ( $v ), 2 );
    $content_parts[strtolower($a)] = strtoupper($b);
  }
  if ( isset ( $content_parts['method'] ) )
    $method = $content_parts['method']; // override method from icalendar
  switch ( $method )
  {
    case 'REQUEST':
      if ( $content_parts['component'] == 'VFREEBUSY' ) {
        ischedule_freebusy_request ( $ical, $attendees_ok, $attendees_fail );
      }
      if ( $content_parts['component'] == 'VEVENT' ) {
        ischedule_request ( $ical, $attendees_ok, $attendees_fail );
        // scheduling event request
      }
      if ( $content_parts['component'] == 'VTODO' ) {
        ischedule_request ( $ical, $attendees_ok, $attendees_fail );
        // scheduling todo request
      }
      if ( $content_parts['component'] == 'VJOURNAL' ) {
        // scheduling journal request, not sure how to handle this or if it will ever by used
      }
      break;
    case 'REPLY':
        ischedule_request ( $ical, $attendees_ok, $attendees_fail );
      break;
    case 'ADD':
        ischedule_request ( $ical, $attendees_ok, $attendees_fail );
      break;
    case 'CANCEL':
        ischedule_request ( $ical, $attendees_ok, $attendees_fail );
      break;
    case 'PUBLISH':
      break;
    case 'REFRESH':
      break;
    case 'COUNTER':
      break;
    case 'DECLINECOUNTER':
      break;
    default:
      dbg_error_log('ischedule','invalid request' );
      $request->DoResponse( 400, translate('invalid request') );
  }
}
else {
  dbg_error_log('ischedule','invalid request' );
  $request->DoResponse( 400, translate('invalid request') );
}

function ischedule_freebusy_request( $ic, $attendees, $attendees_fail) {
  global $c, $session, $request;
  $reply = new XMLDocument( array( "urn:ietf:params:xml:ns:ischedule" => "I" ) );
  $icalAttendees = $ic->GetAttendees();
  $responses = array();
  $ical = $ic->GetComponents('VFREEBUSY');
  $ical = $ical[0];
  $fbq_start = $ical->GetPValue('DTSTART');
  $fbq_end   = $ical->GetPValue('DTEND');
  if ( ! ( isset($fbq_start) || isset($fbq_end) ) ) {
    $request->DoResponse( 400, 'All valid freebusy requests MUST contain a DTSTART and a DTEND' );
  }

  $range_start = new RepeatRuleDateTime($fbq_start);
  $range_end   = new RepeatRuleDateTime($fbq_end);

  foreach( $attendees AS $k => $attendee ) {
    $response = $reply->NewXMLElement("response", false, false, 'urn:ietf:params:xml:ns:ischedule');
    $fb = get_freebusy( '^'.$attendee->dav_name, $range_start, $range_end );

    $fb->AddProperty( 'UID',       $ical->GetPValue('UID') );
    $fb->SetProperties( $ical->GetProperties('ORGANIZER'), 'ORGANIZER');
    foreach ( $ical->GetProperties('ATTENDEE') as $at ) {
      if ( $at->Value() == 'mailto:' . $attendee->email )
        $fb->AddProperty( $at );
    }

    $vcal = new vCalendar( array('METHOD' => 'REPLY') );
    $vcal->AddComponent( $fb );

    $response = $reply->NewXMLElement( "response", false, false, 'urn:ietf:params:xml:ns:ischedule' );
    $response->NewElement( "recipient", 'mailto:'.$attendee->email, false, 'urn:ietf:params:xml:ns:ischedule' );
    $response->NewElement( "request-status", "2.0;Success", false, 'urn:ietf:params:xml:ns:ischedule' );
    $response->NewElement( "calendar-data", $vcal->Render(), false, 'urn:ietf:params:xml:ns:ischedule' );

    $responses[] = $response;
  }

  foreach ( $attendees_fail AS $k => $attendee ) {
    $XMLresponse = $reply->NewXMLElement("response", false, false, 'urn:ietf:params:xml:ns:ischedule');
    $XMLresponse->NewElement( "recipient", $reply->href('mailto:'.$attendee));
    $XMLresponse->NewElement( "request-status",'5.3;cannot schedule this user, unknown or access denied');
    $responses[] = $XMLresponse;
  }
  $response = $reply->NewXMLElement( "schedule-response", $responses, $reply->GetXmlNsArray(), 'urn:ietf:params:xml:ns:ischedule' );
  $request->XMLResponse( 200, $response );
}

function ischedule_request( $ic, $attendees, $attendees_fail ) {
  global $c, $session, $request;
  $oldSession = $session;
  $reply = new XMLDocument( array( "urn:ietf:params:xml:ns:ischedule" => "I" ) );
  $responses = array();
  $ical = $ic->GetComponents('VEVENT');
  $ical = $ical[0];

  foreach ( $attendees AS $k => $attendee ) {
    $XMLresponse = $reply->NewXMLElement("response", false, false, 'urn:ietf:params:xml:ns:ischedule');
    dbg_error_log('ischedule','scheduling event for ' .$attendee->email);
    $schedule_target = new Principal('email',$attendee->email);
    $response = '3.7'; // Attendee was not found on server.
    if ( $schedule_target->Exists() ) {
      $session = new FakeSession($schedule_target);
      $attendee_calendar = new WritableCollection(array('path' => $schedule_target->internal_url('schedule-default-calendar')));
      if ( !$attendee_calendar->Exists() ) {
        dbg_error_log('ERROR','Default calendar at "%s" does not exist for user "%s"',
        $attendee_calendar->dav_name(), $schedule_target->username());
        $response = '5.3;cannot schedule this user, unknown or access denied'; // No scheduling support for user
      }
      else {
        $attendee_inbox = new WritableCollection(array('path' => $schedule_target->internal_url('schedule-inbox')));
        if ( ! $attendee_inbox->HavePrivilegeTo('schedule-deliver-invite') ) {
          $response = '3.8;denied'; //  No authority to deliver invitations to user.
        }
        else if ( $attendee_inbox->WriteCalendarMember($ic, false) !== false ) {
          $response = '2.0;delivered'; // Scheduling invitation delivered successfully
        }
      }
      $session = $oldSession;
    }
    dbg_error_log( 'ischedule', 'Status for attendee <%s> set to "%s"', $attendee->email, $response );
    $XMLresponse->NewElement("recipient", 'mailto:'.$attendee->email, false, 'urn:ietf:params:xml:ns:ischedule' );
    $XMLresponse->NewElement("request-status", $response, false, 'urn:ietf:params:xml:ns:ischedule' );
    $responses[] = $XMLresponse;
  }

  foreach ( $attendees_fail AS $k => $attendee ) {
    $XMLresponse = $reply->NewXMLElement("response", false, false, 'urn:ietf:params:xml:ns:ischedule');
    $XMLresponse->NewElement("recipient", 'mailto:'.$attendee->email, false, 'urn:ietf:params:xml:ns:ischedule' );
    $XMLresponse->NewElement("request-status", '5.3;cannot schedule this user, unknown or access denied', false, 'urn:ietf:params:xml:ns:ischedule' );
    $responses[] = $XMLresponse;
  }

  $response = $reply->NewXMLElement( "schedule-response", $responses, $reply->GetXmlNsArray(), 'urn:ietf:params:xml:ns:ischedule' );
  $request->XMLResponse( 200, $response );
}

function ischedule_cancel( $ic, $attendees, $attendees_fail ) {
  global $c, $session, $request;
  $reply = new XMLDocument( array("DAV:" => "", "urn:ietf:params:xml:ns:caldav" => "C", "urn:ietf:params:xml:ns:ischedule" => "I" ) );
  $responses = array();
  $ical = $ic->GetComponents('VEVENT');
  $ical = $ical[0];

  foreach ( $attendees AS $k => $attendee ) {
    $XMLresponse = $reply->NewXMLElement("response", false, false, 'urn:ietf:params:xml:ns:ischedule');
    dbg_error_log('ischedule','scheduling event for ' .$attendee->email);
    $schedule_target = new Principal('email',$attendee->email);
    $response = '3.7'; // Attendee was not found on server.
    if ( $schedule_target->Exists() ) {
      $attendee_calendar = new WritableCollection(array('path' => $schedule_target->internal_url('schedule-default-calendar')));
      if ( !$attendee_calendar->Exists() ) {
        dbg_error_log('ERROR','Default calendar at "%s" does not exist for user "%s"',
        $attendee_calendar->dav_name(), $schedule_target->username());
        $response = '5.3;cannot schedule this user, unknown or access denied'; // No scheduling support for user
      }
      else {
        $attendee_inbox = new WritableCollection(array('path' => $schedule_target->internal_url('schedule-inbox')));
        if ( ! $attendee_inbox->HavePrivilegeTo('schedule-deliver-invite') ) {
          $response = '3.8;denied'; //  No authority to deliver invitations to user.
        }
        else if ( $attendee_inbox->WriteCalendarMember($ic, false) !== false ) {
          $response = '2.0;delivered'; // Scheduling invitation delivered successfully
        }
      }
    }
    dbg_error_log( 'PUT', 'Status for attendee <%s> set to "%s"', $attendee->email, $response );
    $XMLresponse->NewElement("recipient", $reply->href('mailto:'.$attendee->email), false, 'urn:ietf:params:xml:ns:ischedule' );
    $XMLresponse->NewElement("request-status", $response, false, 'urn:ietf:params:xml:ns:ischedule' );
    $responses[] = $XMLresponse;
  }

  foreach ( $attendees_fail AS $k => $attendee ) {
    $XMLresponse = $reply->NewXMLElement("response", false, false, 'urn:ietf:params:xml:ns:ischedule');
    $XMLresponse->NewElement("recipient", $reply->href('mailto:'.$attendee->email), false, 'urn:ietf:params:xml:ns:ischedule' );
    $XMLresponse->NewElement("request-status", '5.3;cannot schedule this user, unknown or access denied', false, 'urn:ietf:params:xml:ns:ischedule' );
    $responses[] = $XMLresponse;
  }

  $response = $reply->NewXMLElement( "schedule-response", $responses, $reply->GetXmlNsArray(), 'urn:ietf:params:xml:ns:ischedule' );
  $request->XMLResponse( 200, $response );
}

