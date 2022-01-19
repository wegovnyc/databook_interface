<?php
/**
* CalDAV Server - handle PUT method
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst .Net Ltd, Morphoss Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/
dbg_error_log("PUT", "method handler");

require_once('DAVResource.php');

include_once('caldav-PUT-functions.php');

$vcalendar = new vCalendar( $request->raw_post );
$uid = $vcalendar->GetUID();
if ( empty($uid) ) {
  $uid = uuid();
  $vcalendar->SetUID($uid);
}

if ( $add_member ) {
  $request->path = $request->dav_name() . $uid . '.ics';
  $dav_resource = new DAVResource($request->path);
  if ( $dav_resource->Exists() ) {
    $uid = uuid();
    $vcalendar->SetUID($uid);
    $request->path = $request->dav_name() . $uid . '.ics';
    $dav_resource = new DAVResource($request->path);

    if ( $dav_resource->Exists() ) throw new Exception("Failed to generate unique segment name for add-member!");
  }
}
else {
  $dav_resource = new DAVResource($request->path);
}
if ( ! $dav_resource->HavePrivilegeTo('DAV::write-content') ) {
  $request->DoResponse(403,'No write permission');
}

if ( ! $dav_resource->Exists() && ! $dav_resource->HavePrivilegeTo('DAV::bind') ) {
  $request->DoResponse(403,'No bind permission.');
}

if ( ! ini_get('open_basedir') && (isset($c->dbg['ALL']) || (isset($c->dbg['put']) && $c->dbg['put'])) ) {
  $fh = fopen('/var/log/davical/PUT.debug','w');
  if ( $fh ) {
    fwrite($fh,$request->raw_post);
    fclose($fh);
  }
}

controlRequestContainer( $dav_resource->GetProperty('username'), $dav_resource->GetProperty('user_no'), $dav_resource->bound_from(), true);

$lock_opener = $request->FailIfLocked();


if ( $dav_resource->IsCollection()  ) {
  if ( $dav_resource->IsPrincipal() || $dav_resource->IsBinding() || !isset($c->readonly_webdav_collections) || $c->readonly_webdav_collections == true ) {
    $request->DoResponse( 405 ); // Method not allowed
    return;
  }

  $appending = (isset($_GET['mode']) && $_GET['mode'] == 'append' );

  /**
  * CalDAV does not define the result of a PUT on a collection.  We treat that
  * as an import. The code is in caldav-PUT-functions.php
  */
  import_collection($request->raw_post,$request->user_no,$request->path,true, $appending);
  $request->DoResponse( 200 );
  return;
}

$etag = md5($request->raw_post);

$request->CheckEtagMatch( $dav_resource->Exists(), $dav_resource->unique_tag() );

$put_action_type = ($dav_resource->Exists() ? 'UPDATE' : 'INSERT');
$collection = $dav_resource->GetParentContainer();

write_resource( $dav_resource, $request->raw_post, $collection, $session->user_no, $etag,
                                $put_action_type, true, true );

if ( isset($etag) ) header(sprintf('ETag: "%s"', $etag) );

// make sure to return a Location header when add-member was used
if ( $add_member ) header('Location: '.$c->protocol_server_port_script.$request->path);

$request->DoResponse( ($dav_resource->Exists() ? 204 : 201) );
