<?php
/**
* CalDAV Server - handle PROPPATCH method
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Morphoss Ltd - http://www.morphoss.com/
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2
*/
dbg_error_log("PROPPATCH", "method handler");

require_once('vCalendar.php');
require_once('DAVResource.php');

$dav_resource = new DAVResource($request->path);
if ( !$dav_resource->HavePrivilegeTo('DAV::write-properties') ) {
  $parent = $dav_resource->GetParentContainer();
  if ( !$dav_resource->IsBinding() || !$parent->HavePrivilegeTo('DAV::write') ) {
    $request->PreconditionFailed(403, 'DAV::write-properties', 'You do not have permission to write properties to that resource' );
  }
}

$position = 0;
$xmltree = BuildXMLTree( $request->xml_tags, $position);

// echo $xmltree->Render();

if ( $xmltree->GetNSTag() != "DAV::propertyupdate" ) {
  $request->PreconditionFailed( 403, 'DAV::propertyupdate', 'XML request did not contain a &lt;propertyupdate&gt; tag' );
}

/**
* Find the properties being set, and the properties being removed
*/
$setprops = $xmltree->GetPath("/DAV::propertyupdate/DAV::set/DAV::prop/*");
$rmprops  = $xmltree->GetPath("/DAV::propertyupdate/DAV::remove/DAV::prop/*");

/**
* We build full status responses for failures.  For success we just record
* it, since the multistatus response only applies to failure.  While it is
* not explicitly stated in RFC2518, from reading between the lines (8.2.1)
* a success will return 200 OK [with an empty response].
*/
$failure   = array();
$success   = array();

$reply = new XMLDocument( array( 'DAV:' => '') );

/**
 * Small utility function to add propstat for one failure
 * @param unknown_type $tag
 * @param unknown_type $status
 * @param unknown_type $description
 * @param unknown_type $error_tag
 */
function add_failure( $type, $tag, $status, $description=null, $error_tag = null) {
  global $failure, $reply;
  $prop = new XMLElement('prop');
  $reply->NSElement($prop, $tag);
  $propstat = array($prop,new XMLElement( 'status', $status ));

  if ( isset($description))
    $propstat[] = new XMLElement( 'responsedescription', $description );
  if ( isset($error_tag) )
    $propstat[] = new XMLElement( 'error', new XMLElement( $error_tag ) );

  $failure[$type.'-'.$tag] = new XMLElement('propstat', $propstat );
}


/**
* Not much for it but to process the incoming settings in a big loop, doing
* the special-case stuff as needed and falling through to a default which
* stuffs the property somewhere we will be able to retrieve it from later.
*/
$qry = new AwlQuery();
$qry->Begin();
$setcalendar = count($xmltree->GetPath('/DAV::propertyupdate/DAV::set/DAV::prop/DAV::resourcetype/urn:ietf:params:xml:ns:caldav:calendar'));
foreach( $setprops AS $k => $setting ) {
  $tag = $setting->GetNSTag();
  $content = $setting->RenderContent(0,null,true);

  switch( $tag ) {

    case 'DAV::displayname':
      /**
      * Can't set displayname on resources - only collections or principals
      */
      if ( $dav_resource->IsCollection() || $dav_resource->IsPrincipal() ) {
        if ( $dav_resource->IsBinding() ) {
          $qry->QDo('UPDATE dav_binding SET dav_displayname = :displayname WHERE dav_name = :dav_name',
                                            array( ':displayname' => $content, ':dav_name' => $dav_resource->dav_name()) );
        }
        else if ( $dav_resource->IsPrincipal() ) {
          $qry->QDo('UPDATE dav_principal SET fullname = :displayname, displayname = :displayname, modified = current_timestamp WHERE user_no = :user_no',
                                            array( ':displayname' => $content, ':user_no' => $request->user_no) );
        }
        else {
          $qry->QDo('UPDATE collection SET dav_displayname = :displayname, modified = current_timestamp WHERE dav_name = :dav_name',
                                            array( ':displayname' => $content, ':dav_name' => $dav_resource->dav_name()) );
        }
        $success[$tag] = 1;
      }
      else {
        add_failure('set', $tag, 'HTTP/1.1 403 Forbidden',
             translate("The displayname may only be set on collections, principals or bindings."), 'cannot-modify-protected-property');
      }
      break;

    case 'DAV::resourcetype':
      /**
      * We only allow resourcetype setting on a normal collection, and not on a resource, a principal or a bind.
      * Only collections may be CalDAV calendars or addressbooks, and they may not be both.
      */
      $resourcetypes = $setting->GetPath('DAV::resourcetype/*');
      $setcollection = false;
      $setcalendar = false;
      $setaddressbook = false;
      $setother = false;
      foreach( $resourcetypes AS $xnode ) {
        switch( $xnode->GetNSTag() ) {
          case 'urn:ietf:params:xml:ns:caldav:calendar':      $setcalendar = true;      break;
          case 'urn:ietf:params:xml:ns:carddav:addressbook':  $setaddressbook = true;   break;
          case 'DAV::collection': $setcollection = true; break;
          default:
            $setother = true;
        }
      }
      if ( $dav_resource->IsCollection() && $setcollection && ! $dav_resource->IsPrincipal() && ! $dav_resource->IsBinding()
          && !($setcalendar && $setaddressbook) && !$setother ) {
        $resourcetypes = '<collection xmlns="DAV:"/>';
        if ( $setcalendar ) $resourcetypes .= '<calendar xmlns="urn:ietf:params:xml:ns:caldav"/>';
        else if ( $setaddressbook ) $resourcetypes .= '<addressbook xmlns="urn:ietf:params:xml:ns:carddav"/>';
        $qry->QDo('UPDATE collection SET is_calendar = :is_calendar::boolean, is_addressbook = :is_addressbook::boolean,
                     resourcetypes = :resourcetypes WHERE dav_name = :dav_name',
                    array( ':dav_name' => $dav_resource->dav_name(), ':resourcetypes' => $resourcetypes,
                           ':is_calendar' => $setcalendar, ':is_addressbook' => $setaddressbook ) );
        $success[$tag] = 1;
      }
      else if ( $setcalendar && $setaddressbook ) {
        add_failure('set', $tag, 'HTTP/1.1 409 Conflict',
            translate("A collection may not be both a calendar and an addressbook."));
      }
      else if ( $setother ) {
        add_failure('set', $tag, 'HTTP/1.1 403 Forbidden',
             translate("Unsupported resourcetype modification."), 'cannot-modify-protected-property');
      }
      else {
        add_failure('set', $tag, 'HTTP/1.1 403 Forbidden',
             translate("Resources may not be changed to / from collections."), 'cannot-modify-protected-property');
      }
      break;

    case 'urn:ietf:params:xml:ns:caldav:schedule-calendar-transp':
      if ( $dav_resource->IsCollection() && ( $dav_resource->IsCalendar() || $setcalendar ) && !$dav_resource->IsBinding() ) {
        $transparency = $setting->GetPath('urn:ietf:params:xml:ns:caldav:schedule-calendar-transp/*');
        $transparency = preg_replace( '{^.*:}', '', $transparency[0]->GetNSTag());
        $qry->QDo('UPDATE collection SET schedule_transp = :transparency WHERE dav_name = :dav_name',
                    array( ':dav_name' => $dav_resource->dav_name(), ':transparency' => $transparency ) );
        $success[$tag] = 1;
      }
      else {
        add_failure('set', $tag, 'HTTP/1.1 409 Conflict',
              translate("The CalDAV:schedule-calendar-transp property may only be set on calendars."));
      }
      break;

    case 'urn:ietf:params:xml:ns:caldav:calendar-free-busy-set':
      add_failure('set', $tag, 'HTTP/1.1 409 Conflict',
            translate("The calendar-free-busy-set is superseded by the  schedule-calendar-transp property of a calendar collection.") );
      break;

    case 'urn:ietf:params:xml:ns:caldav:calendar-timezone':
      if ( $dav_resource->IsCollection() && $dav_resource->IsCalendar() && ! $dav_resource->IsBinding() ) {
        $tzcomponent = $setting->GetPath('urn:ietf:params:xml:ns:caldav:calendar-timezone');
        $tzstring = $tzcomponent[0]->GetContent();
        $calendar = new vCalendar( $tzstring );
        $timezones = $calendar->GetComponents('VTIMEZONE');
        if ( count($timezones) == 0 ) break;
        $tz = $timezones[0];  // Backward compatibility
        $tzid = $tz->GetPValue('TZID');
        $params = array( ':tzid' => $tzid );
        $qry = new AwlQuery('SELECT 1 FROM timezones WHERE tzid = :tzid', $params );
        if ( $qry->Exec('PUT',__LINE__,__FILE__) && $qry->rows() == 0 ) {
          $params[':olson_name'] = $calendar->GetOlsonName($tz);
          $params[':vtimezone'] = (isset($tz) ? $tz->Render() : null );
          $qry->QDo('INSERT INTO timezones (tzid, olson_name, active, vtimezone) VALUES(:tzid,:olson_name,false,:vtimezone)', $params );
        }

        $qry->QDo('UPDATE collection SET timezone = :tzid WHERE dav_name = :dav_name',
                                       array( ':tzid' => $tzid, ':dav_name' => $dav_resource->dav_name()) );
      }
      else {
        add_failure('set', $tag, 'HTTP/1.1 409 Conflict', translate("calendar-timezone property is only valid for a calendar."));
      }
      break;

    /**
    * The following properties are read-only, so they will cause the request to fail
    */
    case 'http://calendarserver.org/ns/:getctag':
    case 'DAV::owner':
    case 'DAV::principal-collection-set':
    case 'urn:ietf:params:xml:ns:caldav:calendar-user-address-set':
    case 'urn:ietf:params:xml:ns:caldav:schedule-inbox-URL':
    case 'urn:ietf:params:xml:ns:caldav:schedule-outbox-URL':
    case 'DAV::getetag':
    case 'DAV::getcontentlength':
    case 'DAV::getcontenttype':
    case 'DAV::getlastmodified':
    case 'DAV::creationdate':
    case 'DAV::lockdiscovery':
    case 'DAV::supportedlock':
      add_failure('set', $tag, 'HTTP/1.1 409 Conflict', translate("Property is read-only"), new XMLElement( 'cannot-modify-protected-property'));
      break;

    /**
    * If we don't have any special processing for the property, we just store it verbatim (which will be an XML fragment).
    */
    default:
      $qry->QDo('SELECT set_dav_property( :dav_name, :user_no::integer, :tag::text, :value::text)',
            array( ':dav_name' => $dav_resource->dav_name(), ':user_no' => $request->user_no, ':tag' => $tag, ':value' => $content) );
      $success[$tag] = 1;
      break;
  }
}

foreach( $rmprops AS $k => $setting ) {
  $tag = $setting->GetNSTag();
  $content = $setting->RenderContent();

  switch( $tag ) {

    case 'DAV::resourcetype':
      add_failure('rm', $tag, 'HTTP/1.1 409 Conflict',
            translate("DAV::resourcetype may only be set to a new value, it may not be removed."), 'cannot-modify-protected-property');
      break;

    case 'urn:ietf:params:xml:ns:caldav:calendar-timezone':
      if ( $dav_resource->IsCollection() && $dav_resource->IsCalendar() && ! $dav_resource->IsBinding() ) {
        $qry->QDo('UPDATE collection SET timezone = NULL WHERE dav_name = :dav_name', array( ':dav_name' => $dav_resource->dav_name()) );
      }
      else {
        add_failure('rm', $tag, 'HTTP/1.1 409 Conflict',
              translate("calendar-timezone property is only valid for a calendar."), 'cannot-modify-protected-property');
      }
      break;

    /**
    * The following properties are read-only, so they will cause the request to fail
    */
    case 'http://calendarserver.org/ns/:getctag':
    case 'DAV::owner':
    case 'DAV::principal-collection-set':
    case 'urn:ietf:params:xml:ns:caldav:CALENDAR-USER-ADDRESS-SET':
    case 'urn:ietf:params:xml:ns:caldav:schedule-inbox-URL':
    case 'urn:ietf:params:xml:ns:caldav:schedule-outbox-URL':
    case 'DAV::getetag':
    case 'DAV::getcontentlength':
    case 'DAV::getcontenttype':
    case 'DAV::getlastmodified':
    case 'DAV::creationdate':
    case 'DAV::displayname':
    case 'DAV::lockdiscovery':
    case 'DAV::supportedlock':
      add_failure('rm', $tag, 'HTTP/1.1 409 Conflict', translate("Property is read-only"));
      dbg_error_log( 'PROPPATCH', ' RMProperty %s is read only and cannot be removed', $tag);
      break;

    /**
    * If we don't have any special processing then we must have to just delete it.  Nonexistence is not failure.
    */
    default:
      $qry->QDo('DELETE FROM property WHERE dav_name=:dav_name AND property_name=:property_name',
                  array( ':dav_name' => $dav_resource->dav_name(), ':property_name' => $tag) );
      $success[$tag] = 1;
      break;
  }
}


/**
* If we have encountered any instances of failure, the whole damn thing fails.
*/
if ( count($failure) > 0 ) {

  $qry->Rollback();

  $url = ConstructURL($request->path);
  $multistatus = new XMLElement('multistatus');
  array_unshift($failure,new XMLElement('responsedescription', translate("Some properties were not able to be changed.") ));
  array_unshift($failure,new XMLElement('href', $url));
  $response = $reply->DAVElement($multistatus,'response', $failure);

  if ( !empty($success) ) {
    $prop = new XMLElement('prop');
    foreach( $success AS $tag => $v ) {
      $reply->NSElement($prop, $tag);
    }
    $reply->DAVElement($response, 'propstat', array( $prop, new XMLElement( 'status', 'HTTP/1.1 424 Failed Dependency' )) );
  }
  $request->DoResponse( 207, $reply->Render($multistatus), 'text/xml; charset="utf-8"' );

}

/**
* Otherwise we will try and do the SQL. This is inside a transaction, so PostgreSQL guarantees the atomicity
*/
if ( $qry->Commit() ) {

  $cache = getCacheInstance();
  $cache_ns = null;
  if ( $dav_resource->IsPrincipal() ) {
    $cache_ns = 'principal-'.$dav_resource->dav_name();
  }
  else if ( $dav_resource->IsCollection() ) {
    // Uncache anything to do with the collection
    $cache_ns = 'collection-'.$dav_resource->dav_name();
  }

  if ( isset($cache_ns) ) $cache->delete( $cache_ns, null );

  if ( $request->PreferMinimal() ) {
    $request->DoResponse(200); // Does not return.
  }

  $url = ConstructURL($request->path);
  $multistatus = new XMLElement('multistatus');
  $response = $multistatus->NewElement('response');
  $reply->DAVElement($response,'href', $url);
  $reply->DAVElement($response,'responsedescription', translate("All requested changes were made.") );

  $prop = new XMLElement('prop');
  foreach( $success AS $tag => $v ) {
    $reply->NSElement($prop, $tag);
  }
  $reply->DAVElement($response, 'propstat', array( $prop, new XMLElement( 'status', 'HTTP/1.1 200 OK' )) );

  $url = ConstructURL($request->path);
  array_unshift( $failure, new XMLElement('href', $url ) );

  $request->DoResponse( 207, $reply->Render($multistatus), 'text/xml; charset="utf-8"' );
}

/**
* Or it was all crap.
*/
$request->DoResponse( 500 );
exit(0); // unneccessary

