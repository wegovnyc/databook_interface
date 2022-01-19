<?php
/**
* An object representing a DAV 'Principal'
*
* @package   davical
* @subpackage   Principal
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst .Net Ltd, Morphoss Ltd <http://www.morhposs.com/>
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

require_once('Principal.php');

/**
* A class for things to do with a DAV Principal
*
* @package   davical
*/
class DAVPrincipal extends Principal
{

  /**
  * @var RFC4791: Identifies the URL(s) of any WebDAV collections that contain
  * calendar collections owned by the associated principal resource.
  */
  private $calendar_home_set;

  /**
  * @var CardDAV: Identifies the URL(s) of any WebDAV collections that contain
  * addressbook collections owned by the associated principal resource.
  */
  private $addressbook_home_set;

  /**
  * @var Obsolete: Identifies the URL(s) of any calendars participating in free/busy
  */
  private $calendar_free_busy_set;

  /**
  * @var RFC3744: Whether this is a group principal.
  */
  protected $_is_group;

  /**
  * @var RFC3744: The principals that are direct members of this group.
  */
  private $group_member_set;

  /**
  * @var RFC3744: The groups in which the principal is directly a member.
  */
  private $group_membership;

  /**
   * @var caldav-cu-proxy-02: The principals which this one has read permissions on.
   */
  private $read_proxy_for;

  /**
   * @var caldav-cu-proxy-02: The principals which this one has read-write prmissions for.
   */
  private $write_proxy_for;

   /**
   * @var caldav-cu-proxy-02: The principals which have read permissions on this one.
   */
  private $read_proxy_group;

  /**
   * @var caldav-cu-proxy-02: The principals which have write permissions on this one.
   */
  private $write_proxy_group;

  /**
   * @var CardDAV: The URL to an addressbook entry for this principal
   */
  private $principal_address;

  /**
   * A unique tag which will change if this principal changes
   * @var string
   */
  private $unique_tag;

  /**
  * Constructor
  * @param mixed $parameters If null, an empty Principal is created.  If it
  *              is an integer then that ID is read (if possible).  If it is
  *              an array then the Principal matching the supplied elements
  *              is read.  If it is an object then it is expected to be a 'usr'
  *              record that was read elsewhere.
  *
  * @return boolean Whether we actually read data from the DB to initialise the record.
  */
  function __construct( $parameters = null ) {
    global $session, $c;

    $this->exists = null;

    if ( $parameters == null ) return;

    if ( is_object($parameters) ) {
      dbg_error_log( 'principal', 'Principal: record for %s', $parameters->username );
      parent::__construct('username',$parameters->username);
    }
    else if ( is_int($parameters) ) {
      dbg_error_log( 'principal', 'Principal: %d', $parameters );
      parent::__construct('principal_id',$parameters);
    }
    else if ( is_array($parameters) ) {
      if ( ! isset($parameters['options']['allow_by_email']) ) $parameters['options']['allow_by_email'] = false;
      if ( isset($parameters['username']) ) {
        parent::__construct('username',$parameters['username']);
      }
      else if ( isset($parameters['user_no']) ) {
        parent::__construct('user_no',$parameters['user_no']);
      }
      else if ( isset($parameters['principal_id']) ) {
        parent::__construct('principal_id',$parameters['principal_id']);
      }
      else if ( isset($parameters['email']) ) {
        parent::__construct('email',$parameters['email']);
      }
      else if ( isset($parameters['path']) ) {
        parent::__construct('path',$parameters['path']);
      }
      else if ( isset($parameters['principal-property-search']) ) {
        $username = $this->PropertySearch($parameters['principal-property-search']);
        parent::__construct('username',$username);
      }
    }

    if ( ! $this->exists ) return;

    $this->InitialiseRecord();

  }


  /**
  * Initialise the Principal object from a $usr record from the DB.
  * @param object $usr The usr record from the DB.
  */
  function InitialiseRecord() {
    global $c;

    $this->unique_tag = '"'.md5($this->username . $this->modified).'"';
    $this->_is_group = (isset($this->type_id) && $this->type_id == 3);

    $this->principal_address = $this->url . 'principal.vcf';

    $this->user_address_set = array(
       'mailto:'.$this->email,
       $this->url,
//       ConstructURL( '/~'.$this->username.'/', true ),
//       ConstructURL( '/__uuids__/'.$this->username.'/', true ),
    );

    if ( isset ( $c->notifications_server ) ) {
      $this->xmpp_uri = 'xmpp:pubsub.'.$c->notifications_server['host'].'?pubsub;node=/davical-'.$this->principal_id;
      $this->xmpp_server = $c->notifications_server['host'];
    }

    if ( $this->_is_group ) {
      $this->group_member_set = array();
      $qry = new AwlQuery('SELECT usr.username FROM group_member JOIN principal ON (principal_id=member_id) JOIN usr USING(user_no) WHERE usr.active=true AND group_id = :group_id ORDER BY principal.principal_id ', array( ':group_id' => $this->principal_id) );
      if ( $qry->Exec('DAVPrincipal') && $qry->rows() > 0 ) {
        while( $member = $qry->Fetch() ) {
          $this->group_member_set[] = ConstructURL( '/'. $member->username . '/', true);
        }
      }
    }

    $this->group_membership = array();
    $qry = new AwlQuery('SELECT usr.username FROM group_member JOIN principal ON (principal_id=group_id) JOIN usr USING(user_no) WHERE usr.active=true AND member_id = :member_id UNION SELECT usr.username FROM group_member LEFT JOIN grants ON (to_principal=group_id) JOIN principal ON (principal_id=by_principal) JOIN usr USING(user_no) WHERE usr.active=true AND member_id = :member_id and by_principal != member_id ORDER BY 1', array( ':member_id' => $this->principal_id ) );
    if ( $qry->Exec('DAVPrincipal') && $qry->rows() > 0 ) {
      while( $group = $qry->Fetch() ) {
        $this->group_membership[] = ConstructURL( '/'. $group->username . '/', true);
      }
    }

    $this->read_proxy_group = null;
    $this->write_proxy_group = null;
    $this->write_proxy_for = null;
    $this->read_proxy_for = null;

    dbg_error_log( 'principal', ' User: %s (%d) URL: %s, By Email: %d', $this->username, $this->user_no, $this->url, $this->by_email );
  }


  /**
  * Split this out so we do it as infrequently as possible, given the cost.
  */
  function FetchProxyGroups() {
    global $c;

    $this->read_proxy_group = array();
    $this->write_proxy_group = array();
    $this->write_proxy_for = array();
    $this->read_proxy_for = array();

    if ( isset($c->disable_caldav_proxy) && $c->disable_caldav_proxy ) return;

    $write_priv = privilege_to_bits(array('write'));
    // whom are we a proxy for? who is a proxy for us?
    // (as per Caldav Proxy section 5.1 Paragraph 7 and 5)
    $sql = 'SELECT principal_id, username, pprivs(:request_principal::int8,principal_id,:scan_depth::int) FROM principal JOIN usr USING(user_no) WHERE usr.active=true AND principal_id IN (SELECT * from p_has_proxy_access_to(:request_principal,:scan_depth))';
    $params = array( ':request_principal' => $this->principal_id, ':scan_depth' => $c->permission_scan_depth );
    $qry = new AwlQuery($sql, $params);
    if ( $qry->Exec('DAVPrincipal') && $qry->rows() > 0 ) {
      while( $relationship = $qry->Fetch() ) {
        if ( (bindec($relationship->pprivs) & $write_priv) != 0 ) {
          $this->write_proxy_for[] = ConstructURL( '/'. $relationship->username . '/', true);
          $this->group_membership[] = ConstructURL( '/'. $relationship->username . '/calendar-proxy-write/', true);
        }
        else {
          $this->read_proxy_for[] = ConstructURL( '/'. $relationship->username . '/', true);
          $this->group_membership[] = ConstructURL( '/'. $relationship->username . '/calendar-proxy-read/', true);
        }
      }
    }

    $sql = 'SELECT principal_id, username, pprivs(:request_principal::int8,principal_id,:scan_depth::int) FROM principal JOIN usr USING(user_no) WHERE usr.active=true AND principal_id IN (SELECT * from grants_proxy_access_from_p(:request_principal,:scan_depth))';
    $qry = new AwlQuery($sql, $params ); // reuse $params assigned for earlier query
    if ( $qry->Exec('DAVPrincipal') && $qry->rows() > 0 ) {
      while( $relationship = $qry->Fetch() ) {
        if ( bindec($relationship->pprivs) & $write_priv ) {
          $this->write_proxy_group[] = ConstructURL( '/'. $relationship->username . '/', true);
        }
        else {
          $this->read_proxy_group[] = ConstructURL( '/'. $relationship->username . '/', true);
        }
      }
    }
      dbg_error_log( 'principal', 'Read-proxy-for:    %s', implode(',',$this->read_proxy_for) );
      dbg_error_log( 'principal', 'Write-proxy-for:   %s', implode(',',$this->write_proxy_for) );
      dbg_error_log( 'principal', 'Read-proxy-group:  %s', implode(',',$this->read_proxy_group) );
      dbg_error_log( 'principal', 'Write-proxy-group: %s', implode(',',$this->write_proxy_group) );
  }


  /**
  * Accessor for the read proxy group
  */
  function ReadProxyGroup() {
    if ( !isset($this->read_proxy_group) ) $this->FetchProxyGroups();
    return $this->read_proxy_group;
  }


  /**
  * Accessor for the write proxy group
  */
  function WriteProxyGroup() {
    if ( !isset($this->write_proxy_group) ) $this->FetchProxyGroups();
    return $this->write_proxy_group;
  }


  /**
  * Accessor for read or write proxy
  * @param string read/write - which sort of proxy list is requested.
  */
  function ProxyFor( $type ) {
    if ( !isset($this->read_proxy_for) ) $this->FetchProxyGroups();
    if ( $type == 'write' ) return $this->write_proxy_for;
    return $this->read_proxy_for;
  }


  /**
  * Accessor for the group membership - the groups this principal is a member of
  */
  function GroupMembership() {
    if ( !isset($this->read_proxy_group) ) $this->FetchProxyGroups();
    return $this->group_membership;
  }


  /**
  * Accessor for the group member set - the members of this group
  */
  function GroupMemberSet() {
    if ( ! $this->_is_group ) return null;
    return $this->group_member_set;
  }


  /**
  * Is this a group principal?
  * @return boolean Whether this is a group principal
  */
  function IsGroup() {
    return $this->_is_group;
  }


  /**
  * Return an arbitrary property
  * @return string The name of the arbitrary property
  */
  function GetProperty( $property_id ) {

    switch( $property_id ) {
      case 'DAV::resource-id':
        if ( $this->exists && $this->principal_id > 0 )
          ConstructURL('/.resources/'.$this->principal_id);
        else
          return null;
        break;
    }

    if ( isset($this->{$property_id}) ) {
      if ( ! is_object($this->{$property_id}) ) return $this->{$property_id};
      return clone($this->{$property_id});
    }
    return null;
  }

  /**
  * Returns the unique_tag (ETag or getctag) for this resource
  */
  public function unique_tag() {
    if ( isset($this->unique_tag) ) return $this->unique_tag;

    if ( $this->exists !== true ) $this->unique_tag = '"-1"';

    return $this->unique_tag;
  }


  /**
  * Get the calendar_home_set, as lazily as possible
  */
  function calendar_home_set() {
    if ( !isset($this->calendar_home_set) ) {
      $this->calendar_home_set = array();
      $qry = new AwlQuery('SELECT DISTINCT parent_container FROM collection WHERE is_calendar AND dav_name ~ :dav_name_start',
                             array( ':dav_name_start' => '^'.$this->dav_name));
      if ( $qry->Exec('principal',__LINE__,__FILE__) ) {
        if ( $qry->rows() > 0 ) {
          while( $calendar = $qry->Fetch() ) {
            $this->calendar_home_set[] = ConstructURL($calendar->parent_container, true);
          }
        }
        else {
          $this->calendar_home_set[] = $this->url;
        }
      }
    }
    return $this->calendar_home_set;
  }


  /**
  * Get the addressbook_home_set, as lazily as possible
  */
  function addressbook_home_set() {
    if ( !isset($this->addressbook_home_set) ) {
      $this->addressbook_home_set = array();
      $qry = new AwlQuery('SELECT DISTINCT parent_container FROM collection WHERE is_addressbook AND dav_name ~ :dav_name_start',
                             array( ':dav_name_start' => '^'.$this->dav_name));
      if ( $qry->Exec('principal',__LINE__,__FILE__) ) {
        if ( $qry->rows() > 0 ) {
          while( $addressbook = $qry->Fetch() ) {
            $this->addressbook_home_set[] = ConstructURL($addressbook->parent_container, true);
          }
        }
        else {
          $this->addressbook_home_set[] = $this->url;
        }
      }
    }
    return $this->addressbook_home_set;
  }


  /**
  * The property calendar-free-busy-set has been dropped from draft 5 of the scheduling extensions for CalDAV,
  * and is not present in the final official RFC (6638).
  * Hence, by default we do not call this method and do not populate the property.
  * Enable the config value $c->support_obsolete_free_busy_property if you really need it, however be aware that
  * performance may be adversely affected if you do so, since the resulting query over the collection table is slow.
  */
  function calendar_free_busy_set() {
    if (!isset($this->calendar_free_busy_set)) {
      $this->calendar_free_busy_set = array();
      $qry = new AwlQuery('SELECT dav_name FROM collection WHERE is_calendar AND (schedule_transp = \'opaque\' OR schedule_transp IS NULL) AND dav_name ~ :dav_name_start ORDER BY user_no, collection_id',
                      array(':dav_name_start' => '^' . $this->dav_name));
      if ($qry->Exec('principal', __LINE__, __FILE__)) {
        while ($calendar = $qry->Fetch()) {
          $this->calendar_free_busy_set[] = ConstructURL($calendar->dav_name, true);
        }
      }
    }
    return $this->calendar_free_busy_set;
  }


  /**
  * Return the privileges bits for the current session user to this resource
  */
  function Privileges() {
    global $session;
    if ( !isset($this->privileges) ) $this->privileges = 0;
    if ( is_string($this->privileges) ) $this->privileges = bindec( $this->privileges );
    if ( $this->_is_group ) {
      if ( isset($session->principal) && in_array($session->principal->url(), $this->GroupMemberSet()) ) {
        $this->privileges |=  privilege_to_bits( array('DAV::read', 'DAV::read-current-user-privilege-set') );
      }
    }
    return $this->privileges;
  }


  /**
  * Returns a representation of the principal as a collection
  */
  function AsCollection() {
    $dav_name = (isset($this->original_request_url) ? DeconstructURL($this->original_request_url) : $this->dav_name());
    $collection = (object) array(
                            'collection_id' => ($this->principal_id() ? $this->principal_id() : 0),
                            'is_calendar' => false,
                            'is_addressbook' => false,
                            'is_principal' => true,
                            'type'     => 'principal' . (isset($this->original_request_url) ? '_link' : ''),
                            'user_no'  => ($this->user_no() ? $this->user_no() : 0),
                            'username' => $this->username(),
                            'dav_name' => $dav_name,
                            'parent_container' => '/',
                            'email'    => ($this->email()? $this->email() : ''),
                            'created'  => $this->created,
                            'updated'  => $this->modified,
                            'dav_etag' => substr($this->unique_tag(),1,-1),
                            'resourcetypes' => $this->resourcetypes
                  );
    $collection->dav_displayname =  (isset($this->dav_displayname) ? $this->dav_displayname : (isset($this->fullname) ? $this->fullname : $collection->username));

    return $collection;
  }


  function PropertySearch( $parameters ) {
    throw new Exception("Unimplemented!");
  }

  /**
  * Returns properties which are specific to this principal
  */
  function PrincipalProperty( $tag, $prop, &$reply, &$denied ) {
    global $c, $request;

    dbg_error_log('principal',':PrincipalProperty: Principal Property "%s"', $tag );
    switch( $tag ) {
      case 'DAV::getcontenttype':
        $reply->DAVElement( $prop, 'getcontenttype', 'httpd/unix-directory' );
        break;

      case 'DAV::resourcetype':
        $reply->DAVElement( $prop, 'resourcetype', array( new XMLElement('principal'), new XMLElement('collection')) );
        break;

      case 'DAV::displayname':
        $reply->DAVElement( $prop, 'displayname', $this->fullname );
        break;

      case 'DAV::principal-URL':
        $reply->DAVElement( $prop, 'principal-URL', $reply->href($this->url()) );
        break;

      case 'DAV::getlastmodified':
        $reply->DAVElement( $prop, 'getlastmodified', ISODateToHTTPDate($this->modified) );
        break;

      case 'DAV::creationdate':
        $reply->DAVElement( $prop, 'creationdate', DateToISODate($this->created) );
        break;

      case 'DAV::getcontentlanguage':
        /** Use the principal's locale by preference, otherwise system default */
        $locale = (isset($c->current_locale) ? $c->current_locale : '');
        if ( isset($this->locale) && $this->locale != '' ) $locale = $this->locale;
        $reply->DAVElement( $prop, 'getcontentlanguage', $locale );
        break;

      case 'http://calendarserver.org/ns/:group-member-set':
      case 'DAV::group-member-set':
        if ( $request->IsProxyRequest() ) {
          /** calendar-proxy-{read,write} pseudo-principal, see caldav-proxy 3.2 */
          if ($request->proxy_type == 'read') {
            $reply->DAVElement( $prop, 'group-member-set', $reply->href($this->ReadProxyGroup()) );
          } else {
            $reply->DAVElement( $prop, 'group-member-set', $reply->href($this->WriteProxyGroup()) );
          }
        } else {
          /** regular group principal */
          if ( ! $this->_is_group ) return false;
          $reply->DAVElement( $prop, 'group-member-set', $reply->href($this->group_member_set) );
        }
        break;

      case 'http://calendarserver.org/ns/:group-membership':
      case 'DAV::group-membership':
        $reply->DAVElement( $prop, 'group-membership', $reply->href($this->GroupMembership()) );
        break;

      case 'urn:ietf:params:xml:ns:caldav:schedule-inbox-URL':
        $reply->CalDAVElement($prop, 'schedule-inbox-URL', $reply->href($this->url('schedule-inbox')) );
        break;

      case 'urn:ietf:params:xml:ns:caldav:schedule-outbox-URL':
        $reply->CalDAVElement($prop, 'schedule-outbox-URL', $reply->href($this->url('schedule-outbox')) );
        break;

      case 'urn:ietf:params:xml:ns:caldav:schedule-default-calendar-URL':
        $reply->CalDAVElement($prop, 'schedule-default-calendar-URL', $reply->href($this->url('schedule-default-calendar')) );
        break;

      case 'http://calendarserver.org/ns/:dropbox-home-URL':
        $reply->CalendarserverElement($prop, 'dropbox-home-URL', $reply->href($this->url('dropbox')) );
        break;

      case 'http://calendarserver.org/ns/:xmpp-server':
        if ( ! isset( $this->xmpp_uri ) ) return false;
        $reply->CalendarserverElement($prop, 'xmpp-server', $this->xmpp_server );
        break;

      case 'http://calendarserver.org/ns/:xmpp-uri':
        if ( ! isset( $this->xmpp_uri ) ) return false;
        $reply->CalendarserverElement($prop, 'xmpp-uri', $this->xmpp_uri );
        break;

      case 'urn:ietf:params:xml:ns:carddav:addressbook-home-set':
        $reply->CardDAVElement($prop, $tag, $reply->href( $this->addressbook_home_set() ) );
        break;

      case 'urn:ietf:params:xml:ns:caldav:calendar-home-set':
        $reply->CalDAVElement($prop, $tag, $reply->href( $this->calendar_home_set() ) );
        break;

      case 'urn:ietf:params:xml:ns:caldav:calendar-free-busy-set':
        /** Note that this property was dropped from the scheduling extensions for CalDAV.
        * We only populate it if the config value support_obsolete_free_busy_property is set.
        * This should not be enabled unless your CalDAV client requires the property; beware
        * that doing so may also adversely affect PROPFIND performance.
        */
        if ( isset($c->support_obsolete_free_busy_property) && $c->support_obsolete_free_busy_property )
          $reply->CalDAVElement( $prop, 'calendar-free-busy-set', $reply->href( $this->calendar_free_busy_set() ) );
        else
          return false;
        break;

      case 'urn:ietf:params:xml:ns:caldav:calendar-user-address-set':
        $reply->CalDAVElement($prop, 'calendar-user-address-set', $reply->href($this->user_address_set));
        break;

      case 'DAV::owner':
        // After a careful reading of RFC3744 we see that this must be the principal-URL of the owner
        $reply->DAVElement( $prop, 'owner', $reply->href( $this->url ) );
        break;

      // Empty tag responses.
      case 'DAV::alternate-URI-set':
        $reply->DAVElement( $prop, $reply->Tag($tag));
        break;

      case 'SOME-DENIED-PROPERTY':  /** @todo indicating the style for future expansion */
        $denied[] = $reply->Tag($tag);
        break;

      default:
        return false;
        break;
    }

    return true;
  }


  /**
  * Render XML for a single Principal (user) from the DB
  *
  * @param array $properties The requested properties for this principal
  * @param reference $reply A reference to the XMLDocument being used for the reply
  * @param boolean $props_only Default false.  If true will only return the fragment with the properties, not a full response fragment.
  *
  * @return string An XML fragment with the requested properties for this principal
  */
  function RenderAsXML( $properties, &$reply, $props_only = false ) {
    dbg_error_log('principal',':RenderAsXML: Principal "%s"', $this->username );

    $prop = new XMLElement('prop');
    $denied = array();
    $not_found = array();
    foreach( $properties AS $k => $tag ) {
      if ( ! $this->PrincipalProperty( $tag, $prop, $reply, $denied ) ) {
        dbg_error_log( 'principal', 'Request for unsupported property "%s" of principal "%s".', $tag, $this->username );
        $not_found[] = $reply->Tag($tag);
      }
    }

    if ( $props_only ) return $prop;

    $status = new XMLElement('status', 'HTTP/1.1 200 OK' );

    $propstat = new XMLElement( 'propstat', array( $prop, $status) );
    $href = $reply->href($this->url );

    $elements = array($href,$propstat);

    if ( count($denied) > 0 ) {
      $status = new XMLElement('status', 'HTTP/1.1 403 Forbidden' );
      $noprop = new XMLElement('prop');
      foreach( $denied AS $k => $v ) {
        $noprop->NewElement( $v );
      }
      $elements[] = new XMLElement( 'propstat', array( $noprop, $status) );
    }

    if ( count($not_found) > 0 ) {
      $status = new XMLElement('status', 'HTTP/1.1 404 Not Found' );
      $noprop = new XMLElement('prop');
      foreach( $not_found AS $k => $v ) {
        $noprop->NewElement( $v );
      }
      $elements[] = new XMLElement( 'propstat', array( $noprop, $status) );
    }

    $response = new XMLElement( 'response', $elements );

    return $response;
  }

}
