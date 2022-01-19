<?php
/**
* CalDAV Server - handle REPORT method
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@morphoss.com>
* @copyright Catalyst .Net Ltd, Morphoss Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2
*/
dbg_error_log("REPORT", "method handler");

require_once("XMLDocument.php");
require_once('DAVResource.php');

require_once('RRule.php');

if ( ! ini_get('open_basedir') && (isset($c->dbg['ALL']) || (isset($c->dbg['report']) && $c->dbg['report'])) ) {
  $fh = fopen('/var/log/davical/REPORT.debug','w');
  if ( $fh ) {
    fwrite($fh,$request->raw_post);
    fclose($fh);
  }
}

if ( !isset($request->xml_tags) ) {
  $request->DoResponse( 406, translate("REPORT body contains no XML data!") );
}
$position = 0;
$xmltree = BuildXMLTree( $request->xml_tags, $position);
if ( !is_object($xmltree) ) {
  $request->DoResponse( 406, translate("REPORT body is not valid XML data!") );
}

$target = new DAVResource($request->path);

if ( $xmltree->GetNSTag() != 'DAV::principal-property-search'
                && $xmltree->GetNSTag() != 'DAV::principal-property-search-set' ) {
  $target->NeedPrivilege( array('DAV::read', 'urn:ietf:params:xml:ns:caldav:read-free-busy'), true ); // They may have either
}

require_once("vCalendar.php");

$reportnum = -1;
$report = array();
$denied = array();
$unsupported = array();
if ( isset($prop_filter) ) unset($prop_filter);

if ( $xmltree->GetNSTag() == 'urn:ietf:params:xml:ns:caldav:free-busy-query' ) {
  include("caldav-REPORT-freebusy.php");
  exit; // Not that the above include should return anyway
}

$reply = new XMLDocument( array( "DAV:" => "" ) );
switch( $xmltree->GetNSTag() ) {
  case 'DAV::principal-property-search':
    include("caldav-REPORT-principal.php");
    exit; // Not that it should return anyway.
  case 'DAV::principal-search-property-set':
    include("caldav-REPORT-pps-set.php");
    exit; // Not that it should return anyway.
  case 'DAV::sync-collection':
    if ( $target->IsExternal() ) {
      require_once("external-fetch.php");
      update_external ( $target );
    }
    include("caldav-REPORT-sync-collection.php");
    exit; // Not that it should return anyway.
  case 'DAV::expand-property':
    if ( $target->IsExternal() ) {
      require_once("external-fetch.php");
      update_external ( $target );
    }
    include("caldav-REPORT-expand-property.php");
    exit; // Not that it should return anyway.
  case 'DAV::principal-match':
    include("caldav-REPORT-principal-match.php");
    exit; // Not that it should return anyway.
}

/**
* check if we need to do expansion of recurring events
* @param object $calendar_data_node
*/
function check_for_expansion( $calendar_data_node ) {
  global $need_expansion, $expand_range_start, $expand_range_end, $expand_as_floating;

  if ( !class_exists('DateTime') ) return; /** We don't support expansion on PHP5.1 */

  $expansion = $calendar_data_node->GetElements('urn:ietf:params:xml:ns:caldav:expand');
  if ( isset($expansion[0]) ) {
    $need_expansion = true;
    $expand_range_start = $expansion[0]->GetAttribute('start');
    $expand_range_end = $expansion[0]->GetAttribute('end');
    $expand_as_floating = $expansion[0]->GetAttribute('floating');
    if ( isset($expand_range_start) ) $expand_range_start = new RepeatRuleDateTime($expand_range_start);
    if ( isset($expand_range_end) )   $expand_range_end   = new RepeatRuleDateTime($expand_range_end);
    if ( isset($expand_as_floating) && $expand_as_floating == "yes" )
      $expand_as_floating = true;
    else
      $expand_as_floating = false;
  }
}


/**
* Return XML for a single component from the DB
*
* @param array $properties The properties for this component
* @param string $item The DB row data for this component
*
* @return string An XML document which is the response for the component
*/
function component_to_xml( $properties, $item ) {
  global $session, $c, $request, $reply;

  dbg_error_log("REPORT","Building XML Response for item '%s'", $item->dav_name );

  $denied = array();
  $unsupported = array();
  $caldav_data = $item->caldav_data;
  $displayname = preg_replace( '{^.*/}', '', $item->dav_name );
  $type = 'unknown';
  $contenttype = 'text/plain';
  switch( strtoupper($item->caldav_type) ) {
    case 'VJOURNAL':
    case 'VEVENT':
    case 'VTODO':
      $displayname = $item->summary;
      $type = 'calendar';
      $contenttype = 'text/calendar';
      if ( isset($properties['urn:ietf:params:xml:ns:caldav:calendar-data']) || isset($properties['DAV::displayname']) ) {
        if ( !$request->AllowedTo('all') && $session->user_no != $item->user_no ) {
          // the user is not admin / owner of this calendar looking at his calendar and can not admin the other cal
          if ( $item->class == 'CONFIDENTIAL' || !$request->AllowedTo('read') ) {
            dbg_error_log("REPORT","Anonymising confidential event for: %s", $item->dav_name );
            $vcal = new vCalendar( $caldav_data );
            $caldav_data = $vcal->Confidential()->Render();
            $displayname = translate('Busy');
          }
        }
      }

      if ( isset($c->hide_alarm) && $c->hide_alarm ) {
        $dav_resource = new DAVResource($item->dav_name);
        if ( isset($properties['urn:ietf:params:xml:ns:caldav:calendar-data']) && !$dav_resource->HavePrivilegeTo('write') ) {
          dbg_error_log("REPORT","Stripping event alarms for: %s", $item->dav_name );
          $vcal = new vCalendar($caldav_data);
          $vcal->ClearComponents('VALARM');
          $caldav_data = $vcal->Render();
        }
      }
      break;

    case 'VCARD':
      $displayname = $item->fn;
      $type = 'vcard';
      $contenttype = 'text/vcard';
      break;
  }

  $url = ConstructURL($item->dav_name);

  $prop = new XMLElement("prop");
  $need_resource = false;
  foreach( $properties AS $full_tag => $v ) {
    $base_tag = preg_replace('{^.*:}', '', $full_tag );
    switch( $full_tag ) {
      case 'DAV::getcontentlength':
        $contentlength = strlen($caldav_data);
        $prop->NewElement($base_tag, $contentlength );
        break;
      case 'DAV::getlastmodified':
        $prop->NewElement($base_tag, ISODateToHTTPDate($item->modified) );
        break;
      case 'urn:ietf:params:xml:ns:caldav:calendar-data':
        if ( $type == 'calendar' ) $reply->CalDAVElement($prop, $base_tag, $caldav_data );
        else $unsupported[] = $base_tag;
        break;
      case 'urn:ietf:params:xml:ns:carddav:address-data':
        if ( $type == 'vcard' ) $reply->CardDAVElement($prop, $base_tag, $caldav_data );
        else $unsupported[] = $base_tag;
        break;
      case 'DAV::getcontenttype':
        $prop->NewElement($base_tag, $contenttype );
        break;
      case 'DAV::current-user-principal':
        $prop->NewElement("current-user-principal", $request->current_user_principal_xml);
        break;
      case 'DAV::displayname':
        $prop->NewElement($base_tag, $displayname );
        break;
      case 'DAV::resourcetype':
        $prop->NewElement($base_tag); // Just an empty resourcetype for a non-collection.
        break;
      case 'DAV::getetag':
        $prop->NewElement($base_tag, '"'.$item->dav_etag.'"' );
        break;
      case '"current-user-privilege-set"':
        $prop->NewElement($base_tag, privileges($request->permissions) );
        break;
      default:
        // It's harder.  We need the DAVResource() to get this one.
        $need_resource = true;
    }
    if ( $need_resource ) break;
  }
  $href = new XMLElement("href", $url );
  if ( $need_resource ) {
    if ( !isset($dav_resource) ) $dav_resource = new DAVResource($item->dav_name);
    $elements = $dav_resource->GetPropStat(array_keys($properties), $reply);
    array_unshift($elements, $href);
  }
  else {
    $elements = array($href);
    $status = new XMLElement("status", "HTTP/1.1 200 OK" );
    $elements[] = new XMLElement( "propstat", array( $prop, $status) );
    if ( count($denied) > 0 ) {
      $status = new XMLElement("status", "HTTP/1.1 403 Forbidden" );
      $noprop = new XMLElement("prop");
      foreach( $denied AS $k => $v ) {
        $reply->NSElement($noprop, $v);
      }
      $elements[] = new XMLElement( "propstat", array( $noprop, $status) );
    }

    if ( ! $request->PreferMinimal() && count($unsupported) > 0 ) {
      $status = new XMLElement("status", "HTTP/1.1 404 Not Found" );
      $noprop = new XMLElement("prop");
      foreach( $unsupported AS $k => $v ) {
        $reply->NSElement($noprop, $v);
      }
      $elements[] = new XMLElement( "propstat", array( $noprop, $status) );
    }
  }

  $response = new XMLElement( "response", $elements );

  return $response;
}

if ( $target->IsExternal() ) {
  require_once("external-fetch.php");
  update_external ( $target );
}

// These reports are always allowed to see the resource_data because they are special
$c->sync_resource_data_ok = true;

if ( $xmltree->GetNSTag() == "urn:ietf:params:xml:ns:caldav:calendar-query" ) {
  $calquery = $xmltree->GetPath("/urn:ietf:params:xml:ns:caldav:calendar-query/*");
  include("caldav-REPORT-calquery.php");
}
elseif ( $xmltree->GetNSTag() == "urn:ietf:params:xml:ns:caldav:calendar-multiget" ) {
  $mode = 'caldav';
  $qry_content = $xmltree->GetContent('urn:ietf:params:xml:ns:caldav:calendar-multiget');
  include("caldav-REPORT-multiget.php");
}
elseif ( $xmltree->GetNSTag() == "urn:ietf:params:xml:ns:carddav:addressbook-multiget" ) {
  $mode = 'carddav';
  $qry_content = $xmltree->GetContent('urn:ietf:params:xml:ns:carddav:addressbook-multiget');
  include("caldav-REPORT-multiget.php");
}
elseif ( $xmltree->GetNSTag() == "urn:ietf:params:xml:ns:carddav:addressbook-query" ) {
  $cardquery = $xmltree->GetPath("/urn:ietf:params:xml:ns:carddav:addressbook-query/*");
  include("caldav-REPORT-cardquery.php");
}
else {
  dbg_error_log( 'ERROR', "Request for unsupported report type '%s'.", $xmltree->GetNSTag() );
  $request->PreconditionFailed( 403, 'DAV::supported-report', sprintf( '"%s" is not a supported report type', $xmltree->GetNSTag()) );
}

