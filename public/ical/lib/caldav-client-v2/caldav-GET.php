<?php
/**
* CalDAV Server - handle GET method
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst .Net Ltd, Morphoss Ltd <http://www.morphoss.com/>
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/
dbg_error_log("get", "GET method handler");

require("caldav-GET-functions.php");

$dav_resource = new DAVResource($request->path);
$dav_resource->NeedPrivilege( array('urn:ietf:params:xml:ns:caldav:read-free-busy','DAV::read') );
if ( $dav_resource->IsExternal() ) {
  require_once("external-fetch.php");
  update_external ( $dav_resource );
}

if ( ! $dav_resource->Exists() ) {
  $request->DoResponse( 404, translate("Resource Not Found.") );
}


if ( $dav_resource->IsCollection() ) {
  $response = export_iCalendar($dav_resource);
  header( 'Etag: '.$dav_resource->unique_tag() );
  $request->DoResponse( 200, ($request->method == 'HEAD' ? '' : $response), 'text/calendar; charset="utf-8"' );
}


// Just a single event then

$resource = $dav_resource->resource();
$ic = new iCalComponent( $resource->caldav_data );

$resource->caldav_data = preg_replace( '{(?<!\r)\n}', "\r\n", $resource->caldav_data);

/** Default deny... */
$allowed = false;
if ( $dav_resource->HavePrivilegeTo('all', false) || $session->user_no == $resource->user_no || $session->user_no == $resource->logged_user
      || ( $c->allow_get_email_visibility && $ic->IsAttendee($session->email) ) ) {
  /**
  * These people get to see all of the event, and they should always
  * get any alarms as well.
  */
  $allowed = true;
}
else if ( $resource->class != 'PRIVATE' ) {
  $allowed = true; // but we may well obfuscate it below
  if ( ! $dav_resource->HavePrivilegeTo('DAV::read') || ( $resource->class == 'CONFIDENTIAL' && ! $request->HavePrivilegeTo('DAV::write-content') ) ) {
    $ical = new iCalComponent( $resource->caldav_data );
    $comps = $ical->GetComponents('VTIMEZONE',false);
    $confidential = obfuscated_event($comps[0]);
    $ical->SetComponents( array($confidential), $resource->caldav_type );
    $resource->caldav_data = $ical->Render();
  }
}
// else $resource->class == 'PRIVATE' and this person may not see it.

if ( ! $allowed ) {
  $request->DoResponse( 403, translate("Forbidden") );
}

header( 'Etag: "'.$resource->dav_etag.'"' );
header( 'Content-Length: '.strlen($resource->caldav_data) );

$contenttype = 'text/plain';
switch( $resource->caldav_type ) {
  case 'VJOURNAL':
  case 'VEVENT':
  case 'VTODO':
    $contenttype = 'text/calendar; component=' . strtolower($resource->caldav_type);
    break;

  case 'VCARD':
    $contenttype = 'text/vcard';
    break;
}

$request->DoResponse( 200, ($request->method == 'HEAD' ? '' : $resource->caldav_data), $contenttype.'; charset="utf-8"' );
