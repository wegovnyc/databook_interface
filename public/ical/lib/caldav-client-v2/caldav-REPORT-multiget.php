<?php
/**
* Handle the calendar-multiget REPORT request.
*/

$responses = array();

/**
 * Build the array of properties to include in the report output
 */

$proptype = $qry_content[0]->GetNSTag();
$properties = array();
$need_expansion = false;
switch( $proptype ) {
  case 'DAV::prop':
    $qry_props = $xmltree->GetPath('/*/'.$proptype.'/*');
    foreach( $qry_content[0]->GetElements() AS $k => $v ) {
      $properties[$v->GetNSTag()] = 1;
      if ( $v->GetNSTag() == 'urn:ietf:params:xml:ns:caldav:calendar-data' ) check_for_expansion($v);
    }
    break;

  case 'DAV::allprop':
    $properties['DAV::allprop'] = 1;
    if ( $qry_content[1]->GetNSTag() == 'DAV::include' ) {
      foreach( $qry_content[1]->GetElements() AS $k => $v ) {
        $include_properties[] = $v->GetNSTag(); /** $include_properties is referenced in DAVResource where allprop is expanded */
        if ( $v->GetNSTag() == 'urn:ietf:params:xml:ns:caldav:calendar-data' ) check_for_expansion($v);
      }
    }
    break;

  default:
    $properties[$proptype] = 1;
}
if ( empty($properties) ) $properties['DAV::allprop'] = 1;

$collection = new DAVResource($request->path);
$bound_from = $collection->bound_from();

/**
 * Build the href list for the IN ( href, href, href, ... ) clause.
 */
$mg_hrefs = $xmltree->GetPath('/*/DAV::href');
$href_in = '';
$params = array();
foreach( $mg_hrefs AS $k => $v ) {
  /**
   * We need this to work if they specified a relative *or* a full path, so we strip off
   * anything up to the matching request->path (which will include any http...) and then
   * put the $bound_from prefix back on.
   */
  $rawurl = rawurldecode($v->GetContent());
  $path_pos = strpos($rawurl,$request->path);
  if ( $path_pos === false ) {
    $href = $bound_from . $rawurl;
  }
  else {
    $href = $bound_from . substr( $rawurl, $path_pos + strlen($request->path));
  }
  @dbg_error_log("REPORT", 'Reporting on href "%s"', $href );
  $href_in .= ($href_in == '' ? '' : ', ');
  $href_in .= ':href'.$k;
  $params[':href'.$k] = $href;
}

$where = " WHERE caldav_data.collection_id = " . $collection->resource_id();
$where .= " AND caldav_data.dav_name IN ( $href_in ) ";

if ( $mode == 'caldav' ) {
  if ( $collection->Privileges() != privilege_to_bits('DAV::all') ) {
    $where .= " AND (calendar_item.class != 'PRIVATE' OR calendar_item.class IS NULL) ";
  }
}
$sql = 'SELECT calendar_item.*, addressbook_resource.*, caldav_data.* FROM caldav_data
                  LEFT JOIN calendar_item USING(dav_id, user_no, dav_name, collection_id)
                  LEFT JOIN addressbook_resource USING(dav_id)
                  LEFT JOIN collection USING(collection_id)';

/**
 * @todo: Add stanzas for missing rows, so we don't just return a blank multistatus but
 * actually return <response> stanzas with a 404 for each absent href.  We could do
 * this relatively easily with an array_flip($params) and remove each matching dav_name
 * as we process it.
 */
if ( isset($c->strict_result_ordering) && $c->strict_result_ordering ) $where .= " ORDER BY caldav_data.dav_id";
$qry = new AwlQuery( $sql . $where, $params );
if ( $qry->Exec('REPORT',__LINE__,__FILE__) && $qry->rows() > 0 ) {
  while( $dav_object = $qry->Fetch() ) {
    if ( $bound_from != $collection->dav_name() ) {
      $dav_object->dav_name = str_replace( $bound_from, $collection->dav_name(), $dav_object->dav_name);
    }
    if ( $need_expansion ) {
      $vResource = new vComponent($dav_object->caldav_data);
      $expanded = expand_event_instances($vResource, $expand_range_start, $expand_range_end);
      $dav_object->caldav_data = $expanded->Render();
    }
    $responses[] = component_to_xml( $properties, $dav_object );
  }
}

$multistatus = new XMLElement( "multistatus", $responses, $reply->GetXmlNsArray() );

$request->XMLResponse( 207, $multistatus );
