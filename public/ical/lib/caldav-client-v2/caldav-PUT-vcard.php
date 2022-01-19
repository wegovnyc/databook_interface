<?php
/**
* CalDAV Server - handle PUT method on VCARD content-types
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@morphoss.com>
* @copyright Morphoss Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/
dbg_error_log("PUT", "method handler");

require_once('DAVResource.php');

include_once('caldav-PUT-functions.php');

if ( ! ini_get('open_basedir') && (isset($c->dbg['ALL']) || (isset($c->dbg['put']) && $c->dbg['put'])) ) {
  $fh = fopen('/var/log/davical/PUT.debug','w');
  if ( $fh ) {
    fwrite($fh,$request->raw_post);
    fclose($fh);
  }
}

$lock_opener = $request->FailIfLocked();

require_once('vcard.php');
$vcard = new vCard( $request->raw_post );
$uid = $vcard->GetPValue('UID');
if ( empty($uid) ) {
  $uid = uuid();
  $vcard->AddProperty('UID',$uid);
}

if ( $add_member ) {
  $request->path = $request->dav_name() . $uid . '.vcf';
  $dest = new DAVResource($request->path);
  if ( $dest->Exists() ) {
    $uid = uuid();
    $vcard->AddProperty('UID',$uid);
    $request->path = $request->dav_name() . $uid . '.vcf';
    $dest = new DAVResource($request->path);
    if ( $dest->Exists() ) throw new Exception("Failed to generate unique segment name for add-member!");
  }
}
else {
  $dest = new DAVResource($request->path);
}

$container = $dest->GetParentContainer();
if ( ! $dest->Exists() ) {
  if ( $container->IsPrincipal() ) {
    $request->PreconditionFailed(405,'method-not-allowed',translate('A DAViCal principal collection may only contain collections'));
  }
  if ( ! $container->Exists() ) {
    $request->PreconditionFailed( 409, 'collection-must-exist',translate('The destination collection does not exist') );
  }
  $container->NeedPrivilege('DAV::bind');
}
else {
  if ( $dest->IsCollection() ) {
    if ( ! isset($c->readonly_webdav_collections) || $c->readonly_webdav_collections ) {
      $request->PreconditionFailed(405,'method-not-allowed',translate('You may not PUT to a collection URL'));
    }
  }
  $dest->NeedPrivilege('DAV::write-content');
}

$request->CheckEtagMatch( $dest->Exists(), $dest->unique_tag() );

$user_no = $dest->GetProperty('user_no');
$collection_id = $container->GetProperty('collection_id');

$original_etag = md5($request->raw_post);

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
elseif ( stripos($last_modified, 'TZ') ) {
  // At least one of my examples has this crap.
  $last_modified = str_replace('TZ','T000000Z',$last_modified);
  $vcard->ClearProperties('REV');
  $vcard->AddProperty('REV',$last_modified);
}
elseif( preg_match('{^(\d{8})(\d{6})Z?}', $last_modified, $matches) ) {
  // 'T' missing
  $last_modified = $matches[1] . 'T' . $matches[2] . 'Z';
  $vcard->ClearProperties('REV');
  $vcard->AddProperty('REV',$last_modified);
}
elseif( preg_match('{([0-9]{4})-([0-9]{2})-([0-9]{2}T)([0-9]{2}):([0-9]{2}):([0-9]{2})Z?}', $last_modified, $matches) ){
  // iOS sends these, see http://tools.ietf.org/html/rfc2426#section-3.6.4
  $last_modified = $matches[1] . $matches[2] .$matches[3]  .$matches[4] .$matches[5]. $matches[6] . 'Z';
  $vcard->ClearProperties('REV');
  $vcard->AddProperty('REV', $last_modified);
}
elseif( !preg_match('{^\d{8}T\d{6}Z$}', $last_modified) ) {
  // reset to timestamp format, see https://tools.ietf.org/html/rfc6350#section-6.7.4
  $last_modified = gmdate( 'Ymd\THis\Z' );
  $vcard->ClearProperties('REV');
  $vcard->AddProperty('REV',$last_modified);
}
$rendered_card = $vcard->Render();
$etag = md5($rendered_card);
$params = array(
    ':user_no' => $user_no,
    ':dav_name' => $dest->bound_from(),
    ':etag' => $etag,
    ':dav_data' => $rendered_card,
    ':session_user' => $session->user_no,
    ':modified' => $last_modified
);

if ($dest->IsCollection()) {
  if ( $dest->IsPrincipal() || $dest->IsBinding() || !isset($c->readonly_webdav_collections) || $c->readonly_webdav_collections == true ) {
    $request->DoResponse( 405 ); // Method not allowed
    return;
  }

  $appending = (isset($_GET['mode']) && $_GET['mode'] == 'append' );

  import_collection($request->raw_post, $request->user_no, $request->path, true, $appending);

  $request->DoResponse( 200 );
  return;
}


$qry = new AwlQuery();
$qry->Begin();

if ( $dest->Exists() ) {
  $sql = 'UPDATE caldav_data SET caldav_data=:dav_data, dav_etag=:etag, logged_user=:session_user,
          modified=:modified, user_no=:user_no, caldav_type=\'VCARD\' WHERE dav_name=:dav_name';
  $response_code = 200;
  $put_action_type = 'UPDATE';
  $qry->QDo( $sql, $params );

  $qry->QDo("SELECT dav_id FROM caldav_data WHERE dav_name = :dav_name ", array(':dav_name' => $params[':dav_name']) );
}
else {
  $sql = 'INSERT INTO caldav_data ( user_no, dav_name, dav_etag, caldav_data, caldav_type, logged_user, created, modified, collection_id )
          VALUES( :user_no, :dav_name, :etag, :dav_data, \'VCARD\', :session_user, current_timestamp, :modified, :collection_id )';
  $params[':collection_id'] = $collection_id;
  $response_code = 201;
  $qry->QDo( $sql, $params );
  $put_action_type = 'INSERT';

  $qry->QDo("SELECT currval('dav_id_seq') AS dav_id" );
}
$row = $qry->Fetch();

$vcard->Write( $row->dav_id, $dest->Exists() );

$qry->QDo("SELECT write_sync_change( $collection_id, $response_code, :dav_name)", array(':dav_name' => $dest->bound_from() ) );

if ( function_exists('log_caldav_action') ) {
  log_caldav_action( $put_action_type, $uid, $user_no, $collection_id, $request->path );
}
else if ( isset($log_action) && $log_action  ) {
  dbg_error_log( 'PUT', 'No log_caldav_action( %s, %s, %s, %s, %s) can be called.',
      $put_action_type, $uid, $user_no, $collection_id, $request->path );
}


if ( !$qry->Commit() ) {
   $qry->Rollback();
   $request->DoResponse( 500, "A database error occurred" );
}

// Uncache anything to do with the collection
$cache = getCacheInstance();
$cache->delete( 'collection-'.$container->dav_name(), null );

if ( $add_member ) header('Location: '.$c->protocol_server_port_script.$request->path);

if ( $etag == $original_etag ) header('ETag: "'. $etag . '"' ); // Only send the ETag if we didn't change what they gave us.
if ( $response_code == 200 ) $response_code = 204;
$request->DoResponse( $response_code );
