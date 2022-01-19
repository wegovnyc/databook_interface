<?php
/**
* CalDAV Server - handle BIND method
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Morphoss Ltd - http://www.morphoss.com/
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/
dbg_error_log('BIND', 'method handler');
require_once('AwlQuery.php');

$request->NeedPrivilege('DAV::bind');

if ( ! $request->IsCollection() ) {
  $request->PreconditionFailed(403,'DAV::bind-into-collection',translate('The BIND Request-URI MUST identify a collection.'));
}
$parent_container = $request->path;
if ( preg_match( '{[^/]$}', $parent_container ) ) $parent_container .= '/';

require_once('DAVResource.php');
$parent = new DAVResource( $parent_container );
if ( ! $parent->Exists() || $parent->IsSchedulingCollection() ) {
  $request->PreconditionFailed(403, 'DAV::method-not-allowed',translate('The BIND method is not allowed at that location.') );
}

require_once('XMLDocument.php');
$reply = new XMLDocument(array( 'DAV:' => '' ));

$position = 0;
$xmltree = BuildXMLTree( $request->xml_tags, $position);

$segment = $xmltree->GetElements('DAV::segment');
$segment = $segment[0]->GetContent();

if ( preg_match( '{[/\\\\]}', $segment ) ) {
  $request->PreconditionFailed(403, 'DAV::name-allowed',translate('That destination name contains invalid characters.') );
}

$href    = $xmltree->GetElements('DAV::href');
$href    = $href[0]->GetContent();

$destination_path = $parent_container . $segment .'/';
$destination = new DAVResource( $destination_path );
if ( $destination->Exists() ) {
  $request->PreconditionFailed(403,'DAV::can-overwrite',translate('A resource already exists at the destination.'));
}

//  external binds shouldn't ever point back to ourselves but they should be a valid http[s] url
if ( preg_match ( '{^(?:https?://|file:///)([^/]+)(:[0-9]\+)?/.+$}', $href, $matches )
      && strcasecmp( $matches[0], 'localhost' ) !== 0 && strcasecmp( $matches[0], '127.0.0.1' ) !== 0
      && strcasecmp( $matches[0], $_SERVER['SERVER_NAME'] ) !== 0 && strcasecmp( $matches[0], $_SERVER['SERVER_ADDR'] ) !== 0 ) {
  require_once('external-fetch.php');
  $qry = new AwlQuery( );
  $qry->QDo('SELECT collection_id FROM collection WHERE dav_name = :dav_name ', array( ':dav_name' => '/.external/'. md5($href) ));
  if ( $qry->rows() == 1 && ($row = $qry->Fetch()) ) {
    $dav_id = $row->collection_id;
  }
  else {
    create_external ( '/.external/'. md5($href) ,true,false );
    $qry->QDo('SELECT collection_id FROM collection WHERE dav_name = :dav_name ', array( ':dav_name' => '/.external/'. md5($href) ));
    if ( $qry->rows() != 1 || !($row = $qry->Fetch()) )
      $request->DoResponse(500,translate('Database Error'));
    $dav_id = $row->collection_id;
  }

  $sql = 'INSERT INTO dav_binding ( bound_source_id, access_ticket_id, dav_owner_id, parent_container, dav_name, dav_displayname, external_url, type )
  VALUES( :target_id, :ticket_id, :session_principal, :parent_container, :dav_name, :displayname, :external_url, :external_type )';
  $params = array(
      ':target_id'    => $dav_id,
      ':ticket_id'    => null,
      ':parent_container' => $parent->dav_name(),
      ':session_principal' => $session->principal_id,
      ':dav_name'     => $destination_path,
      ':displayname'  => $segment,
      ':external_url' => $href,
      ':external_type' => 'calendar'
  );
  $qry = new AwlQuery( $sql, $params );
  if ( $qry->Exec('BIND',__LINE__,__FILE__) ) {
    $qry = new AwlQuery( 'SELECT bind_id from dav_binding where dav_name = :dav_name', array( ':dav_name' => $destination_path ) );
    if ( ! $qry->Exec('BIND',__LINE__,__FILE__) || $qry->rows() != 1 || !($row = $qry->Fetch()) )
      $request->DoResponse(500,translate('Database Error'));
    fetch_external ( $row->bind_id, '' );
    $request->DoResponse(201);
  }
  else {
    $request->DoResponse(500,translate('Database Error'));
  }
}
else {
  $source = new DAVResource( $href );
  if ( !$source->Exists() ) {
    $request->PreconditionFailed(403,'DAV::bind-source-exists',translate('The BIND Request MUST identify an existing resource.'));
  }

  if ( $source->IsPrincipal() || !$source->IsCollection() ) {
    $request->PreconditionFailed(403,'DAV::binding-allowed',translate('DAViCal only allows BIND requests for collections at present.'));
  }

  if ( $source->IsBinding() )
    $source = new DAVResource( $source->bound_from() );


  /*
    bind_id INT8 DEFAULT nextval('dav_id_seq') PRIMARY KEY,
    bound_source_id INT8 REFERENCES collection(collection_id) ON UPDATE CASCADE ON DELETE CASCADE,
    access_ticket_id TEXT REFERENCES access_ticket(ticket_id) ON UPDATE CASCADE ON DELETE SET NULL,
    parent_container TEXT NOT NULL,
    dav_name TEXT UNIQUE NOT NULL,
    dav_displayname TEXT,
    external_url TEXT,
    type TEXT
  */

  $sql = 'INSERT INTO dav_binding ( bound_source_id, access_ticket_id, dav_owner_id, parent_container, dav_name, dav_displayname )
  VALUES( :target_id, :ticket_id, :session_principal, :parent_container, :dav_name, :displayname )';
  $params = array(
      ':target_id'    => $source->GetProperty('collection_id'),
      ':ticket_id'    => (isset($request->ticket) ? $request->ticket->id() : null),
      ':parent_container' => $parent->dav_name(),
      ':session_principal' => $session->principal_id,
      ':dav_name'     => $destination_path,
      ':displayname'  => $source->GetProperty('displayname')
  );
  $qry = new AwlQuery( $sql, $params );
  if ( $qry->Exec('BIND',__LINE__,__FILE__) ) {
    header('Location: '. ConstructURL($destination_path) );

    // Uncache anything to do with the target
    $cache = getCacheInstance();
    $cache_ns = 'collection-'.$destination_path;
    $cache->delete( $cache_ns, null );

    $request->DoResponse(201);
  }
  else {
    $request->DoResponse(500,translate('Database Error'));
  }
}
