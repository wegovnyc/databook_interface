<?php
/**
* CalDAV Server - functions used by GET method handler
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst .Net Ltd, Morphoss Ltd <http://www.morphoss.com/>
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

require_once("iCalendar.php");
require_once("DAVResource.php");

function obfuscated_event( $icalendar ) {
  // The user is not admin / owner of this calendar looking at his calendar and can not admin the other cal,
  // or maybe they don't have *read* access but they got here, so they must at least have free/busy access
  // so we will present an obfuscated version of the event that just says "Busy" (translated :-)
  $confidential = new iCalComponent();
  $confidential->SetType($icalendar->GetType());
  $confidential->AddProperty( 'SUMMARY', translate('Busy') );
  $confidential->AddProperty( 'CLASS', 'CONFIDENTIAL' );
  $confidential->SetProperties( $icalendar->GetProperties('DTSTART'), 'DTSTART' );
  $confidential->SetProperties( $icalendar->GetProperties('RRULE'), 'RRULE' );
  $confidential->SetProperties( $icalendar->GetProperties('DURATION'), 'DURATION' );
  $confidential->SetProperties( $icalendar->GetProperties('DTEND'), 'DTEND' );
  $confidential->SetProperties( $icalendar->GetProperties('UID'), 'UID' );
  $confidential->SetProperties( $icalendar->GetProperties('CREATED'), 'CREATED' );

  return $confidential;
}

function export_iCalendar( DAVResource $dav_resource ) {
  global $session, $c, $request;
  if ( ! $dav_resource->IsCalendar() && !(isset($c->get_includes_subcollections) && $c->get_includes_subcollections) ) {
    /** RFC2616 says we must send an Allow header if we send a 405 */
    header("Allow: PROPFIND,PROPPATCH,OPTIONS,MKCOL,REPORT,DELETE");
    $request->DoResponse( 405, translate("GET requests on collections are only supported for calendars.") );
  }

  /**
  * The CalDAV specification does not define GET on a collection, but typically this is
  * used as a .ics download for the whole collection, which is what we do also.
  */
  if ( isset($c->get_includes_subcollections) && $c->get_includes_subcollections ) {
    $where = 'caldav_data.collection_id IN ';
    $where .= '(SELECT bound_source_id FROM dav_binding WHERE dav_binding.dav_name ~ :path_match ';
    $where .= 'UNION ';
    $where .= 'SELECT collection_id FROM collection WHERE collection.dav_name ~ :path_match) ';
    $params = array( ':path_match' => '^'.$dav_resource->dav_name() );
    $distinct = 'DISTINCT ON (calendar_item.uid) ';
  }
  else {
    $where = 'caldav_data.collection_id = :collection_id ';
    $params = array( ':collection_id' => $dav_resource->resource_id() );
    $distinct = '';
  }
  $sql = 'SELECT '.$distinct.' caldav_data, class, caldav_type, calendar_item.user_no, logged_user ';
  $sql .= 'FROM collection INNER JOIN caldav_data USING(collection_id) ';
  $sql .= 'INNER JOIN calendar_item USING ( dav_id ) WHERE '.$where;
  if ( isset($c->strict_result_ordering) && $c->strict_result_ordering ) $sql .= ' ORDER BY calendar_item.uid, calendar_item.dav_id';

  $qry = new AwlQuery( $sql, $params );
  if ( !$qry->Exec("GET",__LINE__,__FILE__) ) {
    $request->DoResponse( 500, translate("Database Error") );
  }

  /**
  * Here we are constructing a whole calendar response for this collection, including
  * the timezones that are referred to by the events we have selected.
  */
  $vcal = new iCalComponent();
  $vcal->VCalendar();
  $displayname = $dav_resource->GetProperty('displayname');
  if ( isset($displayname) ) {
    $vcal->AddProperty("X-WR-CALNAME", $displayname);
  }
  if ( !empty($c->auto_refresh_duration) ) {
    $vcal->AddProperty("X-APPLE-AUTO-REFRESH-INTERVAL", $c->auto_refresh_duration);
    $vcal->AddProperty("AUTO-REFRESH", $c->auto_refresh_duration);
    $vcal->AddProperty("X-PUBLISHED-TTL", $c->auto_refresh_duration);
  }

  $need_zones = array();
  $timezones = array();
  while( $event = $qry->Fetch() ) {
    $ical = new iCalComponent( $event->caldav_data );

    /** Save the timezone component(s) into a minimal set for inclusion later */
    $event_zones = $ical->GetComponents('VTIMEZONE',true);
    foreach( $event_zones AS $k => $tz ) {
      $tzid = $tz->GetPValue('TZID');
      if ( !isset($tzid) ) continue ;
      if ( $tzid != '' && !isset($timezones[$tzid]) ) {
        $timezones[$tzid] = $tz;
      }
    }

    /** Work out which ones are actually used here */
    $comps = $ical->GetComponents('VTIMEZONE',false);
    foreach( $comps AS $k => $comp ) {
      $tzid = $comp->GetPParamValue('DTSTART', 'TZID');      if ( isset($tzid) && !isset($need_zones[$tzid]) ) $need_zones[$tzid] = 1;
      $tzid = $comp->GetPParamValue('DUE',     'TZID');      if ( isset($tzid) && !isset($need_zones[$tzid]) ) $need_zones[$tzid] = 1;
      $tzid = $comp->GetPParamValue('DTEND',   'TZID');      if ( isset($tzid) && !isset($need_zones[$tzid]) ) $need_zones[$tzid] = 1;

      if ( $dav_resource->HavePrivilegeTo('all',false) || $session->user_no == $event->user_no || $session->user_no == $event->logged_user
            || ( isset($session->email) && $c->allow_get_email_visibility && $comp->IsAttendee($session->email) ) ) {
        /**
        * These people get to see all of the event, and they should always
        * get any alarms as well.
        */
        $vcal->AddComponent($comp);
        continue;
      }
      /** No visibility even of the existence of these events if they aren't admin/owner/attendee */
      if ( $event->class == 'PRIVATE' ) continue;

      if ( ! $dav_resource->HavePrivilegeTo('DAV::read') || $event->class == 'CONFIDENTIAL' ) {
       $vcal->AddComponent(obfuscated_event($comp));
      }
      elseif ( isset($c->hide_alarm) && $c->hide_alarm ) {
        // Otherwise we hide the alarms (if configured to)
        $comp->ClearComponents('VALARM');
        $vcal->AddComponent($comp);
      }
      else {
        $vcal->AddComponent($comp);
      }
    }
  }

  /** Put the timezones on there that we need */
  foreach( $need_zones AS $tzid => $v ) {
    if ( isset($timezones[$tzid]) ) $vcal->AddComponent($timezones[$tzid]);
  }

  return $vcal->Render();
}
