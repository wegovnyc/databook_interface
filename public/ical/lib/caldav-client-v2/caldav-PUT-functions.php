<?php
/**
* CalDAV Server - handle PUT method
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@morphoss.com>
* @copyright Morphoss Ltd - http://www.morphoss.com/
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later version
*/

/**
* Check if the user wants to put just one VEVENT/VTODO or a whole calendar
* if the collection = calendar = $request_container doesn't exist then create it
* return true if it's a whole calendar
*/

require_once('AwlCache.php');
require_once('vComponent.php');
require_once('vCalendar.php');
require_once('WritableCollection.php');
require_once('schedule-functions.php');
include_once('iSchedule.php');
include_once('RRule.php');

$bad_events = null;

/**
* A regex which will match most reasonable timezones acceptable to PostgreSQL.
*/
$GLOBALS['tz_regex'] = ':^(Africa|America|Antarctica|Arctic|Asia|Atlantic|Australia|Brazil|Canada|Chile|Etc|Europe|Indian|Mexico|Mideast|Pacific|US)/[a-z_]+$:i';

/**
* This function launches an error
* @param boolean $caldav_context Whether we are responding via CalDAV or interactively
* @param int $user_no the user who will receive this ics file
* @param string $path the $path where the PUT failed to store such as /user_foo/home/
* @param string $message An optional error message to return to the client
* @param int $error_no An optional value for the HTTP error code
*/
function rollback_on_error( $caldav_context, $user_no, $path, $message='', $error_no=500 ) {
  global $c, $bad_events;
  if ( !$message ) $message = translate('Database error');
  $qry = new AwlQuery();
  if ( $qry->TransactionState() != 0 ) $qry->Rollback();
  if ( $caldav_context ) {
    if ( isset($bad_events) && isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import ) {
      $bad_events[] = $message;
    }
    else {
      global $request;
      $request->DoResponse( $error_no, $message );
    }
    // and we don't return from that, ever...
  }

  $c->messages[] = sprintf(translate('Status: %d, Message: %s, User: %d, Path: %s'), $error_no, $message, $user_no, $path);

}



/**
* Work out the location we are doing the PUT to, and check that we have the rights to
* do the needful.
* @param string $username The name of the destination user
* @param int $user_no The user making the change
* @param string $path The DAV path the resource is bing PUT to
* @param boolean $caldav_context Whether we are responding via CalDAV or interactively
* @param boolean $public Whether the collection will be public, should we need to create it
*/
function controlRequestContainer( $username, $user_no, $path, $caldav_context, $public = null ) {
  global $c, $request, $bad_events;

  // Check to see if the path is like /foo /foo/bar or /foo/bar/baz etc. (not ending with a '/', but contains at least one)
  if ( preg_match( '#^(.*/)([^/]+)$#', $path, $matches ) ) {//(
    $request_container = $matches[1];   // get everything up to the last '/'
  }
  else {
    // In this case we must have a URL with a trailing '/', so it must be a collection.
    $request_container = $path;
  }

  if ( isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import ) {
    $bad_events = array();
  }

  /**
  * Before we write the event, we check the container exists, creating it if it doesn't
  */
  if ( $request_container == "/$username/" ) {
    /**
    * Well, it exists, and we support it, but it is against the CalDAV spec
    */
    dbg_error_log( 'WARN', ' Storing events directly in user\'s base folders is not recommended!');
  }
  else {
    $sql = 'SELECT * FROM collection WHERE dav_name = :dav_name';
    $qry = new AwlQuery( $sql, array( ':dav_name' => $request_container) );
    if ( ! $qry->Exec('PUT',__LINE__,__FILE__) ) {
      rollback_on_error( $caldav_context, $user_no, $path, 'Database error in: '.$sql );
    }
    if ( !isset($c->readonly_webdav_collections) || $c->readonly_webdav_collections == true ) {
      if ( $qry->rows() == 0 ) {
        $request->DoResponse( 405 ); // Method not allowed
      }
      return;
    }
    if ( $qry->rows() == 0 ) {
      if ( $public == true ) $public = 't'; else $public = 'f';
      if ( preg_match( '{^(.*/)([^/]+)/$}', $request_container, $matches ) ) {
        $parent_container = $matches[1];
        $displayname = $matches[2];
      }
      $sql = 'INSERT INTO collection ( user_no, parent_container, dav_name, dav_etag, dav_displayname, is_calendar, created, modified, publicly_readable, resourcetypes )
VALUES( :user_no, :parent_container, :dav_name, :dav_etag, :dav_displayname, TRUE, current_timestamp, current_timestamp, :is_public::boolean, :resourcetypes )';
      $params = array(
      ':user_no' => $user_no,
      ':parent_container' => $parent_container,
      ':dav_name' => $request_container,
      ':dav_etag' => md5($user_no. $request_container),
      ':dav_displayname' => $displayname,
      ':is_public' => $public,
      ':resourcetypes' => '<DAV::collection/><urn:ietf:params:xml:ns:caldav:calendar/>'
      );
      $qry->QDo( $sql, $params );
    }
    else if ( isset($public) ) {
      $collection = $qry->Fetch();
      if ( empty($collection->is_public) ) $collection->is_public = 'f';
      if ( $collection->is_public == ($public?'t':'f')  ) {
        $sql = 'UPDATE collection SET publicly_readable = :is_public::boolean WHERE collection_id = :collection_id';
        $params = array( ':is_public' => ($public?'t':'f'), ':collection_id' => $collection->collection_id );
        if ( ! $qry->QDo($sql,$params) ) {
          rollback_on_error( $caldav_context, $user_no, $path, 'Database error in: '.$sql );
        }
      }
    }

  }
}


/**
* Check if this collection should force all events to be PUBLIC.
* @param string $user_no the user that owns the collection
* @param string $dav_name the collection to check
* @return boolean Return true if public events only are allowed.
*/
function public_events_only( $user_no, $dav_name ) {
  global $c;

  $sql = 'SELECT public_events_only FROM collection WHERE dav_name = :dav_name';

  $qry = new AwlQuery($sql, array(':dav_name' => $dav_name) );

  if( $qry->Exec('PUT',__LINE__,__FILE__) && $qry->rows() == 1 ) {
    $collection = $qry->Fetch();

    if ($collection->public_events_only == 't') {
      return true;
    }
  }

  // Something went wrong, must be false.
  return false;
}


/**
 * Get a TZID string from this VEVENT/VTODO/... component if we can
 * @param vComponent $comp
 * @return The TZID value we found, or null
 */
function GetTZID( vComponent $comp ) {
  $p = $comp->GetProperty('DTSTART');
  if ( !isset($p) && $comp->GetType() == 'VTODO' ) {
    $p = $comp->GetProperty('DUE');
  }
  if ( !isset($p) ) return null;
  return $p->GetParameterValue('TZID');
}


/**
* Deliver scheduling requests to attendees
* @param vComponent $ical the VCALENDAR to deliver
*/
function handle_schedule_request( $ical ) {
  global $c, $session, $request;
  $resources = $ical->GetComponents('VTIMEZONE',false);
  $ic = $resources[0];
  $etag = md5 ( $request->raw_post );
  $reply = new XMLDocument( array("DAV:" => "", "urn:ietf:params:xml:ns:caldav" => "C" ) );
  $responses = array();

  $attendees = $ic->GetProperties('ATTENDEE');
  $wr_attendees = $ic->GetProperties('X-WR-ATTENDEE');
  if ( count ( $wr_attendees ) > 0 ) {
    dbg_error_log( "PUT", "Non-compliant iCal request.  Using X-WR-ATTENDEE property" );
    foreach( $wr_attendees AS $k => $v ) {
      $attendees[] = $v;
    }
  }
  dbg_error_log( "PUT", "Attempting to deliver scheduling request for %d attendees", count($attendees) );

  foreach( $attendees AS $k => $attendee ) {
    $attendee_email = preg_replace( '/^mailto:/', '', $attendee->Value() );
    if ( $attendee_email == $request->principal->email() ) {
      dbg_error_log( "PUT", "not delivering to owner" );
      continue;
    }
    if ( $attendee->GetParameterValue ( 'PARTSTAT' ) != 'NEEDS-ACTION' || preg_match ( '/^[35]\.[3-9]/',  $attendee->GetParameterValue ( 'SCHEDULE-STATUS' ) ) ) {
      dbg_error_log( "PUT", "attendee %s does not need action", $attendee_email );
      continue;
    }

    if ( isset($c->enable_auto_schedule) && !$c->enable_auto_schedule ) {
      // In this case we're being asked not to do auto-scheduling, so we build
      // a response back for the client saying we can't...
      $attendee->SetParameterValue ('SCHEDULE-STATUS','5.3;No scheduling support for user');
      continue;
    }

    dbg_error_log( "PUT", "Delivering to %s", $attendee_email );

    $attendee_principal = new DAVPrincipal ( array ('email'=>$attendee_email, 'options'=> array ( 'allow_by_email' => true ) ) );
    if ( ! $attendee_principal->Exists() ){
      $attendee->SetParameterValue ('SCHEDULE-STATUS','5.3;No scheduling support for user');
      continue;
    }
    $deliver_path = $attendee_principal->internal_url('schedule-inbox');

    $ar = new DAVResource($deliver_path);
    $priv =  $ar->HavePrivilegeTo('schedule-deliver-invite' );
    if ( ! $ar->HavePrivilegeTo('schedule-deliver-invite' ) ){
      $reply = new XMLDocument( array('DAV:' => '') );
      $privnodes = array( $reply->href($attendee_principal->url('schedule-inbox')), new XMLElement( 'privilege' ) );
      // RFC3744 specifies that we can only respond with one needed privilege, so we pick the first.
      $reply->NSElement( $privnodes[1], 'schedule-deliver-invite' );
      $xml = new XMLElement( 'need-privileges', new XMLElement( 'resource', $privnodes) );
      $xmldoc = $reply->Render('error',$xml);
      $request->DoResponse( 403, $xmldoc, 'text/xml; charset="utf-8"');
    }


    $attendee->SetParameterValue ('SCHEDULE-STATUS','1.2;Scheduling message has been delivered');
    $ncal = new vCalendar( array('METHOD' => 'REQUEST') );
    $ncal->AddComponent( array_merge( $ical->GetComponents('VEVENT',false), array($ic) ));
    $content = $ncal->Render();
    $cid = $ar->GetProperty('collection_id');
    dbg_error_log('DELIVER', 'to user: %s, to path: %s, collection: %s, from user: %s, caldata %s', $attendee_principal->user_no(), $deliver_path, $cid, $request->user_no, $content );
    $item_etag = md5($content);
    write_resource( new DAVResource($deliver_path . $etag . '.ics'), $content, $ar, $request->user_no, $item_etag,
                    $put_action_type='INSERT', $caldav_context=true, $log_action=true, $etag );
    $attendee->SetParameterValue ('SCHEDULE-STATUS','1.2;Scheduling message has been delivered');
  }
  // don't write an entry in the out box, ical doesn't delete it or ever read it again
  $ncal = new vCalendar(array('METHOD' => 'REQUEST'));
  $ncal->AddComponent ( array_merge ( $ical->GetComponents('VEVENT',false) , array ($ic) ));
  $content = $ncal->Render();
  $deliver_path = $request->principal->internal_url('schedule-inbox');
  $ar = new DAVResource($deliver_path);
  $item_etag = md5($content);
  write_resource( new DAVResource($deliver_path . $etag . '.ics'), $content, $ar, $request->user_no, $item_etag,
                     $put_action_type='INSERT', $caldav_context=true, $log_action=true, $etag );
  //$etag = md5($content);
  header('ETag: "'. $etag . '"' );
  header('Schedule-Tag: "'.$etag . '"' );
  $request->DoResponse( 201, 'Created' );
}


/**
* Deliver scheduling replies to organizer and other attendees
* @param vComponent $ical the VCALENDAR to deliver
* @return false on error
*/
function handle_schedule_reply ( vCalendar $ical ) {
  global $c, $session, $request;
  $resources = $ical->GetComponents('VTIMEZONE',false);
  $ic = $resources[0];
  $etag = md5 ( $request->raw_post );
  $organizer = $ical->GetOrganizer();
  $arrayOrganizer = array($organizer);
  // for now we treat events with out organizers as an error
  if ( empty( $arrayOrganizer ) ) return false;

  $attendees = array_merge($arrayOrganizer,$ical->GetAttendees());
  dbg_error_log( "PUT", "Attempting to deliver scheduling request for %d attendees", count($attendees) );

  foreach( $attendees AS $k => $attendee ) {
    $attendee_email = preg_replace( '/^mailto:/i', '', $attendee->Value() );
    dbg_error_log( "PUT", "Delivering to %s", $attendee_email );
    $attendee_principal = new DAVPrincipal ( array ('email'=>$attendee_email, 'options'=> array ( 'allow_by_email' => true ) ) );
    $deliver_path = $attendee_principal->internal_url('schedule-inbox');
    $attendee_email = preg_replace( '/^mailto:/i', '', $attendee->Value() );
    if ( $attendee_email == $request->principal->email ) {
      dbg_error_log( "PUT", "not delivering to owner" );
      continue;
    }
    $ar = new DAVResource($deliver_path);
    if ( ! $ar->HavePrivilegeTo('schedule-deliver-reply' ) ){
      $reply = new XMLDocument( array('DAV:' => '') );
      $privnodes = array( $reply->href($attendee_principal->url('schedule-inbox')), new XMLElement( 'privilege' ) );
      // RFC3744 specifies that we can only respond with one needed privilege, so we pick the first.
      $reply->NSElement( $privnodes[1], 'schedule-deliver-reply' );
      $xml = new XMLElement( 'need-privileges', new XMLElement( 'resource', $privnodes) );
      $xmldoc = $reply->Render('error',$xml);
      $request->DoResponse( 403, $xmldoc, 'text/xml; charset="utf-8"' );
      continue;
    }

    $ncal = new vCalendar( array('METHOD' => 'REPLY') );
    $ncal->AddComponent ( array_merge ( $ical->GetComponents('VEVENT',false) , array ($ic) ));
    $content = $ncal->Render();
    write_resource( new DAVResource($deliver_path . $etag . '.ics'), $content, $ar, $request->user_no, md5($content),
                       $put_action_type='INSERT', $caldav_context=true, $log_action=true, $etag );
  }
  $request->DoResponse( 201, 'Created' );
}


/**
 * Do the scheduling adjustments for a REPLY when an ATTENDEE updates their status.
 * @param vCalendar $resource The resource that the ATTENDEE is writing to their calendar
 * @param string $organizer The property which is the event ORGANIZER.
 */
function do_scheduling_reply( vCalendar $resource, vProperty $organizer ) {
  global $request;
  $organizer_email = preg_replace( '/^mailto:/i', '', $organizer->Value() );
  $organizer_principal = new Principal('email',$organizer_email );
  if ( !$organizer_principal->Exists() ) {
    dbg_error_log( 'PUT', 'Organizer "%s" not found - cannot perform scheduling reply.', $organizer );
    return false;
  }
  $sql = 'SELECT caldav_data.dav_name, caldav_data.caldav_data FROM caldav_data JOIN calendar_item USING(dav_id) ';
  $sql .= 'WHERE caldav_data.collection_id IN (SELECT collection_id FROM collection WHERE is_calendar AND user_no =?) ';
  $sql .= 'AND uid=? LIMIT 1';
  $uids = $resource->GetPropertiesByPath('/VCALENDAR/*/UID');
  if ( count($uids) == 0 ) {
    dbg_error_log( 'PUT', 'No UID in VCALENDAR - giving up on REPLY.' );
    return false;
  }
  $uid = $uids[0]->Value();
  $qry = new AwlQuery($sql,$organizer_principal->user_no(), $uid);
  if ( !$qry->Exec('PUT',__LINE__,__FILE__) || $qry->rows() < 1 ) {
    dbg_error_log( 'PUT', 'Could not find original event from organizer - giving up on REPLY.' );
    return false;
  }
  $row = $qry->Fetch();
  $attendees = $resource->GetAttendees();
  foreach( $attendees AS $v ) {
    $email = preg_replace( '/^mailto:/i', '', $v->Value() );
    if ( $email == $request->principal->email() ) {
      $attendee = $v;
    }
  }
  if ( empty($attendee) ) {
    dbg_error_log( 'PUT', 'Could not find ATTENDEE in VEVENT - giving up on REPLY.' );
    return false;
  }
  $schedule_original = new vCalendar($row->caldav_data);
  $attendee->SetParameterValue('SCHEDULE-STATUS', '2.0');
  $schedule_original->UpdateAttendeeStatus($request->principal->email(), clone($attendee) );

  $collection_path = preg_replace('{/[^/]+$}', '/', $row->dav_name );
  $segment_name = str_replace($collection_path, '', $row->dav_name );
  $organizer_calendar = new WritableCollection(array('path' => $collection_path));
  $organizer_inbox = new WritableCollection(array('path' => $organizer_principal->internal_url('schedule-inbox')));

  $schedule_reply = GetItip(new vCalendar($schedule_original->Render(null, true)), 'REPLY', $attendee->Value(), array('CUTYPE'=>true, 'SCHEDULE-STATUS'=>true));

  dbg_error_log( 'PUT', 'Writing scheduling REPLY from %s to %s', $request->principal->email(), $organizer_principal->email() );

  $response = '3.7'; // Organizer was not found on server.
  if ( !$organizer_calendar->Exists() ) {
    dbg_error_log('ERROR','Default calendar at "%s" does not exist for user "%s"',
                              $organizer_calendar->dav_name(), $organizer_principal->username());
    $response = '5.2'; // No scheduling support for user
  }
  else {
    if ( ! $organizer_inbox->HavePrivilegeTo('schedule-deliver-reply') ) {
      $response = '3.8'; // No authority to deliver replies to organizer.
    }
    else if ( $organizer_inbox->WriteCalendarMember($schedule_reply, false, false, $request->principal->username().$segment_name) !== false ) {
      $response = '1.2'; // Scheduling reply delivered successfully
      if ( $organizer_calendar->WriteCalendarMember($schedule_original, false, false, $segment_name) === false ) {
        dbg_error_log('ERROR','Could not write updated calendar member to %s',
                $organizer_calendar->dav_name());
        trace_bug('Failed to write scheduling resource.');
      }
    }
  }

  $schedule_request = clone($schedule_original);
  $schedule_request->AddProperty('METHOD', 'REQUEST');

  dbg_error_log( 'PUT', 'Status for organizer <%s> set to "%s"', $organizer->Value(), $response );
  $organizer->SetParameterValue( 'SCHEDULE-STATUS', $response );
  $resource->UpdateOrganizerStatus($organizer);
  $scheduling_actions = true;

  $calling_attendee = clone($attendee);
  $attendees = $schedule_original->GetAttendees();
  foreach( $attendees AS $attendee ) {
    $email = preg_replace( '/^mailto:/i', '', $attendee->Value() );
    if ( $email == $request->principal->email() || $email == $organizer_principal->email() ) continue;

    $agent = $attendee->GetParameterValue('SCHEDULE-AGENT');
    if ( $agent && $agent != 'SERVER' ) {
      dbg_error_log( "PUT", "not delivering to %s, schedule agent set to value other than server", $email );
      continue;
    }

    // an attendee's reply should modify only the PARTSTAT on other attendees' objects
    // other properties (that might have been adjusted individually by those other
    // attendees) should remain unmodified. Therefore, we have to make $schedule_original
    // and $schedule_request be initialized by each attendee's object here.
    $attendee_principal = new DAVPrincipal ( array ('email'=>$email, 'options'=> array ( 'allow_by_email' => true ) ) );
    if ( ! $attendee_principal->Exists() ){
      dbg_error_log( 'PUT', 'Could not find attendee %s', $email);
      continue;
    }
    $sql = 'SELECT caldav_data.dav_name, caldav_data.caldav_data, caldav_data.collection_id FROM caldav_data JOIN calendar_item USING(dav_id) ';
    $sql .= 'WHERE caldav_data.collection_id IN (SELECT collection_id FROM collection WHERE is_calendar AND user_no =?) ';
    $sql .= 'AND uid=? LIMIT 1';
    $qry = new AwlQuery($sql,$attendee_principal->user_no(), $uid);
    if ( !$qry->Exec('PUT',__LINE__,__FILE__) || $qry->rows() < 1 ) {
      dbg_error_log( 'PUT', "Could not find attendee's event %s", $uid );
    }
    $row = $qry->Fetch();
    $schedule_original = new vCalendar($row->caldav_data);
    $schedule_original->UpdateAttendeeStatus($request->principal->email(), clone($calling_attendee) );
    $schedule_request = clone($schedule_original);
    $schedule_request->AddProperty('METHOD', 'REQUEST');

    $schedule_target = new Principal('email',$email);
    $response = '3.7'; // Attendee was not found on server.
    if ( $schedule_target->Exists() ) {
      // Instead of always writing to schedule-default-calendar, we first try to
      // find a calendar with an existing instance of the event in any calendar of this attendee.
      $r = new DAVResource($row);
      $attendee_calendar = new WritableCollection(array('path' => $r->parent_path()));
      if ($attendee_calendar->IsCalendar()) {
        dbg_error_log( 'PUT', "found the event in attendee's calendar %s", $attendee_calendar->dav_name() );
      } else {
        dbg_error_log( 'PUT', 'could not find the event in any calendar, using schedule-default-calendar');
        $attendee_calendar = new WritableCollection(array('path' => $schedule_target->internal_url('schedule-default-calendar')));
      }
      if ( !$attendee_calendar->Exists() ) {
        dbg_error_log('ERROR','Default calendar at "%s" does not exist for user "%s"',
        $attendee_calendar->dav_name(), $schedule_target->username());
        $response = '5.2'; // No scheduling support for user
      }
      else {
        $attendee_inbox = new WritableCollection(array('path' => $schedule_target->internal_url('schedule-inbox')));
        if ( ! $attendee_inbox->HavePrivilegeTo('schedule-deliver-invite') ) {
          $response = '3.8'; //  No authority to deliver invitations to user.
        }
        else if ( $attendee_inbox->WriteCalendarMember($schedule_request, false) !== false ) {
          $response = '1.2'; // Scheduling invitation delivered successfully
          if ( $attendee_calendar->WriteCalendarMember($schedule_original, false) === false ) {
            dbg_error_log('ERROR','Could not write updated calendar member to %s',
                    $attendee_calendar->dav_name(), $attendee_calendar->dav_name(), $schedule_target->username());
            trace_bug('Failed to write scheduling resource.');
          }
        }
      }
    }
    dbg_error_log( 'PUT', 'Status for attendee <%s> set to "%s"', $attendee->Value(), $response );
    $attendee->SetParameterValue( 'SCHEDULE-STATUS', $response );
    $scheduling_actions = true;

    $resource->UpdateAttendeeStatus($email, clone($attendee));

  }

  return $scheduling_actions;
}


/**
* Create/Update the scheduling requests for this resource.  This includes updating
* the scheduled user's default calendar.
* @param vComponent $resource The VEVENT/VTODO/... resource we are scheduling
* @param boolean $create true if the scheduling requests are being created.
* @return true If there was any scheduling action
*/
function do_scheduling_requests( vCalendar $resource, $create, $old_data = null ) {
  global $request, $c;
  if ( !isset($request) || (isset($c->enable_auto_schedule) && !$c->enable_auto_schedule) ) return false;

  if ( ! is_object($resource) ) {
    trace_bug( 'do_scheduling_requests called with non-object parameter (%s)', gettype($resource) );
    return  false;
  }

  $organizer = $resource->GetOrganizer();
  if ( $organizer === false || empty($organizer) ) {
    dbg_error_log( 'PUT', 'Event has no organizer - no scheduling required.' );
    return false;
  }
  $organizer_email = preg_replace( '/^mailto:/i', '', $organizer->Value() );

  // force re-render the object (to get the same representation for all attendiees)
  $resource->Render(null, true);

  if ( $request->principal->email() != $organizer_email ) {
    return do_scheduling_reply($resource,$organizer);
  }

  // required because we set the schedule status on the original object (why clone() not works here?)
  $orig_resource = new vCalendar($resource->Render(null, true));

  $schedule_request = new vCalendar($resource->Render(null, true));
  $schedule_request->AddProperty('METHOD', 'REQUEST');

  $old_attendees = array();
  if ( !empty($old_data) ) {
    $old_resource = new vCalendar($old_data);
    $old_attendees = $old_resource->GetAttendees();
  }
  $attendees = $resource->GetAttendees();
  if ( count($attendees) == 0 && count($old_attendees) == 0 ) {
    dbg_error_log( 'PUT', 'Event has no attendees - no scheduling required.', count($attendees) );
    return false;
  }
  $removed_attendees = array();
  foreach( $old_attendees AS $attendee ) {
    $email = preg_replace( '/^mailto:/i', '', $attendee->Value() );
    if ( $email == $request->principal->email() ) continue;
    $removed_attendees[$email] = $attendee;
  }

  $uids = $resource->GetPropertiesByPath('/VCALENDAR/*/UID');
  if ( count($uids) == 0 ) {
    dbg_error_log( 'PUT', 'No UID in VCALENDAR - giving up on REPLY.' );
    return false;
  }
  $uid = $uids[0]->Value();

  dbg_error_log( 'PUT', 'Writing scheduling resources for %d attendees', count($attendees) );
  $scheduling_actions = false;
  foreach( $attendees AS $attendee ) {
    $email = preg_replace( '/^mailto:/i', '', $attendee->Value() );
    if ( $email == $request->principal->email() ) {
      dbg_error_log( "PUT", "not delivering to owner '%s'", $request->principal->email() );
      continue;
    }

    if ( $create ) {
      $attendee_is_new = true;
    }
    else {
      $attendee_is_new = !isset($removed_attendees[$email]);
      if ( !$attendee_is_new ) unset($removed_attendees[$email]);
    }

    $agent = $attendee->GetParameterValue('SCHEDULE-AGENT');
    if ( $agent && $agent != 'SERVER' ) {
      dbg_error_log( "PUT", "not delivering to %s, schedule agent set to value other than server", $email );
      continue;
    }
    $schedule_target = new Principal('email',$email);
    $response = '3.7';  // Attendee was not found on server.
    dbg_error_log( 'PUT', 'Handling scheduling resources for %s on %s which is %s', $email,
                     ($create?'create':'update'), ($attendee_is_new? 'new' : 'an update') );
    if ( $schedule_target->Exists() ) {
      // Instead of always writing to schedule-default-calendar, we first try to
      // find a calendar with an existing instance of the event.
      $sql = 'SELECT caldav_data.dav_name, caldav_data.caldav_data, caldav_data.collection_id FROM caldav_data JOIN calendar_item USING(dav_id) ';
      $sql .= 'WHERE caldav_data.collection_id IN (SELECT collection_id FROM collection WHERE is_calendar AND user_no =?) ';
      $sql .= 'AND uid=? LIMIT 1';
      $qry = new AwlQuery($sql,$schedule_target->user_no(), $uid);
      if ( !$qry->Exec('PUT',__LINE__,__FILE__) || $qry->rows() < 1 ) {
        dbg_error_log( 'PUT', "Could not find event in attendee's calendars" );
        $attendee_calendar = new WritableCollection(array('path' => $schedule_target->internal_url('schedule-default-calendar')));
      } else {
        $row = $qry->Fetch();
        $r = new DAVResource($row);
        $attendee_calendar = new WritableCollection(array('path' => $r->parent_path()));
        if ($attendee_calendar->IsCalendar()) {
          dbg_error_log( 'PUT', "found the event in attendee's calendar %s", $attendee_calendar->dav_name() );
        } else {
          dbg_error_log( 'PUT', 'could not find the event in any calendar, using schedule-default-calendar');
          $attendee_calendar = new WritableCollection(array('path' => $schedule_target->internal_url('schedule-default-calendar')));
        }
      }
      if ( !$attendee_calendar->Exists() ) {
        dbg_error_log('ERROR','Default calendar at "%s" does not exist for user "%s"',
                      $attendee_calendar->dav_name(), $schedule_target->username());
        $response = '5.2';  // No scheduling support for user
      }
      else {
        $attendee_inbox = new WritableCollection(array('path' => $schedule_target->internal_url('schedule-inbox')));
        if ( ! $attendee_inbox->HavePrivilegeTo('schedule-deliver-invite') ) {
          $response = '3.8';  // No authority to deliver invitations to user.
        }
        else if ( $attendee_inbox->WriteCalendarMember($schedule_request, $attendee_is_new) !== false ) {
          $response = '1.2';  // Scheduling invitation delivered successfully
          if ( $attendee_calendar->WriteCalendarMember($orig_resource, $attendee_is_new) === false ) {
                dbg_error_log('ERROR','Could not write %s calendar member to %s', ($attendee_is_new?'new':'updated'),
                        $attendee_calendar->dav_name(), $attendee_calendar->dav_name(), $schedule_target->username());
                trace_bug('Failed to write scheduling resource.');
          }
        }
      }
    }
    else {
      $remote = new iSchedule ();
      $answer = $remote->sendRequest ( $email, 'VEVENT/REQUEST', $schedule_request->Render() );
      if ( $answer === false ) {
        $response = '3.7'; // Invalid Calendar User: iSchedule request failed or DKIM not configured
      }
      else {
        foreach ( $answer as $a ) // should only be one element in array
        {
          if ( $a === false ) {
            $response = '3.7'; // Invalid Calendar User: weird reply from remote server
          }
          elseif ( substr( $a, 0, 1 ) >= 1 ) {
            $response = $a; // NOTE: this may need to be limited to the reponse code
          }
          else {
            $response = '2.0'; // Success
          }
        }
      }
    }
    dbg_error_log( 'PUT', 'Status for attendee <%s> set to "%s"', $attendee->Value(), $response );
    $attendee->SetParameterValue( 'SCHEDULE-STATUS', $response );
    $scheduling_actions = true;
  }

  if ( !$create ) {
    foreach( $removed_attendees AS $attendee ) {
      $schedule_target = new Principal('email',$email);
      if ( $schedule_target->Exists() ) {
        $attendee_calendar = new WritableCollection(array('path' => $schedule_target->internal_url('schedule-default-calendar')));
      }
    }
  }
  return $scheduling_actions;
}


/**
* This function will import a whole collection
* @param string $ics_content the ics file to import
* @param int $user_no the user which will receive this ics file
* @param string $path the $path where it will be stored such as /user_foo/home/
* @param boolean $caldav_context Whether we are responding via CalDAV or interactively
*
* The work is either done by import_addressbook_collection() or import_calendar_collection()
*/
function import_collection( $import_content, $user_no, $path, $caldav_context, $appending = false ) {
  global $c;

  if ( ! ini_get('open_basedir') && (isset($c->dbg['ALL']) || isset($c->dbg['put'])) ) {
    $fh = fopen('/var/log/davical/PUT-2.debug','w');
    if ( $fh ) {
      fwrite($fh,$import_content);
      fclose($fh);
    }
  }

  if ( preg_match( '{^begin:(vcard|vcalendar)}i', $import_content, $matches) ) {
    if ( strtoupper($matches[1]) == 'VCARD' )
      import_addressbook_collection( $import_content, $user_no, $path, $caldav_context, $appending );
    elseif ( strtoupper($matches[1]) == 'VCALENDAR' )
      import_calendar_collection( $import_content, $user_no, $path, $caldav_context, $appending );

    // Uncache anything to do with the collection
    $cache = getCacheInstance();
    $cache_ns = 'collection-'.preg_replace( '{/[^/]*$}', '/', $path);
    $cache->delete( $cache_ns, null );
  }
  else {
    dbg_error_log('PUT', 'Can only import files which are VCARD or VCALENDAR');
  }
}


/**
* This function will import a whole addressbook
* @param string $vcard_content the vcf file to import
* @param int $user_no the user wich will receive this vcf file
* @param string $path the $path where it will be stored such as /user_foo/addresses/
* @param boolean $caldav_context Whether we are responding via CalDAV or interactively
*/
function import_addressbook_collection( $vcard_content, $user_no, $path, $caldav_context, $appending = false ) {
  global $c, $session;
  // We hack this into an enclosing component because vComponent only expects a single root component
  $addressbook = new vComponent("BEGIN:ADDRESSES\r\n".$vcard_content."\r\nEND:ADDRESSES\r\n");

  require_once('vcard.php');

  $sql = 'SELECT * FROM collection WHERE dav_name = :dav_name';
  $qry = new AwlQuery( $sql, array( ':dav_name' => $path) );
  if ( ! $qry->Exec('PUT',__LINE__,__FILE__) ) rollback_on_error( $caldav_context, $user_no, $path, 'Database error in: '.$sql );
  if ( ! $qry->rows() == 1 ) {
    dbg_error_log( 'ERROR', ' PUT: Collection does not exist at "%s" for user %d', $path, $user_no );
    rollback_on_error( $caldav_context, $user_no, $path, sprintf('Error: Collection does not exist at "%s" for user %d', $path, $user_no ));
  }
  $collection = $qry->Fetch();
  $collection_id = $collection->collection_id;

  // Fetch the current collection data
  $qry->QDo('SELECT dav_name, caldav_data FROM caldav_data WHERE collection_id=:collection_id', array(
          ':collection_id' => $collection_id
      ));
  $current_data = array();
  while( $row = $qry->Fetch() )
    $current_data[$row->dav_name] = $row->caldav_data;

  if ( !(isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import) ) $qry->Begin();
  $base_params = array(
       ':collection_id' => $collection_id,
       ':session_user' => $session->user_no,
       ':caldav_type' => 'VCARD'
  );

  $dav_data_insert = <<<EOSQL
INSERT INTO caldav_data ( user_no, dav_name, dav_etag, caldav_data, caldav_type, logged_user, created, modified, collection_id )
    VALUES( :user_no, :dav_name, :etag, :dav_data, :caldav_type, :session_user, :created, :modified, :collection_id )
EOSQL;

  $dav_data_update = <<<EOSQL
UPDATE caldav_data SET user_no=:user_no, caldav_data=:dav_data, dav_etag=:etag, caldav_type=:caldav_type, logged_user=:session_user,
  modified=current_timestamp WHERE collection_id=:collection_id AND dav_name=:dav_name
EOSQL;


  $resources = $addressbook->GetComponents();
  if ( count($resources) > 0 )
    $qry->QDo('SELECT new_sync_token(0,'.$collection_id.')');

  foreach( $resources AS $k => $resource ) {
    if ( isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import ) $qry->Begin();

    $vcard = new vCard( $resource->Render() );

    $uid = $vcard->GetPValue('UID');
    if ( empty($uid) ) {
      $uid = uuid();
      $vcard->AddProperty('UID',$uid);
    }

    $last_modified = $vcard->GetPValue('REV');
    if ( empty($last_modified) ) {
      $last_modified = gmdate( 'Ymd\THis\Z' );
      $vcard->AddProperty('REV',$last_modified);
    }

    $created = $vcard->GetPValue('X-CREATED');
    if ( empty($last_modified) ) {
      $created = gmdate( 'Ymd\THis\Z' );
      $vcard->AddProperty('X-CREATED',$created);
    }

    $rendered_card = $vcard->Render();

    // We don't allow any of &?\/@%+: in the UID to appear in the path, but anything else is fair game.
    $dav_name = sprintf( '%s%s.vcf', $path, preg_replace('{[&?\\/@%+:]}','',$uid) );

    $dav_data_params = $base_params;
    $dav_data_params[':user_no'] = $user_no;
    $dav_data_params[':dav_name'] = $dav_name;
    $dav_data_params[':etag'] = md5($rendered_card);
    $dav_data_params[':dav_data'] = $rendered_card;
    $dav_data_params[':modified'] = $last_modified;
    $dav_data_params[':created'] = $created;

    // Do we actually need to do anything?
    $inserting = true;
    if ( isset($current_data[$dav_name]) ) {
      if ( $rendered_card == $current_data[$dav_name] ) {
        unset($current_data[$dav_name]);
        continue;
      }
      $sync_change = 200;
      unset($current_data[$dav_name]);
      $inserting = false;
    }
    else
      $sync_change = 201;

    if ( isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import ) $qry->Begin();

    // Write to the caldav_data table
    if ( !$qry->QDo( ($inserting ? $dav_data_insert : $dav_data_update), $dav_data_params) )
      rollback_on_error( $caldav_context, $user_no, $path, 'Database error on:'. ($inserting ? $dav_data_insert : $dav_data_update));

    // Get the dav_id for this row
    $qry->QDo('SELECT dav_id FROM caldav_data WHERE dav_name = :dav_name ', array(':dav_name' => $dav_name));
    if ( $qry->rows() == 1 && $row = $qry->Fetch() ) {
      $dav_id = $row->dav_id;
    }

    $vcard->Write( $row->dav_id, !$inserting );

    $qry->QDo("SELECT write_sync_change( $collection_id, $sync_change, :dav_name)", array(':dav_name' => $dav_name ) );

    if ( isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import ) $qry->Commit();
  }

  if ( !$appending && count($current_data) > 0 ) {
    $params = array( ':collection_id' => $collection_id );
    if ( isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import ) $qry->Begin();
    foreach( $current_data AS $dav_name => $data ) {
      $params[':dav_name'] = $dav_name;
      $qry->QDo('DELETE FROM caldav_data WHERE collection_id = :collection_id AND dav_name = :dav_name', $params);
      $qry->QDo('SELECT write_sync_change(:collection_id, 404, :dav_name)', $params);
    }
    if ( isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import ) $qry->Commit();
  }

  if ( !(isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import) ) {
    if ( ! $qry->Commit() ) rollback_on_error( $caldav_context, $user_no, $path, 'Database error on COMMIT');
  }

}


/**
* This function will import a whole calendar
* @param string $ics_content the ics file to import
* @param int $user_no the user wich will receive this ics file
* @param string $path the $path where it will be stored such as /user_foo/home/
* @param boolean $caldav_context Whether we are responding via CalDAV or interactively
*
* Any VEVENTs with the same UID will be concatenated together
*/
function import_calendar_collection( $ics_content, $user_no, $path, $caldav_context, $appending = false ) {
  global $c, $session, $tz_regex;
  $calendar = new vComponent($ics_content);
  $timezones = $calendar->GetComponents('VTIMEZONE',true);
  $components = $calendar->GetComponents('VTIMEZONE',false);

  // Add a parameter to calendars on import so it will only load events 'after' @author karora
  // date, or an RFC5545 duration format offset from the current date.
  $after = null;
  if ( isset($_GET['after']) ) {
    $after = $_GET['after'];
    if ( strtoupper(substr($after, 0, 1)) == 'P' || strtoupper(substr($after, 0, 1)) == '-P' ) {
      $duration = new Rfc5545Duration($after);
      $duration = $duration->asSeconds();
      $after = time() - (abs($duration));
    }
    else {
      $after = new RepeatRuleDateTime($after);
      $after = $after->epoch();
    }
  }

  $displayname = $calendar->GetPValue('X-WR-CALNAME');
  if ( !$appending && isset($displayname) ) {
    $sql = 'UPDATE collection SET dav_displayname = :displayname WHERE dav_name = :dav_name';
    $qry = new AwlQuery( $sql, array( ':displayname' => $displayname, ':dav_name' => $path) );
    if ( ! $qry->Exec('PUT',__LINE__,__FILE__) ) rollback_on_error( $caldav_context, $user_no, $path, 'Database error on: '.$sql );
  }


  $tz_ids    = array();
  foreach( $timezones AS $k => $tz ) {
    $tz_ids[$tz->GetPValue('TZID')] = $k;
  }

  /** Build an array of resources.  Each resource is an array of vComponent */
  $resources = array();
  foreach( $components AS $k => $comp ) {
    $uid = $comp->GetPValue('UID');
    if ( $uid == null || $uid == '' ) {
      $uid = uuid();
      $comp->AddProperty('UID',$uid);
      dbg_error_log( 'LOG WARN', ' PUT: New collection resource does not have a UID - we assign one!' );
    }
    if ( !isset($resources[$uid]) ) $resources[$uid] = array();
    $resources[$uid][] = $comp;

    /** Ensure we have the timezone component for this in our array as well */
    $tzid = GetTZID($comp);
    if ( !empty($tzid) && !isset($resources[$uid][$tzid]) && isset($tz_ids[$tzid]) ) {
      $resources[$uid][$tzid] = $timezones[$tz_ids[$tzid]];
    }
  }


  $sql = 'SELECT * FROM collection WHERE dav_name = :dav_name';
  $qry = new AwlQuery( $sql, array( ':dav_name' => $path) );
  if ( ! $qry->Exec('PUT',__LINE__,__FILE__) ) rollback_on_error( $caldav_context, $user_no, $path, 'Database error on: '.$sql );
  if ( ! $qry->rows() == 1 ) {
    dbg_error_log( 'ERROR', ' PUT: Collection does not exist at "%s" for user %d', $path, $user_no );
    rollback_on_error( $caldav_context, $user_no, $path, sprintf( 'Error: Collection does not exist at "%s" for user %d', $path, $user_no ));
  }
  $collection = $qry->Fetch();
  $collection_id = $collection->collection_id;

  // Fetch the current collection data
  $qry->QDo('SELECT dav_name, caldav_data FROM caldav_data WHERE collection_id=:collection_id', array(
          ':collection_id' => $collection_id
      ));
  $current_data = array();
  while( $row = $qry->Fetch() )
    $current_data[$row->dav_name] = $row->caldav_data;

  if ( !(isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import) ) $qry->Begin();
  $base_params = array( ':collection_id' => $collection_id );

  $dav_data_insert = <<<EOSQL
INSERT INTO caldav_data ( user_no, dav_name, dav_etag, caldav_data, caldav_type, logged_user, created, modified, collection_id )
    VALUES( :user_no, :dav_name, :etag, :dav_data, :caldav_type, :session_user, current_timestamp, current_timestamp, :collection_id )
EOSQL;

  $dav_data_update = <<<EOSQL
UPDATE caldav_data SET user_no=:user_no, caldav_data=:dav_data, dav_etag=:etag, caldav_type=:caldav_type, logged_user=:session_user,
  modified=current_timestamp WHERE collection_id=:collection_id AND dav_name=:dav_name
EOSQL;

  $calitem_insert = <<<EOSQL
INSERT INTO calendar_item (user_no, dav_name, dav_id, dav_etag, uid, dtstamp, dtstart, dtend, summary, location, class, transp,
                    description, rrule, tz_id, last_modified, url, priority, created, due, percent_complete, status, collection_id )
    VALUES ( :user_no, :dav_name, currval('dav_id_seq'), :etag, :uid, :dtstamp, :dtstart, ##dtend##, :summary, :location, :class, :transp,
                :description, :rrule, :tzid, :modified, :url, :priority, :created, :due, :percent_complete, :status, :collection_id)
EOSQL;

  $calitem_update = <<<EOSQL
UPDATE calendar_item SET user_no=:user_no, dav_etag=:etag, uid=:uid, dtstamp=:dtstamp,
                dtstart=:dtstart, dtend=##dtend##, summary=:summary, location=:location,
                class=:class, transp=:transp, description=:description, rrule=:rrule,
                tz_id=:tzid, last_modified=:modified, url=:url, priority=:priority,
                due=:due, percent_complete=:percent_complete, status=:status
       WHERE collection_id=:collection_id AND dav_name=:dav_name
EOSQL;

  $last_olson = '';
  if ( count($resources) > 0 )
    $qry->QDo('SELECT new_sync_token(0,'.$collection_id.')');

  foreach( $resources AS $uid => $resource ) {

    /** Construct the VCALENDAR data */
    $vcal = new vCalendar();
    $vcal->SetComponents($resource);
    $icalendar = $vcal->Render();
    $dav_name = sprintf( '%s%s.ics', $path, preg_replace('{[&?\\/@%+:]}','',$uid) );

    if ( isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import ) $qry->Begin();

    /** As ever, we mostly deal with the first resource component */
    $first = $resource[0];

    $dav_data_params = $base_params;
    $dav_data_params[':user_no'] = $user_no;
    // We don't allow any of &?\/@%+: in the UID to appear in the path, but anything else is fair game.
    $dav_data_params[':dav_name'] = $dav_name;
    $dav_data_params[':etag'] = md5($icalendar);
    $calitem_params = $dav_data_params;
    $dav_data_params[':dav_data'] = $icalendar;
    $dav_data_params[':caldav_type'] = $first->GetType();
    $dav_data_params[':session_user'] = $session->user_no;

    $dtstart = $first->GetPValue('DTSTART');
    $calitem_params[':dtstart'] = $dtstart;
    if ( (!isset($dtstart) || $dtstart == '') && $first->GetPValue('DUE') != '' ) {
      $dtstart = $first->GetPValue('DUE');
      if ( isset($after) ) $dtstart_date = new RepeatRuleDateTime($first->GetProperty('DUE'));
    }
    else if ( isset($after) ) {
      $dtstart_date = new RepeatRuleDateTime($first->GetProperty('DTSTART'));
    }

    $calitem_params[':rrule'] = $first->GetPValue('RRULE');

    // Skip it if it's after our start date for this import.
    if ( isset($after) && empty($calitem_params[':rrule']) && $dtstart_date->epoch() < $after ) continue;

    // Do we actually need to do anything?
    $inserting = true;
    if ( isset($current_data[$dav_name]) ) {
      if ( $icalendar == $current_data[$dav_name] ) {
        if ( $after == null ) {
          unset($current_data[$dav_name]);
          continue;
        }
      }
      $sync_change = 200;
      unset($current_data[$dav_name]);
      $inserting = false;
    }
    else
      $sync_change = 201;

    // Write to the caldav_data table
    if ( !$qry->QDo( ($inserting ? $dav_data_insert : $dav_data_update), $dav_data_params) )
      rollback_on_error( $caldav_context, $user_no, $path, 'Database error on:'. ($inserting ? $dav_data_insert : $dav_data_update));

    // Get the dav_id for this row
    $qry->QDo('SELECT dav_id FROM caldav_data WHERE dav_name = :dav_name ', array(':dav_name' => $dav_data_params[':dav_name']));
    if ( $qry->rows() == 1 && $row = $qry->Fetch() ) {
      $dav_id = $row->dav_id;
    }

    $dtend = $first->GetPValue('DTEND');
    if ( isset($dtend) && $dtend != '' ) {
      dbg_error_log( 'PUT', ' DTEND: "%s", DTSTART: "%s", DURATION: "%s"', $dtend, $dtstart, $first->GetPValue('DURATION') );
      $calitem_params[':dtend'] = $dtend;
      $dtend = ':dtend';
    }
    else {
      $dtend = 'NULL';
      if ( $first->GetPValue('DURATION') != '' AND $dtstart != '' ) {
        $duration = trim(preg_replace( '#[PT]#', ' ', $first->GetPValue('DURATION') ));
        if ( $duration == '' ) $duration = '0 seconds';
        $dtend = '(:dtstart::timestamp with time zone + :duration::interval)';
        $calitem_params[':duration'] = $duration;
      }
      elseif ( $first->GetType() == 'VEVENT' ) {
        /**
        * From RFC2445 4.6.1:
        * For cases where a "VEVENT" calendar component specifies a "DTSTART"
        * property with a DATE data type but no "DTEND" property, the events
        * non-inclusive end is the end of the calendar date specified by the
        * "DTSTART" property. For cases where a "VEVENT" calendar component specifies
        * a "DTSTART" property with a DATE-TIME data type but no "DTEND" property,
        * the event ends on the same calendar date and time of day specified by the
        * "DTSTART" property.
        *
        * So we're looking for 'VALUE=DATE', to identify the duration, effectively.
        *
        */
        $dtstart_prop = $first->GetProperty('DTSTART');
        if ( empty($dtstart_prop) ) {
          dbg_error_log('PUT','Invalid VEVENT without DTSTART, UID="%s" in collection %d', $uid, $collection_id);
          continue;
        }
        $value_type = $dtstart_prop->GetParameterValue('VALUE');
        dbg_error_log('PUT','DTSTART without DTEND. DTSTART value type is %s', $value_type );
        if ( isset($value_type) && $value_type == 'DATE' )
          $dtend = '(:dtstart::timestamp with time zone::date + \'1 day\'::interval)';
        else
          $dtend = ':dtstart';
      }
    }

    $last_modified = $first->GetPValue('LAST-MODIFIED');
    if ( !isset($last_modified) || $last_modified == '' ) $last_modified = gmdate( 'Ymd\THis\Z' );
    $calitem_params[':modified'] = $last_modified;

    $dtstamp = $first->GetPValue('DTSTAMP');
    if ( empty($dtstamp) ) $dtstamp = $last_modified;
    $calitem_params[':dtstamp'] = $dtstamp;

    /** RFC2445, 4.8.1.3: Default is PUBLIC, or also if overridden by the collection settings */
    $class = ($collection->public_events_only == 't' ? 'PUBLIC' : $first->GetPValue('CLASS') );
    if ( !isset($class) || $class == '' ) $class = 'PUBLIC';
    $calitem_params[':class'] = $class;


    /** Calculate what timezone to set, first, if possible */
    $tzid = GetTZID($first);
    if ( !empty($tzid) && !empty($resource[$tzid]) ) {
      $tz = $resource[$tzid];
      $olson = $vcal->GetOlsonName($tz);
      dbg_error_log( 'PUT', ' Using TZID[%s] and location of [%s]', $tzid, (isset($olson) ? $olson : '') );
      if ( !empty($olson) && ($olson != $last_olson) && preg_match( $tz_regex, $olson ) ) {
        dbg_error_log( 'PUT', ' Setting timezone to %s', $olson );
        $qry->QDo('SET TIMEZONE TO \''.$olson."'" );
        $last_olson = $olson;
      }
      $params = array( ':tzid' => $tzid);
      $qry = new AwlQuery('SELECT 1 FROM timezones WHERE tzid = :tzid', $params );
      if ( $qry->Exec('PUT',__LINE__,__FILE__) && $qry->rows() == 0 ) {
        $params[':olson_name'] = $olson;
        $params[':vtimezone'] = (isset($tz) ? $tz->Render() : null );
        $params[':last_modified'] = (isset($tz) ? $tz->GetPValue('LAST-MODIFIED') : null );
        if ( empty($params[':last_modified']) ) {
          $params[':last_modified'] = gmdate('Ymd\THis\Z');
        }
        $qry->QDo('INSERT INTO timezones (tzid, olson_name, active, vtimezone, last_modified) VALUES(:tzid,:olson_name,false,:vtimezone,:last_modified)', $params );
      }
    }
    else {
      $tz = $olson = $tzid = null;
    }

    $sql = str_replace( '##dtend##', $dtend, ($inserting ? $calitem_insert : $calitem_update) );
    $calitem_params[':tzid'] = $tzid;
    $calitem_params[':uid'] = $first->GetPValue('UID');
    $calitem_params[':summary'] = $first->GetPValue('SUMMARY');
    $calitem_params[':location'] = $first->GetPValue('LOCATION');
    $calitem_params[':transp'] = $first->GetPValue('TRANSP');
    $calitem_params[':description'] = $first->GetPValue('DESCRIPTION');
    $calitem_params[':url'] = $first->GetPValue('URL');
    $calitem_params[':priority'] = $first->GetPValue('PRIORITY');
    $calitem_params[':due'] = $first->GetPValue('DUE');
    $calitem_params[':percent_complete'] = $first->GetPValue('PERCENT-COMPLETE');
    $calitem_params[':status'] = $first->GetPValue('STATUS');

    if ( $inserting ) {
      $created = $first->GetPValue('CREATED');
      if ( $created == '00001231T000000Z' ) $created = '20001231T000000Z';
      $calitem_params[':created'] = $created;
    }

    // Write the calendar_item row for this entry
    if ( !$qry->QDo($sql,$calitem_params) ) rollback_on_error( $caldav_context, $user_no, $path);

    write_alarms($dav_id, $first);
    write_attendees($dav_id, $vcal);

    $qry->QDo("SELECT write_sync_change( $collection_id, $sync_change, :dav_name)", array(':dav_name' => $dav_name ) );

    do_scheduling_requests( $vcal, true );
    if ( isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import ) $qry->Commit();
  }

  if ( !$appending && count($current_data) > 0 ) {
    $params = array( ':collection_id' => $collection_id );
    if ( isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import ) $qry->Begin();
    foreach( $current_data AS $dav_name => $data ) {
      $params[':dav_name'] = $dav_name;
      $qry->QDo('DELETE FROM caldav_data WHERE collection_id = :collection_id AND dav_name = :dav_name', $params);
      $qry->QDo('SELECT write_sync_change(:collection_id, 404, :dav_name)', $params);
    }
    if ( isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import ) $qry->Commit();
  }

  if ( !(isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import) ) {
    if ( ! $qry->Commit() ) rollback_on_error( $caldav_context, $user_no, $path);
  }
}


/**
* Given a dav_id and an original vCalendar, pull out each of the VALARMs
* and write the values into the calendar_alarm table.
*
* @param int $dav_id The dav_id of the caldav_data we're processing
* @param vComponent The VEVENT or VTODO containing the VALARM
* @return null
*/
function write_alarms( $dav_id, vComponent $ical ) {
  $qry = new AwlQuery('DELETE FROM calendar_alarm WHERE dav_id = '.$dav_id );
  $qry->Exec('PUT',__LINE__,__FILE__);

  $alarms = $ical->GetComponents('VALARM');
  if ( count($alarms) < 1 ) return;

  $qry->SetSql('INSERT INTO calendar_alarm ( dav_id, action, trigger, summary, description, component, next_trigger )
          VALUES( '.$dav_id.', :action, :trigger, :summary, :description, :component,
                                      :related::timestamp with time zone + :related_trigger::interval )' );
  $qry->Prepare();
  foreach( $alarms AS $v ) {
    $trigger = array_merge($v->GetProperties('TRIGGER'));
    if ( $trigger == null ) continue; // Bogus data.
    $trigger = $trigger[0];
    $related = null;
    $related_trigger = '0M';
    $trigger_type = $trigger->GetParameterValue('VALUE');
    if ( !isset($trigger_type) || $trigger_type == 'DURATION' ) {
      switch ( $trigger->GetParameterValue('RELATED') ) {
        case 'DTEND':  $related = $ical->GetProperty('DTEND'); break;
        case 'DUE':    $related = $ical->GetProperty('DUE');   break;
        default:       $related = $ical->GetProperty('DTSTART');
      }
      $duration = $trigger->Value();
      if ( !preg_match('{^-?P(:?\d+W)?(:?\d+D)?(:?T(:?\d+H)?(:?\d+M)?(:?\d+S)?)?$}', $duration ) ) continue;
      $minus = (substr($duration,0,1) == '-');
      $related_trigger = trim(preg_replace( '#[PT-]#', ' ', $duration ));
      if ( $minus ) {
        $related_trigger = preg_replace( '{(\d+[WDHMS])}', '-$1 ', $related_trigger );
      }
      else {
        $related_trigger = preg_replace( '{(\d+[WDHMS])}', '$1 ', $related_trigger );
      }
    }
    else if ( $trigger_type == 'DATE-TIME' ) {
      $related = $trigger;
    }
    else {
      if ( false === strtotime($trigger->Value()) ) continue; // Invalid date.
      $related = $trigger;
    }
    $related_date = new RepeatRuleDateTime($related);
    $qry->Bind(':action', $v->GetPValue('ACTION'));
    $qry->Bind(':trigger', $trigger->Render());
    $qry->Bind(':summary', $v->GetPValue('SUMMARY'));
    $qry->Bind(':description', $v->GetPValue('DESCRIPTION'));
    $qry->Bind(':component', $v->Render());
    $qry->Bind(':related', $related_date->UTC() );
    $qry->Bind(':related_trigger', $related_trigger );
    $qry->Exec('PUT',__LINE__,__FILE__);
  }
}


/**
* Parse out the attendee property and write a row to the
* calendar_attendee table for each one.
* @param int $dav_id The dav_id of the caldav_data we're processing
* @param vComponent The VEVENT or VTODO containing the ATTENDEEs
* @return null
*/
function write_attendees( $dav_id, vCalendar $ical ) {
  $qry = new AwlQuery('DELETE FROM calendar_attendee WHERE dav_id = '.$dav_id );
  $qry->Exec('PUT',__LINE__,__FILE__);

  $attendees = $ical->GetAttendees();
  if ( count($attendees) < 1 ) return;

  $qry->SetSql('INSERT INTO calendar_attendee ( dav_id, status, partstat, cn, attendee, role, rsvp, property )
          VALUES( '.$dav_id.', :status, :partstat, :cn, :attendee, :role, :rsvp, :property )' );
  $qry->Prepare();
  $processed = array();
  foreach( $attendees AS $v ) {
    $attendee = $v->Value();
    if ( isset($processed[$attendee]) ) {
      dbg_error_log( 'LOG', 'Duplicate attendee "%s" in resource "%d"', $attendee, $dav_id );
      dbg_error_log( 'LOG', 'Original:  "%s"', $processed[$attendee] );
      dbg_error_log( 'LOG', 'Duplicate: "%s"', $v->Render() );
      continue; /** @todo work out why we get duplicate ATTENDEE on one VEVENT */
    }
    $qry->Bind(':attendee', $attendee );
    $qry->Bind(':status',   $v->GetParameterValue('STATUS') );
    $qry->Bind(':partstat', $v->GetParameterValue('PARTSTAT') );
    $qry->Bind(':cn',       $v->GetParameterValue('CN') );
    $qry->Bind(':role',     $v->GetParameterValue('ROLE') );
    $qry->Bind(':rsvp',     $v->GetParameterValue('RSVP') );
    $qry->Bind(':property', $v->Render() );
    $qry->Exec('PUT',__LINE__,__FILE__);
    $processed[$attendee] = $v->Render();
  }
}


/**
* Actually write the resource to the database.  All checking of whether this is reasonable
* should be done before this is called.
*
* @param DAVResource $resource The resource being written
* @param string $caldav_data The actual data to be written
* @param DAVResource $collection The collection containing the resource being written
* @param int $author The user_no who wants to put this resource on the server
* @param string $etag An etag unique for this event
* @param string $put_action_type INSERT or UPDATE depending on what we are to do
* @param boolean $caldav_context True, if we are responding via CalDAV, false for other ways of calling this
* @param string Either 'INSERT' or 'UPDATE': the type of action we are doing
* @param boolean $log_action Whether to log the fact that we are writing this into an action log (if configured)
* @param string $weak_etag An etag that is NOT modified on ATTENDEE changes for this event
*
* @return boolean True for success, false for failure.
*/
function write_resource( DAVResource $resource, $caldav_data, DAVResource $collection, $author, &$etag, $put_action_type, $caldav_context, $log_action=true, $weak_etag=null ) {
  global $tz_regex, $session;

  $path = $resource->bound_from();
  $user_no = $collection->user_no();
  $vcal = new vCalendar( $caldav_data );
  $resources = $vcal->GetComponents('VTIMEZONE',false); // Not matching VTIMEZONE
  if ( !isset($resources[0]) ) {
    $resource_type = 'Unknown';
    /** @todo Handle writing non-calendar resources, like address book entries or random file data */
    rollback_on_error( $caldav_context, $user_no, $path, translate('No calendar content'), 412 );
    return false;
  }
  else {
    $first = $resources[0];
    if ( !($first  instanceof vComponent) ) {
      print $vcal->Render();
      fatal('This is not a vComponent!');
    }
    $resource_type = $first->GetType();
  }

  $collection_id = $collection->collection_id();

  $qry = new AwlQuery();
  $qry->Begin();

  $dav_params = array(
      ':etag' => $etag,
      ':dav_data' => $caldav_data,
      ':caldav_type' => $resource_type,
      ':session_user' => $author,
      ':weak_etag' => $weak_etag
  );

  $calitem_params = array(
      ':etag' => $etag
  );

  if ( $put_action_type == 'INSERT' ) {
    $qry->QDo('SELECT nextval(\'dav_id_seq\') AS dav_id, null AS caldav_data');
  }
  else {
    $qry->QDo('SELECT dav_id, caldav_data FROM caldav_data WHERE dav_name = :dav_name ', array(':dav_name' => $path));
  }
  if ( $qry->rows() != 1 || !($row = $qry->Fetch()) ) {
    // No dav_id?  => We're toast!
    trace_bug( 'No dav_id for "%s" on %s!!!', $path, ($put_action_type == 'INSERT' ? 'create': 'update'));
    rollback_on_error( $caldav_context, $user_no, $path);
    return false;
  }
  $dav_id = $row->dav_id;
  $old_dav_data = $row->caldav_data;
  $dav_params[':dav_id'] = $dav_id;
  $calitem_params[':dav_id'] = $dav_id;

  $due = null;
  if ( $first->GetType() == 'VTODO' ) $due = $first->GetPValue('DUE');
  $calitem_params[':due'] = $due;
  $dtstart = $first->GetPValue('DTSTART');
  if ( empty($dtstart) ) $dtstart = $due;
  if (preg_match("/^1[0-8][0-9][0-9][01][0-9][0-3][0-9]$/", $dtstart))
     $dtstart = $dtstart . "T000000Z";
  $calitem_params[':dtstart'] = $dtstart;

  $dtend = $first->GetPValue('DTEND');
  if ( isset($dtend) && $dtend != '' ) {
    dbg_error_log( 'PUT', ' DTEND: "%s", DTSTART: "%s", DURATION: "%s"', $dtend, $dtstart, $first->GetPValue('DURATION') );
    if (preg_match("/^1[0-8][0-9][0-9][01][0-9][0-3][0-9]$/", $dtend))
       $dtend = $dtend . "T000000Z";
    $calitem_params[':dtend'] = $dtend;
    $dtend = ':dtend';
  }
  else {
    // In this case we'll construct the SQL directly as a calculation relative to :dtstart
    $dtend = 'NULL';
    if ( $first->GetPValue('DURATION') != '' AND $dtstart != '' ) {
      $duration = trim(preg_replace( '#[PT]#', ' ', $first->GetPValue('DURATION') ));
      if ( $duration == '' ) $duration = '0 seconds';
      $dtend = '(:dtstart::timestamp with time zone + :duration::interval)';
      $calitem_params[':duration'] = $duration;
    }
    elseif ( $first->GetType() == 'VEVENT' ) {
      /**
      * From RFC2445 4.6.1:
      * For cases where a "VEVENT" calendar component specifies a "DTSTART"
      * property with a DATE data type but no "DTEND" property, the events
      * non-inclusive end is the end of the calendar date specified by the
      * "DTSTART" property. For cases where a "VEVENT" calendar component specifies
      * a "DTSTART" property with a DATE-TIME data type but no "DTEND" property,
      * the event ends on the same calendar date and time of day specified by the
      * "DTSTART" property.
      *
      * So we're looking for 'VALUE=DATE', to identify the duration, effectively.
      *
      */
      $dtstart_prop = $first->GetProperty('DTSTART');
      $value_type = $dtstart_prop->GetParameterValue('VALUE');
      dbg_error_log('PUT','DTSTART without DTEND. DTSTART value type is %s', $value_type );
      if ( isset($value_type) && $value_type == 'DATE' )
        $dtend = '(:dtstart::timestamp with time zone::date + \'1 day\'::interval)';
      else
        $dtend = ':dtstart';
    }
  }

  $dtstamp = $first->GetPValue('DTSTAMP');
  if ( !isset($dtstamp) || $dtstamp == '' ) {
    // Strictly, we're dealing with an out of spec component here, but we'll try and survive
    $dtstamp = gmdate( 'Ymd\THis\Z' );
  }
  $calitem_params[':dtstamp'] = $dtstamp;

  $last_modified = $first->GetPValue('LAST-MODIFIED');
  if ( !isset($last_modified) || $last_modified == '' ) $last_modified = $dtstamp;
  $dav_params[':modified'] = $last_modified;
  $calitem_params[':modified'] = $last_modified;

  $created = $first->GetPValue('CREATED');
  if ( $created == '00001231T000000Z' ) $created = '20001231T000000Z';

  $class = $first->GetPValue('CLASS');
  /* Check and see if we should over ride the class. */
  /** @todo is there some way we can move this out of this function? Or at least get rid of the need for the SQL query here. */
  if ( public_events_only($user_no, $path) ) {
    $class = 'PUBLIC';
  }

  /*
   * It seems that some calendar clients don't set a class...
   * RFC2445, 4.8.1.3:
   * Default is PUBLIC
   */
  if ( !isset($class) || $class == '' ) {
    $class = 'PUBLIC';
  }
  $calitem_params[':class'] = $class;

  /** Calculate what timezone to set, first, if possible */
  $last_olson = 'Turkmenikikamukau';  // I really hope this location doesn't exist!
  $tzid = GetTZID($first);
  if ( !empty($tzid) ) {
    $timezones = $vcal->GetComponents('VTIMEZONE');
    foreach( $timezones AS $k => $tz ) {
      if ( $tz->GetPValue('TZID') != $tzid ) {
        /**
        * We'll skip any tz definitions that are for a TZID other than the DTSTART/DUE on the first VEVENT/VTODO
        */
        dbg_error_log( 'ERROR', ' Event uses TZID[%s], skipping included TZID[%s]!', $tz->GetPValue('TZID'), $tzid );
        continue;
      }
      $olson = olson_from_tzstring($tzid);
      if ( empty($olson) ) {
        $olson = $tz->GetPValue('X-LIC-LOCATION');
        if ( !empty($olson) ) {
          $olson = olson_from_tzstring($olson);
        }
      }
    }

    dbg_error_log( 'PUT', ' Using TZID[%s] and location of [%s]', $tzid, (isset($olson) ? $olson : '') );
    if ( !empty($olson) && ($olson != $last_olson) && preg_match( $tz_regex, $olson ) ) {
      dbg_error_log( 'PUT', ' Setting timezone to %s', $olson );
      if ( $olson != '' ) {
        $qry->QDo('SET TIMEZONE TO \''.$olson."'" );
      }
      $last_olson = $olson;
    }
    $params = array( ':tzid' => $tzid);
    $qry = new AwlQuery('SELECT 1 FROM timezones WHERE tzid = :tzid', $params );
    if ( $qry->Exec('PUT',__LINE__,__FILE__) && $qry->rows() == 0 ) {
      $params[':olson_name'] = $olson;
      $params[':vtimezone'] = (isset($tz) ? $tz->Render() : null );
      $qry->QDo('INSERT INTO timezones (tzid, olson_name, active, vtimezone) VALUES(:tzid,:olson_name,false,:vtimezone)', $params );
    }
    if ( !isset($olson) || $olson == '' ) $olson = $tzid;

  }

  $qry->QDo('SELECT new_sync_token(0,'.$collection_id.')');

  $calitem_params[':tzid'] = $tzid;
  $calitem_params[':uid'] = $first->GetPValue('UID');
  $calitem_params[':summary'] = $first->GetPValue('SUMMARY');
  $calitem_params[':location'] = $first->GetPValue('LOCATION');
  $calitem_params[':transp'] = $first->GetPValue('TRANSP');
  $calitem_params[':description'] = $first->GetPValue('DESCRIPTION');
  $calitem_params[':rrule'] = $first->GetPValue('RRULE');
  $calitem_params[':url'] = $first->GetPValue('URL');
  $calitem_params[':priority'] = $first->GetPValue('PRIORITY');
  $calitem_params[':percent_complete'] = $first->GetPValue('PERCENT-COMPLETE');
  $calitem_params[':status'] = $first->GetPValue('STATUS');

  // force re-render the object (to get the same representation for all attendiees)
  $vcal->Render(null, true);

  if ( !$collection->IsSchedulingCollection() ) {
    if ( do_scheduling_requests($vcal, ($put_action_type == 'INSERT'), $old_dav_data ) ) {
      $dav_params[':dav_data'] = $vcal->Render(null, true);
      $etag = null;
    }
  }

  if ( !isset($dav_params[':modified']) ) $dav_params[':modified'] = 'now';
  if ( $put_action_type == 'INSERT' ) {
    $sql = 'INSERT INTO caldav_data ( dav_id, user_no, dav_name, dav_etag, caldav_data, caldav_type, logged_user, created, modified, collection_id, weak_etag )
            VALUES( :dav_id, :user_no, :dav_name, :etag, :dav_data, :caldav_type, :session_user, :created, :modified, :collection_id, :weak_etag )';
    $dav_params[':collection_id'] = $collection_id;
    $dav_params[':user_no'] = $user_no;
    $dav_params[':dav_name'] = $path;
    $dav_params[':created'] = (isset($created) && $created != '' ? $created : $dtstamp);
  }
  else {
    $sql = 'UPDATE caldav_data SET caldav_data=:dav_data, dav_etag=:etag, caldav_type=:caldav_type, logged_user=:session_user,
            modified=:modified, weak_etag=:weak_etag WHERE dav_id=:dav_id';
  }
  $qry = new AwlQuery($sql,$dav_params);
  if ( !$qry->Exec('PUT',__LINE__,__FILE__) ) {
    fatal('Insert into calendar_item failed...');
    rollback_on_error( $caldav_context, $user_no, $path);
    return false;
  }


  if ( $put_action_type == 'INSERT' ) {
    $sql = <<<EOSQL
INSERT INTO calendar_item (user_no, dav_name, dav_id, dav_etag, uid, dtstamp,
                dtstart, dtend, summary, location, class, transp,
                description, rrule, tz_id, last_modified, url, priority,
                created, due, percent_complete, status, collection_id )
   VALUES ( :user_no, :dav_name, :dav_id, :etag, :uid, :dtstamp,
                :dtstart, $dtend, :summary, :location, :class, :transp,
                :description, :rrule, :tzid, :modified, :url, :priority,
                :created, :due, :percent_complete, :status, :collection_id )
EOSQL;
    $sync_change = 201;
    $calitem_params[':collection_id'] = $collection_id;
    $calitem_params[':user_no'] = $user_no;
    $calitem_params[':dav_name'] = $path;
    $calitem_params[':created'] = $dav_params[':created'];
  }
  else {
    $sql = <<<EOSQL
UPDATE calendar_item SET dav_etag=:etag, uid=:uid, dtstamp=:dtstamp,
                dtstart=:dtstart, dtend=$dtend, summary=:summary, location=:location,
                class=:class, transp=:transp, description=:description, rrule=:rrule,
                tz_id=:tzid, last_modified=:modified, url=:url, priority=:priority,
                due=:due, percent_complete=:percent_complete, status=:status
       WHERE dav_id=:dav_id
EOSQL;
    $sync_change = 200;
  }

  write_alarms($dav_id, $first);
  write_attendees($dav_id, $vcal);

  if ( $log_action && function_exists('log_caldav_action') ) {
    log_caldav_action( $put_action_type, $first->GetPValue('UID'), $user_no, $collection_id, $path );
  }
  else if ( $log_action  ) {
    dbg_error_log( 'PUT', 'No log_caldav_action( %s, %s, %s, %s, %s) can be called.',
            $put_action_type, $first->GetPValue('UID'), $user_no, $collection_id, $path );
  }

  $qry = new AwlQuery( $sql, $calitem_params );
  if ( !$qry->Exec('PUT',__LINE__,__FILE__) ) {
    rollback_on_error( $caldav_context, $user_no, $path);
    return false;
  }
  $qry->QDo("SELECT write_sync_change( $collection_id, $sync_change, :dav_name)", array(':dav_name' => $path ) );
  $qry->Commit();

  if ( function_exists('post_commit_action') ) {
    post_commit_action( $put_action_type, $first->GetPValue('UID'), $user_no, $collection_id, $path );
  }

  // Uncache anything to do with the collection
  $cache = getCacheInstance();
  $cache_ns = 'collection-'.preg_replace( '{/[^/]*$}', '/', $path);
  $cache->delete( $cache_ns, null );

  dbg_error_log( 'PUT', 'User: %d, ETag: %s, Path: %s', $author, $etag, $path);

  return true;  // Success!
}



/**
* A slightly simpler version of write_resource which will make more sense for calling from
* an external program.  This makes assumptions that the collection and user do exist
* and bypasses all checks for whether it is reasonable to write this here.
* @param string $path The path to the resource being written
* @param string $caldav_data The actual resource to be written
* @param string $put_action_type INSERT or UPDATE depending on what we are to do
* @return boolean True for success, false for failure.
*/
function simple_write_resource( $path, $caldav_data, $put_action_type, $write_action_log = false ) {
  global $session;

  /**
  * We pull the user_no & collection_id out of the collection table, based on the resource path
  */
  $dav_resource = new DAVResource($path);
  $etag = md5($caldav_data);
  $collection_path = preg_replace( '#/[^/]*$#', '/', $path );
  $collection = new DAVResource($collection_path);
  if ( $collection->IsCollection() || $collection->IsSchedulingCollection() ) {
    return write_resource( $dav_resource, $caldav_data, $collection, $session->user_no, $etag, $put_action_type, false, $write_action_log );
  }
  return false;
}
