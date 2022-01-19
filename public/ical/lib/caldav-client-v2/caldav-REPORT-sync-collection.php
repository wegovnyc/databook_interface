<?php
/**
* CalDAV Server - handle sync-collection report (draft-daboo-webdav-sync-01)
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Morphoss Ltd - http://www.morphoss.com/
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

$responses = array();

/**
 * Build the array of properties to include in the report output
 */
$sync_level = $xmltree->GetPath('/DAV::sync-collection/DAV::sync-level');
if ( empty($sync_level) ) {
  $sync_level = $request->depth;
}
else {
  $sync_level = $sync_level[0]->GetContent();
  if ( $sync_level == 'infinity' )
    $sync_level = DEPTH_INFINITY;
  else
    $sync_level = 1;
}

if ( $sync_level == DEPTH_INFINITY ) {
  $request->PreconditionFailed(403, 'DAV::sync-traversal-supported','This server does not support sync-traversal');
}

$sync_tokens = $xmltree->GetPath('/DAV::sync-collection/DAV::sync-token');
if ( isset($sync_tokens[0]) ) $sync_token = $sync_tokens[0]->GetContent();
if ( !isset($sync_token) ) $sync_token = 0;
$sync_token = intval(str_replace('data:,', '', strtolower($sync_token) ));
dbg_error_log( 'sync', " sync-token: %s", $sync_token );

$proplist = array();
$props = $xmltree->GetPath('/DAV::sync-collection/DAV::prop/*');
if ( !empty($props) ) {
  foreach( $props AS $k => $v ) {
    $proplist[] = $v->GetNSTag();
  }
}

function display_status( $status_code ) {
  return sprintf( 'HTTP/1.1 %03d %s', intval($status_code), getStatusMessage($status_code) );
}

$collection = new DAVResource( $request->path );
if ( !$collection->Exists() ) {
  $request->DoResponse( 404 );
}
$bound_from = $collection->bound_from();
$collection_path = $collection->dav_name();
$request_via_binding = ($bound_from != $collection_path);

$params = array( ':collection_id' => $collection->GetProperty('collection_id'), ':sync_token' => $sync_token );
$sql = "SELECT new_sync_token( :sync_token, :collection_id)";
$qry = new AwlQuery($sql, $params);
if ( !$qry->Exec("REPORT",__LINE__,__FILE__) || $qry->rows() <= 0 ) {
  $request->DoResponse( 500, translate("Database error") );
}
$row = $qry->Fetch();

if ( !isset($row->new_sync_token) ) {
  /** If we got a null back then they gave us a sync token we know not of, so provide a full sync */
  $sync_token = 0;
  $params[':sync_token'] = $sync_token;
  if ( !$qry->QDo($sql, $params) || $qry->rows() <= 0 ) {
    $request->DoResponse( 500, translate("Database error") );
  }
  $row = $qry->Fetch();
}
$new_token = $row->new_sync_token;

if ( $sync_token == $new_token ) {
  // No change, so we just re-send the old token.
  $responses[] = new XMLElement( 'sync-token', 'data:,'.$new_token );
}
else {
  $hide_older = '';
  if ( isset($c->hide_older_than) && intval($c->hide_older_than) > 0 )
    $hide_older = " AND (CASE WHEN caldav_data.caldav_type<>'VEVENT' OR calendar_item.dtstart IS NULL THEN true ELSE calendar_item.dtstart > (now() - interval '".intval($c->hide_older_than)." days') END)";

  $hide_todo = '';
  if ( isset($c->hide_TODO) && ($c->hide_TODO === true || (is_string($c->hide_TODO) && preg_match($c->hide_TODO, $_SERVER['HTTP_USER_AGENT']))) && ! $collection->HavePrivilegeTo('all') )
    $hide_todo = " AND caldav_data.caldav_type NOT IN ('VTODO') ";

  if ( $sync_token == 0 ) {
    $sql = <<<EOSQL
  SELECT collection.*, calendar_item.*, caldav_data.*, addressbook_resource.*, 201 AS sync_status FROM collection
              LEFT JOIN caldav_data USING (collection_id)
              LEFT JOIN calendar_item USING (dav_id)
                           LEFT JOIN addressbook_resource USING (dav_id)
              WHERE collection.collection_id = :collection_id $hide_older $hide_todo
     ORDER BY collection.collection_id, caldav_data.dav_id
EOSQL;
    unset($params[':sync_token']);
  }
  else {
    $sql = <<<EOSQL
  SELECT collection.*, calendar_item.*, caldav_data.*, addressbook_resource.*, sync_changes.*
    FROM collection LEFT JOIN sync_changes USING(collection_id)
                           LEFT JOIN caldav_data USING (collection_id,dav_id)
                           LEFT JOIN calendar_item USING (collection_id,dav_id)
                           LEFT JOIN addressbook_resource USING (dav_id)
                           WHERE collection.collection_id = :collection_id $hide_older $hide_todo
         AND sync_time >= (SELECT modification_time FROM sync_tokens WHERE sync_token = :sync_token)
EOSQL;
    if ( isset($c->strict_result_ordering) && $c->strict_result_ordering ) {
      $sql .= " ORDER BY collection.collection_id, lower(sync_changes.dav_name), sync_changes.sync_time";
    }
    else {
      $sql .= " ORDER BY collection.collection_id, sync_changes.dav_name, sync_changes.sync_time";
    }
  }
  $qry = new AwlQuery($sql, $params );

  $last_dav_name = '';
  $first_status = 0;

  if ( $qry->Exec("REPORT",__LINE__,__FILE__) ) {
    if ( $qry->rows() > 50 ) {
      // If there are more than 50 rows to send we should not send full data in response ...
      $c->sync_resource_data_ok = false;
    }
    while( $object = $qry->Fetch() ) {
      if ( $request_via_binding )
        $object->dav_name = str_replace( $bound_from, $collection_path, $object->dav_name);

      if ( $object->dav_name == $last_dav_name ) {
        /** The complex case: this is the second or subsequent for this dav_id */
        if ( $object->sync_status == 404 ) {
          array_pop($responses);
          $resultset = array(
            new XMLElement( 'href', ConstructURL($object->dav_name) ),
            new XMLElement( 'status', display_status($object->sync_status) )
          );
          $responses[] = new XMLElement( 'response', $resultset );
          $first_status = 404;
        }
        else if ( $object->sync_status == 201 && $first_status == 404 ) {
          // ... Delete ... Create ... is indicated as a create, but don't forget we started with a delete
          array_pop($responses);
          $dav_resource = new DAVResource($object);
          $resultset = $dav_resource->GetPropStat($proplist,$reply);
          array_unshift($resultset, new XMLElement( 'href', ConstructURL($object->dav_name)));
          $responses[] = new XMLElement( 'response', $resultset );
        }
        /** Else:
         *    the object existed at start and we have multiple modifications,
         *  or,
         *    the object didn't exist at start and we have subsequent modifications,
         *  but:
         *    in either case we simply stick with our existing report.
         */
      }
      else {
        /** The simple case: this is the first one for this dav_id */
        if ( $object->sync_status == 404 ) {
          $resultset = array(
            new XMLElement( 'href', ConstructURL($object->dav_name) ),
            new XMLElement( 'status', display_status($object->sync_status) )
          );
          $first_status = 404;
        }
        else {
          $dav_resource = new DAVResource($object);
          $resultset = $dav_resource->GetPropStat($proplist,$reply);
          array_unshift($resultset, new XMLElement( 'href', ConstructURL($object->dav_name)));
          $first_status = $object->sync_status;
        }
        $responses[] = new XMLElement( 'response', $resultset );
        $last_dav_name  = $object->dav_name;
      }
    }
    $responses[] = new XMLElement( 'sync-token', 'data:,'.$new_token );
  }
  else {
    $request->DoResponse( 500, translate("Database error") );
  }
}

$multistatus = new XMLElement( "multistatus", $responses, $reply->GetXmlNsArray() );

$request->XMLResponse( 207, $multistatus );
