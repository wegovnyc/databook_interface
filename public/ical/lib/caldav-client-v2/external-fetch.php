<?php
/**
* Functions for managing external BIND resources
*
*
* @package   davical
* @subpackage   external-bind
* @author    Rob Ostensen <rob@boxacle.net>
* @copyright Rob Ostensen
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

function create_external ( $path,$is_calendar,$is_addressbook )
{
  global $request;
  if ( ! function_exists ( "curl_init" ) ) {
    dbg_error_log("external", "external resource cannot be fetched without curl, please install curl");
    $request->DoResponse( 503, translate('PHP5 curl support is required for external binds') );
    return ;
  }
  $resourcetypes = '<DAV::collection/>';
  if ($is_calendar) $resourcetypes .= '<urn:ietf:params:xml:ns:caldav:calendar/>';
  $qry = new AwlQuery();
  if ( ! $qry->QDo( 'INSERT INTO collection ( user_no, parent_container, dav_name, dav_etag, dav_displayname,
                                 is_calendar, is_addressbook, resourcetypes, created )
              VALUES( :user_no, :parent_container, :dav_name, :dav_etag, :dav_displayname,
                      :is_calendar, :is_addressbook, :resourcetypes, current_timestamp )',
           array(
              ':user_no'          => $request->user_no,
              ':parent_container' => '/.external/',
              ':dav_name'         => $path,
              ':dav_etag'         => md5($request->user_no. $path),
              ':dav_displayname'  => $path,
              ':is_calendar'      => ($is_calendar ? 't' : 'f'),
              ':is_addressbook'   => ($is_addressbook ? 't' : 'f'),
              ':resourcetypes'    => $resourcetypes
            ) ) ) {
    $request->DoResponse( 500, translate('Error writing calendar details to database.') );
  }
}

function fetch_external ( $bind_id, $min_age = '1 hour' )
{
  if ( ! function_exists ( "curl_init" ) ) {
    dbg_error_log("external", "external resource cannot be fetched without curl, please install curl");
    $request->DoResponse( 503, translate('PHP5 curl support is required for external binds') );
    return ;
  }
  $sql = 'SELECT collection.*, collection.dav_name AS path, dav_binding.external_url AS external_url FROM dav_binding LEFT JOIN collection ON (collection.collection_id=bound_source_id) WHERE bind_id = :bind_id';
  $params = array( ':bind_id' => $bind_id );
  if ( strlen ( $min_age ) > 2 ) {
    $sql .= ' AND collection.modified + interval :interval < NOW()';
    $params[':interval'] = $min_age;
  }
  $sql .= ' ORDER BY modified DESC LIMIT 1';
  $qry = new AwlQuery( $sql, $params );
  if ( $qry->Exec('DAVResource') && $qry->rows() > 0 && $row = $qry->Fetch() ) {
    $curl = curl_init ( $row->external_url );
    curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, true );
    if ( $row->modified ) {
      $local_ts = new DateTime($row->modified);
      curl_setopt ( $curl, CURLOPT_HEADER, true );
      curl_setopt ( $curl, CURLOPT_FILETIME, true );
      curl_setopt ( $curl, CURLOPT_NOBODY, true );
      curl_setopt ( $curl, CURLOPT_TIMEVALUE, $local_ts->format("U") );
      curl_setopt ( $curl, CURLOPT_TIMECONDITION, CURL_TIMECOND_IFMODSINCE );
      dbg_error_log("external", "checking external resource for remote changes " . $row->external_url );
      $ics = curl_exec ( $curl );
      $info = curl_getinfo ( $curl );
      if ( $info['http_code'] === 304 || (isset($info['filetime']) && $info['filetime'] != -1 && new DateTime("@" . $info['filetime']) <=  $local_ts )) {
        dbg_error_log("external", "external resource unchanged " . $info['filetime'] . '  < ' . $local_ts->getTimestamp() . ' (' . $info['http_code'] . ')');
        curl_close ( $curl );
        // BUGlet: should track server-time instead of local-time
        $qry = new AwlQuery( 'UPDATE collection SET modified=NOW() WHERE collection_id = :cid', array ( ':cid' => $row->collection_id ) );
        $qry->Exec('DAVResource');
        return true;
      }
      dbg_error_log("external", "external resource changed, re importing" . $info['filetime'] );
    }
    else {
      dbg_error_log("external", "fetching external resource for the first time " . $row->external_url );
    }
    curl_setopt ( $curl, CURLOPT_NOBODY, false );
    curl_setopt ( $curl, CURLOPT_HEADER, false );
    $ics = curl_exec ( $curl );
    curl_close ( $curl );
    if ( is_string ( $ics ) && strlen ( $ics ) > 20 ) {
      // BUGlet: should track server-time instead of local-time
      $qry = new AwlQuery( 'UPDATE collection SET modified=NOW(), dav_etag=:etag WHERE collection_id = :cid',
        array ( ':cid' => $row->collection_id, ':etag' => md5($ics) ) );
      $qry->Exec('DAVResource');
      require_once ( 'caldav-PUT-functions.php');
      import_collection ( $ics , $row->user_no, $row->path, 'External Fetch' , false ) ;
      return true;
    }
  }
  else {
    dbg_error_log("external", "external resource up to date or not found id(%s)", $bind_id );
  }
  return false;
}

function update_external ( $request )
{
  global $c;
  if ( $c->external_refresh < 1 )
    return ;
  if ( ! function_exists ( "curl_init" ) ) {
    dbg_error_log("external", "external resource cannot be fetched without curl, please install curl");
    return ;
  }
  $sql = 'SELECT bind_id, external_url as url from dav_binding LEFT JOIN collection ON (collection.collection_id=bound_source_id) WHERE dav_binding.dav_name = :dav_name AND collection.modified + interval :interval < NOW()';
  $qry = new AwlQuery( $sql, array ( ':dav_name' => $request->dav_name(), ':interval' => $c->external_refresh . ' minutes' ) );
  dbg_error_log("external", "checking if external resource needs update");
  if ( $qry->Exec('DAVResource') && $qry->rows() > 0 && $row = $qry->Fetch() ) {
    if ( $row->bind_id != 0 ) {
      dbg_error_log("external", "external resource needs updating, this might take a minute : %s", $row->url );
      fetch_external ( $row->bind_id, $c->external_refresh . ' minutes' );
    }
  }
}
