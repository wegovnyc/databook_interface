<?php
/**
* CalDAV Server - handle DELETE method
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst .Net Ltd, Morphoss Ltd <http://www.morphoss.com/>
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/
dbg_error_log("delete", "DELETE method handler");

require_once('DAVResource.php');
$dav_resource = new DAVResource($request->path);
$container = $dav_resource->GetParentContainer();
$container->NeedPrivilege('DAV::unbind');

$lock_opener = $request->FailIfLocked();

require_once('schedule-functions.php');

function delete_collection( $id ) {
  global $session, $request;

  $params = array( ':collection_id' => $id );
  $qry = new AwlQuery('SELECT child.collection_id AS child_id FROM collection child JOIN collection parent ON (parent.dav_name = child.parent_container) WHERE parent.collection_id = :collection_id', $params );
  if ( $qry->Exec('DELETE',__LINE__,__FILE__) && $qry->rows() > 0 ) {
    while( $row = $qry->Fetch() ) {
      delete_collection($row->child_id);
    }
  }

  if ( $qry->QDo("SELECT write_sync_change(collection_id, 404, caldav_data.dav_name) FROM caldav_data WHERE collection_id = :collection_id", $params )
    && $qry->QDo("DELETE FROM property WHERE dav_name LIKE (SELECT dav_name FROM collection WHERE collection_id = :collection_id) || '%'", $params )
    && $qry->QDo("DELETE FROM locks WHERE dav_name LIKE (SELECT dav_name FROM collection WHERE collection_id = :collection_id) || '%'", $params )
    && $qry->QDo("DELETE FROM caldav_data WHERE collection_id = :collection_id", $params )
    && $qry->QDo("DELETE FROM collection WHERE collection_id = :collection_id", $params ) ) {
    @dbg_error_log( "DELETE", "DELETE (collection): User: %d, ETag: %s, Path: %s", $session->user_no, $request->etag_if_match, $request->path);
    return true;
  }
  return false;
}


if ( !$dav_resource->Exists() )$request->DoResponse( 404 );

if ( ! ( $dav_resource->resource_id() > 0 ) ) {
  @dbg_error_log( "DELETE", ": failed: User: %d, ETag: %s, Path: %s, ResourceID: %d", $session->user_no, $request->etag_if_match, $request->path, $dav_resource->resource_id());
  $request->DoResponse( 403 );
}

$qry = new AwlQuery();
$qry->Begin();

if ( $dav_resource->IsCollection() ) {
  $cache = getCacheInstance();
  $myLock = $cache->acquireLock('collection-'.$dav_resource->parent_path());
  if ( $dav_resource->IsBinding() ) {
    $params = array( ':dav_name' => $dav_resource->dav_name() );

    if ( $qry->QDo("DELETE FROM dav_binding WHERE dav_name = :dav_name", $params )
      && $qry->Commit() ) {
      $cache->delete( 'collection-'.$dav_resource->dav_name(), null );
      $cache->delete( 'collection-'.$dav_resource->parent_path(), null );
      $cache->releaseLock($myLock);
      @dbg_error_log( "DELETE", "DELETE: Binding: %d, ETag: %s, Path: %s", $session->user_no, $request->etag_if_match, $request->path);
      $request->DoResponse( 204 );
    }
  }
  else {
    if ( delete_collection( $dav_resource->resource_id() ) && $qry->Commit() ) {
      // Uncache anything to do with the collection
      $cache->delete( 'collection-'.$dav_resource->dav_name(), null );
      $cache->delete( 'collection-'.$dav_resource->parent_path(), null );
      $cache->releaseLock($myLock);
      $request->DoResponse( 204 );
    }
  }
  $cache->releaseLock($myLock);
}
else {
  if ( isset($request->etag_if_match) && $request->etag_if_match != $dav_resource->unique_tag() && $request->etag_if_match != "*"  ) {
    $request->DoResponse( 412, translate("Resource has changed on server - not deleted") );
  }

  // Check to see if we need to do any scheduling transactions for this one.
  do_scheduling_for_delete($dav_resource);

  // We need to serialise access to this process just for this collection
  $cache = getCacheInstance();
  $myLock = $cache->acquireLock('collection-'.$dav_resource->parent_path());

  $collection_id = $dav_resource->GetProperty('collection_id');
  $params = array( ':dav_id' => $dav_resource->resource_id() );
  if ( $qry->QDo("DELETE FROM property WHERE dav_name = (SELECT dav_name FROM caldav_data WHERE dav_id = :dav_id)", $params )
    && $qry->QDo("DELETE FROM locks WHERE dav_name = (SELECT dav_name FROM caldav_data WHERE dav_id = :dav_id)", $params )
    && $qry->QDo("SELECT write_sync_change(collection_id, 404, caldav_data.dav_name) FROM caldav_data WHERE dav_id = :dav_id", $params )
    && $qry->QDo("DELETE FROM caldav_data WHERE dav_id = :dav_id", $params ) ) {
    if ( function_exists('log_caldav_action') ) {
      log_caldav_action( 'DELETE', $dav_resource->GetProperty('uid'), $dav_resource->GetProperty('user_no'), $collection_id, $request->path );
    }

    $qry->Commit();
    @dbg_error_log( "DELETE", "DELETE: User: %d, ETag: %s, Path: %s", $session->user_no, $request->etag_if_match, $request->path);

    if ( function_exists('post_commit_action') ) {
      post_commit_action( 'DELETE', $dav_resource->GetProperty('uid'), $dav_resource->GetProperty('user_no'), $collection_id, $request->path );
    }

    $cache->delete( 'collection-'.$dav_resource->parent_path(), null );
    $cache->releaseLock($myLock);
    $request->DoResponse( 204 );
  }
  $cache->releaseLock($myLock);
}

$request->DoResponse( 500 );
