<?php
/**
* Functions that are needed for all CalDAV Requests
*
*  - Ascertaining the paths
*  - Ascertaining the current user's permission to those paths.
*  - Utility functions which we can use to decide whether this
*    is a permitted activity for this user.
*
* @package   davical
* @subpackage   Request
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst .Net Ltd, Morphoss Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once("AwlCache.php");
require_once("XMLDocument.php");
require_once("DAVPrincipal.php");
require_once("DAVTicket.php");

define('DEPTH_INFINITY', 9999);


/**
* A class for collecting things to do with this request.
*
* @package   davical
*/
class CalDAVRequest
{
  var $options;

  /**
  * The raw data sent along with the request
  */
  var $raw_post;

  /**
  * The HTTP request method: PROPFIND, LOCK, REPORT, OPTIONS, etc...
  */
  var $method;

  /**
  * The depth parameter from the request headers, coerced into a valid integer: 0, 1
  * or DEPTH_INFINITY which is defined above.  The default is set per various RFCs.
  */
  var $depth;

  /**
  * The 'principal' (user/resource/...) which this request seeks to access
  * @var DAVPrincipal
  */
  var $principal;

  /**
  * The 'current_user_principal_xml' the DAV:current-user-principal answer. An
  * XMLElement object with an <href> or <unauthenticated> fragment.
  */
  var $current_user_principal_xml;

  /**
  * The user agent making the request.
  */
  var $user_agent;

  /**
  * The ID of the collection containing this path, or of this path if it is a collection
  */
  var $collection_id;

  /**
  * The path corresponding to the collection_id
  */
  var $collection_path;

  /**
  * The type of collection being requested:
  *  calendar, schedule-inbox, schedule-outbox
  */
  var $collection_type;

  /**
  * The type of collection being requested:
  *  calendar, schedule-inbox, schedule-outbox
  */
  protected $exists;

  /**
  * The value of any 'Destionation:' header, if present.
  */
  var $destination;

  /**
  * The decimal privileges allowed by this user to the identified resource.
  */
  protected $privileges;

  /**
  * A static structure of supported privileges.
  */
  var $supported_privileges;

  /**
  * A DAVTicket object, if there is a ?ticket=id or Ticket: id with this request
  */
  public $ticket;

  /**
   * An array of values from the 'Prefer' header.  At present only 'return=minimal' is acted on in any way - you
   * can test that value with the PreferMinimal() method.
   */
  private $prefer;

  /**
  * Create a new CalDAVRequest object.
  */
  function __construct( $options = array() ) {
    global $session, $c, $debugging;

    $this->options = $options;
    if ( !isset($this->options['allow_by_email']) ) $this->options['allow_by_email'] = false;

    if ( isset($_SERVER['HTTP_PREFER']) ) {
      $this->prefer = explode( ',', $_SERVER['HTTP_PREFER']);
    }
    else if ( isset($_SERVER['HTTP_BRIEF']) && (strtoupper($_SERVER['HTTP_BRIEF']) == 'T') ) {
      $this->prefer = array( 'return=minimal');
    }
    else
      $this->prefer = array();

    /**
    * Our path is /<script name>/<user name>/<user controlled> if it ends in
    * a trailing '/' then it is referring to a DAV 'collection' but otherwise
    * it is referring to a DAV data item.
    *
    * Permissions are controlled as follows:
    *  1. if there is no <user name> component, the request has read privileges
    *  2. if the requester is an admin, the request has read/write priviliges
    *  3. if there is a <user name> component which matches the logged on user
    *     then the request has read/write privileges
    *  4. otherwise we query the defined relationships between users and use
    *     the minimum privileges returned from that analysis.
    */
    if ( isset($_SERVER['PATH_INFO']) ) {
      $this->path = $_SERVER['PATH_INFO'];
    }
    else {
      $this->path = '/';
      if ( isset($_SERVER['REQUEST_URI']) ) {
        if ( preg_match( '{^(.*?\.php)([^?]*)}', $_SERVER['REQUEST_URI'], $matches ) ) {
          $this->path = $matches[2];
          if ( substr($this->path,0,1) != '/' )
            $this->path = '/'.$this->path;
        }
        else if ( $_SERVER['REQUEST_URI'] != '/' ) {
          dbg_error_log('LOG', 'Server is not supplying PATH_INFO and REQUEST_URI does not include a PHP program.  Wildly guessing "/"!!!');
        }
      }
    }
    $this->path = rawurldecode($this->path);

    /** Allow a request for .../calendar.ics to translate into the calendar URL */
    if ( preg_match( '#^(/[^/]+/[^/]+).ics$#', $this->path, $matches ) ) {
      $this->path = $matches[1]. '/';
    }

    if ( isset($c->replace_path) && isset($c->replace_path['from']) && isset($c->replace_path['to']) ) {
      $this->path = preg_replace($c->replace_path['from'], $c->replace_path['to'], $this->path);
    }

    // dbg_error_log( "caldav", "Sanitising path '%s'", $this->path );
    $bad_chars_regex = '/[\\^\\[\\(\\\\]/';
    if ( preg_match( $bad_chars_regex, $this->path ) ) {
      $this->DoResponse( 400, translate("The calendar path contains illegal characters.") );
    }
    if ( strstr($this->path,'//') ) $this->path = preg_replace( '#//+#', '/', $this->path);

    if ( !isset($c->raw_post) ) $c->raw_post = file_get_contents( 'php://input');
    if ( isset($_SERVER['HTTP_CONTENT_ENCODING']) ) {
      $encoding = $_SERVER['HTTP_CONTENT_ENCODING'];
      @dbg_error_log('caldav', 'Content-Encoding: %s', $encoding );
      $encoding = preg_replace('{[^a-z0-9-]}i','',$encoding);
      if ( ! ini_get('open_basedir') && (isset($c->dbg['ALL']) || isset($c->dbg['caldav'])) ) {
        $fh = fopen('/var/log/davical/encoded_data.debug'.$encoding,'w');
        if ( $fh ) {
          fwrite($fh,$c->raw_post);
          fclose($fh);
        }
      }
      switch( $encoding ) {
        case 'gzip':
          $this->raw_post = @gzdecode($c->raw_post);
          break;
        case 'deflate':
          $this->raw_post = @gzinflate($c->raw_post);
          break;
        case 'compress':
          $this->raw_post = @gzuncompress($c->raw_post);
          break;
        default:
      }
      if ( empty($this->raw_post) && !empty($c->raw_post) ) {
        $this->PreconditionFailed(415, 'content-encoding', sprintf('Unable to decode "%s" content encoding.', $_SERVER['HTTP_CONTENT_ENCODING']));
      }
      $c->raw_post = $this->raw_post;
    }
    else {
      $this->raw_post = $c->raw_post;
    }

    if ( isset($debugging) && isset($_GET['method']) ) {
      $_SERVER['REQUEST_METHOD'] = $_GET['method'];
    }
    else if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) ){
      $_SERVER['REQUEST_METHOD'] = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
    }
    $this->method = $_SERVER['REQUEST_METHOD'];
    $this->content_type = (isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : null);
    if ( preg_match( '{^(\S+/\S+)\s*(;.*)?$}', $this->content_type, $matches ) ) {
      $this->content_type = $matches[1];
    }
    if ( strlen($c->raw_post) > 0 ) {
      if ( $this->method == 'PROPFIND' || $this->method == 'REPORT' || $this->method == 'PROPPATCH' || $this->method == 'BIND' || $this->method == 'MKTICKET' || $this->method == 'ACL' ) {
        if ( !preg_match( '{^(text|application)/xml$}', $this->content_type ) ) {
          @dbg_error_log( "LOG request", 'Request is "%s" but client set content-type to "%s". Assuming they meant XML!',
                                                 $this->method, $this->content_type );
          $this->content_type = 'text/xml';
        }
      }
      else if ( $this->method == 'PUT' || $this->method == 'POST' ) {
        $this->CoerceContentType();
      }
    }
    else {
      $this->content_type = 'text/plain';
    }
    $this->user_agent = ((isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "Probably Mulberry"));

    /**
    * A variety of requests may set the "Depth" header to control recursion
    */
    if ( isset($_SERVER['HTTP_DEPTH']) ) {
      $this->depth = $_SERVER['HTTP_DEPTH'];
    }
    else {
      /**
      * Per rfc2518, section 9.2, 'Depth' might not always be present, and if it
      * is not present then a reasonable request-type-dependent default should be
      * chosen.
      */
      switch( $this->method ) {
        case 'DELETE':
        case 'MOVE':
        case 'COPY':
        case 'LOCK':
          $this->depth = 'infinity';
          break;

        case 'REPORT':
          $this->depth = 0;
          break;

        case 'PROPFIND':
        default:
          $this->depth = 0;
      }
    }
    if ( !is_int($this->depth) && "infinity" == $this->depth  ) $this->depth = DEPTH_INFINITY;
    $this->depth = intval($this->depth);

    /**
    * MOVE/COPY use a "Destination" header and (optionally) an "Overwrite" one.
    */
    if ( isset($_SERVER['HTTP_DESTINATION']) ) {
      $this->destination = $_SERVER['HTTP_DESTINATION'];
      if ( preg_match('{^(https?)://([a-z.-]+)(:[0-9]+)?(/.*)$}', $this->destination, $matches ) ) {
        $this->destination = $matches[4];
      }
    }
    $this->overwrite = ( isset($_SERVER['HTTP_OVERWRITE']) && ($_SERVER['HTTP_OVERWRITE'] == 'F') ? false : true ); // RFC4918, 9.8.4 says default True.

    /**
    * LOCK things use an "If" header to hold the lock in some cases, and "Lock-token" in others
    */
    if ( isset($_SERVER['HTTP_IF']) ) $this->if_clause = $_SERVER['HTTP_IF'];
    if ( isset($_SERVER['HTTP_LOCK_TOKEN']) && preg_match( '#[<]opaquelocktoken:(.*)[>]#', $_SERVER['HTTP_LOCK_TOKEN'], $matches ) ) {
      $this->lock_token = $matches[1];
    }

    /**
    * Check for an access ticket.
    */
    if ( isset($_GET['ticket']) ) {
      $this->ticket = new DAVTicket($_GET['ticket']);
    }
    else if ( isset($_SERVER['HTTP_TICKET']) ) {
      $this->ticket = new DAVTicket($_SERVER['HTTP_TICKET']);
    }

    /**
    * LOCK things use a "Timeout" header to set a series of reducing alternative values
    */
    if ( isset($_SERVER['HTTP_TIMEOUT']) ) {
      $timeouts = explode( ',', $_SERVER['HTTP_TIMEOUT'] );
      foreach( $timeouts AS $k => $v ) {
        if ( strtolower($v) == 'infinite' ) {
          $this->timeout = (isset($c->maximum_lock_timeout) ? $c->maximum_lock_timeout : 86400 * 100);
          break;
        }
        elseif ( strtolower(substr($v,0,7)) == 'second-' ) {
          $this->timeout = min( intval(substr($v,7)), (isset($c->maximum_lock_timeout) ? $c->maximum_lock_timeout : 86400 * 100) );
          break;
        }
      }
      if ( ! isset($this->timeout) || $this->timeout == 0 ) $this->timeout = (isset($c->default_lock_timeout) ? $c->default_lock_timeout : 900);
    }

    $this->principal = new Principal('path',$this->path);

    /**
    * RFC2518, 5.2: URL pointing to a collection SHOULD end in '/', and if it does not then
    * we SHOULD return a Content-location header with the correction...
    *
    * We therefore look for a collection which matches one of the following URLs:
    *  - The exact request.
    *  - If the exact request, doesn't end in '/', then the request URL with a '/' appended
    *  - The request URL truncated to the last '/'
    * The collection URL for this request is therefore the longest row in the result, so we
    * can "... ORDER BY LENGTH(dav_name) DESC LIMIT 1"
    */
    $sql = "SELECT * FROM collection WHERE dav_name = :exact_name";
    $params = array( ':exact_name' => $this->path );
    if ( !preg_match( '#/$#', $this->path ) ) {
      $sql .= " OR dav_name = :truncated_name OR dav_name = :trailing_slash_name";
      $params[':truncated_name'] = preg_replace( '#[^/]*$#', '', $this->path);
      $params[':trailing_slash_name'] = $this->path."/";
    }
    $sql .= " ORDER BY LENGTH(dav_name) DESC LIMIT 1";
    $qry = new AwlQuery( $sql, $params );
    if ( $qry->Exec('caldav',__LINE__,__FILE__) && $qry->rows() == 1 && ($row = $qry->Fetch()) ) {
      if ( $row->dav_name == $this->path."/" ) {
        $this->path = $row->dav_name;
        dbg_error_log( "caldav", "Path is actually a collection - sending Content-Location header." );
        header( "Content-Location: ".ConstructURL($this->path) );
      }

      $this->collection_id = $row->collection_id;
      $this->collection_path = $row->dav_name;
      $this->collection_type = ($row->is_calendar == 't' ? 'calendar' : 'collection');
      $this->collection = $row;
      if ( preg_match( '#^((/[^/]+/)\.(in|out)/)[^/]*$#', $this->path, $matches ) ) {
        $this->collection_type = 'schedule-'. $matches[3]. 'box';
      }
      $this->collection->type = $this->collection_type;
    }
    else if ( preg_match( '{^( ( / ([^/]+) / ) \.(in|out)/ ) [^/]*$}x', $this->path, $matches ) ) {
      // The request is for a scheduling inbox or outbox (or something inside one) and we should auto-create it
      $params = array( ':username' => $matches[3], ':parent_container' => $matches[2], ':dav_name' => $matches[1] );
      $params[':boxname'] = ($matches[4] == 'in' ? ' Inbox' : ' Outbox');
      $this->collection_type = 'schedule-'. $matches[4]. 'box';
      $params[':resourcetypes'] = sprintf('<DAV::collection/><urn:ietf:params:xml:ns:caldav:%s/>', $this->collection_type );
      $sql = <<<EOSQL
INSERT INTO collection ( user_no, parent_container, dav_name, dav_displayname, is_calendar, created, modified, dav_etag, resourcetypes )
    VALUES( (SELECT user_no FROM usr WHERE username = text(:username)),
            :parent_container, :dav_name,
            (SELECT fullname FROM usr WHERE username = text(:username)) || :boxname,
             FALSE, current_timestamp, current_timestamp, '1', :resourcetypes )
EOSQL;

      $qry = new AwlQuery( $sql, $params );
      $qry->Exec('caldav',__LINE__,__FILE__);
      dbg_error_log( 'caldav', 'Created new collection as "%s".', trim($params[':boxname']) );

      // Uncache anything to do with the collection
      $cache = getCacheInstance();
      $cache->delete( 'collection-'.$params[':dav_name'], null );
      $cache->delete( 'principal-'.$params[':parent_container'], null );

      $qry = new AwlQuery( "SELECT * FROM collection WHERE dav_name = :dav_name", array( ':dav_name' => $matches[1] ) );
      if ( $qry->Exec('caldav',__LINE__,__FILE__) && $qry->rows() == 1 && ($row = $qry->Fetch()) ) {
        $this->collection_id = $row->collection_id;
        $this->collection_path = $matches[1];
        $this->collection = $row;
        $this->collection->type = $this->collection_type;
      }
    }
    else if ( preg_match( '#^((/[^/]+/)calendar-proxy-(read|write))/?[^/]*$#', $this->path, $matches ) ) {
      $this->collection_type = 'proxy';
      $this->_is_proxy_request = true;
      $this->proxy_type = $matches[3];
      $this->collection_path = $matches[1].'/';  // Enforce trailling '/'
      if ( $this->collection_path == $this->path."/" ) {
        $this->path .= '/';
        dbg_error_log( "caldav", "Path is actually a (proxy) collection - sending Content-Location header." );
        header( "Content-Location: ".ConstructURL($this->path) );
      }
    }
    else if ( $this->options['allow_by_email'] && preg_match( '#^/(\S+@\S+[.]\S+)/?$#', $this->path) ) {
      /** @todo we should deprecate this now that Evolution 2.27 can do scheduling extensions */
      $this->collection_id = -1;
      $this->collection_type = 'email';
      $this->collection_path = $this->path;
      $this->_is_principal = true;
    }
    else if ( preg_match( '#^(/[^/?]+)/?$#', $this->path, $matches) || preg_match( '#^(/principals/[^/]+/[^/]+)/?$#', $this->path, $matches) ) {
      $this->collection_id = -1;
      $this->collection_path = $matches[1].'/';  // Enforce trailling '/'
      $this->collection_type = 'principal';
      $this->_is_principal = true;
      if ( $this->collection_path == $this->path."/" ) {
        $this->path .= '/';
        dbg_error_log( "caldav", "Path is actually a collection - sending Content-Location header." );
        header( "Content-Location: ".ConstructURL($this->path) );
      }
      if ( preg_match( '#^(/principals/[^/]+/[^/]+)/?$#', $this->path, $matches) ) {
        // Force a depth of 0 on these, which are at the wrong URL.
        $this->depth = 0;
      }
    }
    else if ( $this->path == '/' ) {
      $this->collection_id = -1;
      $this->collection_path = '/';
      $this->collection_type = 'root';
    }

    if ( $this->collection_path == $this->path ) $this->_is_collection = true;
    dbg_error_log( "caldav", " Collection '%s' is %d, type %s", $this->collection_path, $this->collection_id, $this->collection_type );

    /**
    * Extract the user whom we are accessing
    */
    $this->principal = new DAVPrincipal( array( "path" => $this->path, "options" => $this->options ) );
    $this->user_no  = $this->principal->user_no();
    $this->username = $this->principal->username();
    $this->by_email = $this->principal->byEmail();
    $this->principal_id = $this->principal->principal_id();

    if ( $this->collection_type == 'principal' || $this->collection_type == 'email' || $this->collection_type == 'proxy' ) {
      $this->collection = $this->principal->AsCollection();
      if( $this->collection_type == 'proxy' ) {
        $this->collection->is_proxy = 't';
        $this->collection->type = 'proxy';
        $this->collection->proxy_type = $this->proxy_type;
        $this->collection->dav_displayname = sprintf('Proxy %s for %s', $this->proxy_type, $this->principal->username() );
      }
    }
    elseif( $this->collection_type == 'root' ) {
      $this->collection = (object) array(
                            'collection_id' => 0,
                            'dav_name' => '/',
                            'dav_etag' => md5($c->system_name),
                            'is_calendar' => 'f',
                            'is_addressbook' => 'f',
                            'is_principal' => 'f',
                            'user_no' => 0,
                            'dav_displayname' => $c->system_name,
                            'type' => 'root',
                            'created' => date('Ymd\THis')
                          );
    }

    /**
    * Evaluate our permissions for accessing the target
    */
    $this->setPermissions();

    $this->supported_methods = array(
      'OPTIONS' => '',
      'PROPFIND' => '',
      'REPORT' => '',
      'DELETE' => '',
      'LOCK' => '',
      'UNLOCK' => '',
      'MOVE' => '',
      'ACL' => ''
    );
    if ( $this->IsCollection() ) {
      switch ( $this->collection_type ) {
        case 'root':
        case 'email':
          // We just override the list completely here.
          $this->supported_methods = array(
            'OPTIONS' => '',
            'PROPFIND' => '',
            'REPORT' => ''
          );
          break;
        case 'schedule-inbox':
        case 'schedule-outbox':
          $this->supported_methods = array_merge(
            $this->supported_methods,
            array(
              'POST' => '', 'GET' => '', 'PUT' => '', 'HEAD' => '', 'PROPPATCH' => ''
            )
          );
          break;
        case 'calendar':
          $this->supported_methods['GET'] = '';
          $this->supported_methods['PUT'] = '';
          $this->supported_methods['HEAD'] = '';
          break;
        case 'collection':
        case 'principal':
          $this->supported_methods['GET'] = '';
          $this->supported_methods['PUT'] = '';
          $this->supported_methods['HEAD'] = '';
          $this->supported_methods['MKCOL'] = '';
          $this->supported_methods['MKCALENDAR'] = '';
          $this->supported_methods['PROPPATCH'] = '';
          $this->supported_methods['BIND'] = '';
          break;
      }
    }
    else {
      $this->supported_methods = array_merge(
        $this->supported_methods,
        array(
          'GET' => '',
          'HEAD' => '',
          'PUT' => ''
        )
      );
    }

    $this->supported_reports = array(
      'DAV::principal-property-search' => '',
      'DAV::expand-property' => '',
      'DAV::sync-collection' => ''
    );
    if ( isset($this->collection) && $this->collection->is_calendar ) {
      $this->supported_reports = array_merge(
        $this->supported_reports,
        array(
          'urn:ietf:params:xml:ns:caldav:calendar-query' => '',
          'urn:ietf:params:xml:ns:caldav:calendar-multiget' => '',
          'urn:ietf:params:xml:ns:caldav:free-busy-query' => ''
        )
      );
    }
    if ( isset($this->collection) && $this->collection->is_addressbook ) {
      $this->supported_reports = array_merge(
        $this->supported_reports,
        array(
          'urn:ietf:params:xml:ns:carddav:addressbook-query' => '',
          'urn:ietf:params:xml:ns:carddav:addressbook-multiget' => ''
        )
      );
    }


    /**
    * If the content we are receiving is XML then we parse it here.  RFC2518 says we
    * should reasonably expect to see either text/xml or application/xml
    */
    if ( isset($this->content_type) && preg_match( '#(application|text)/xml#', $this->content_type ) ) {
      if ( !isset($this->raw_post) || $this->raw_post == '' ) {
        $this->XMLResponse( 400, new XMLElement( 'error', new XMLElement('missing-xml'), array( 'xmlns' => 'DAV:') ) );
      }
      $xml_parser = xml_parser_create_ns('UTF-8');
      $this->xml_tags = array();
      xml_parser_set_option ( $xml_parser, XML_OPTION_SKIP_WHITE, 1 );
      xml_parser_set_option ( $xml_parser, XML_OPTION_CASE_FOLDING, 0 );
      $rc = xml_parse_into_struct( $xml_parser, $this->raw_post, $this->xml_tags );
      if ( $rc == false ) {
        dbg_error_log( 'ERROR', 'XML parsing error: %s at line %d, column %d',
                    xml_error_string(xml_get_error_code($xml_parser)),
                    xml_get_current_line_number($xml_parser), xml_get_current_column_number($xml_parser) );
        $this->XMLResponse( 400, new XMLElement( 'error', new XMLElement('invalid-xml'), array( 'xmlns' => 'DAV:') ) );
      }
      xml_parser_free($xml_parser);
      if ( count($this->xml_tags) ) {
        dbg_error_log( "caldav", " Parsed incoming XML request body." );
      }
      else {
        $this->xml_tags = null;
        dbg_error_log( "ERROR", "Incoming request sent content-type XML with no XML request body." );
      }
    }

    /**
    * Look out for If-None-Match or If-Match headers
    */
    if ( isset($_SERVER["HTTP_IF_NONE_MATCH"]) ) {
      $this->etag_none_match = $_SERVER["HTTP_IF_NONE_MATCH"];
      if ( $this->etag_none_match == '' ) unset($this->etag_none_match);
    }
    if ( isset($_SERVER["HTTP_IF_MATCH"]) ) {
      $this->etag_if_match = $_SERVER["HTTP_IF_MATCH"];
      if ( $this->etag_if_match == '' ) unset($this->etag_if_match);
    }
  }


  /**
  * Permissions are controlled as follows:
  *  1. if the path is '/', the request has read privileges
  *  2. if the requester is an admin, the request has read/write priviliges
  *  3. if there is a <user name> component which matches the logged on user
  *     then the request has read/write privileges
  *  4. otherwise we query the defined relationships between users and use
  *     the minimum privileges returned from that analysis.
  *
  * @param int $user_no The current user number
  *
  */
  function setPermissions() {
    global $c, $session;

    if ( $this->path == '/' || $this->path == '' ) {
      $this->privileges = privilege_to_bits( array('read','read-free-busy','read-acl'));
      dbg_error_log( "caldav", "Full read permissions for user accessing /" );
    }
    else if ( $session->AllowedTo("Admin") || $session->principal->user_no() == $this->user_no ) {
      $this->privileges = privilege_to_bits('all');
      dbg_error_log( "caldav", "Full permissions for %s", ( $session->principal->user_no() == $this->user_no ? "user accessing their own hierarchy" : "a systems administrator") );
    }
    else {
      $this->privileges = 0;
      if ( $this->IsPublic() ) {
        $this->privileges = privilege_to_bits(array('read','read-free-busy'));
        dbg_error_log( "caldav", "Basic read permissions for user accessing a public collection" );
      }
      else if ( isset($c->public_freebusy_url) && $c->public_freebusy_url ) {
        $this->privileges = privilege_to_bits('read-free-busy');
        dbg_error_log( "caldav", "Basic free/busy permissions for user accessing a public free/busy URL" );
      }

      /**
      * In other cases we need to query the database for permissions
      */
      $params = array( ':session_principal_id' => $session->principal->principal_id(), ':scan_depth' => $c->permission_scan_depth );
      if ( isset($this->by_email) && $this->by_email ) {
        $sql ='SELECT pprivs( :session_principal_id::int8, :request_principal_id::int8, :scan_depth::int ) AS perm';
        $params[':request_principal_id'] = $this->principal_id;
      }
      else {
        $sql = 'SELECT path_privs( :session_principal_id::int8, :request_path::text, :scan_depth::int ) AS perm';
        $params[':request_path'] = $this->path;
      }
      $qry = new AwlQuery( $sql, $params );
      if ( $qry->Exec('caldav',__LINE__,__FILE__) && $permission_result = $qry->Fetch() )
        $this->privileges |= bindec($permission_result->perm);

      dbg_error_log( 'caldav', 'Restricted permissions for user accessing someone elses hierarchy: %s', decbin($this->privileges) );
      if ( isset($this->ticket) && $this->ticket->MatchesPath($this->path) ) {
        $this->privileges |= $this->ticket->privileges();
        dbg_error_log( 'caldav', 'Applying permissions for ticket "%s" now: %s', $this->ticket->id(), decbin($this->privileges) );
      }
    }

    /** convert privileges into older style permissions */
    $this->permissions = array();
    $privs = bits_to_privilege($this->privileges);
    foreach( $privs AS $k => $v ) {
      switch( $v ) {
        case 'DAV::all':    $type = 'abstract';   break;
        case 'DAV::write':  $type = 'aggregate';  break;
        default: $type = 'real';
      }
      $v = str_replace('DAV::', '', $v);
      $this->permissions[$v] = $type;
    }

  }


  /**
  * Checks whether the resource is locked, returning any lock token, or false
  *
  * @todo This logic does not catch all locking scenarios.  For example an infinite
  * depth request should check the permissions for all collections and resources within
  * that.  At present we only maintain permissions on a per-collection basis though.
  */
  function IsLocked() {
    if ( !isset($this->_locks_found) ) {
      $this->_locks_found = array();

      $sql = 'DELETE FROM locks WHERE (start + timeout) < current_timestamp';
      $qry = new AwlQuery($sql);
      $qry->Exec('caldav',__LINE__,__FILE__);

      /**
      * Find the locks that might apply and load them into an array
      */
      $sql = 'SELECT * FROM locks WHERE :dav_name::text ~ (\'^\'||dav_name||:pattern_end_match)::text';
      $qry = new AwlQuery($sql, array( ':dav_name' => $this->path, ':pattern_end_match' => ($this->IsInfiniteDepth() ? '' : '$') ) );
      if ( $qry->Exec('caldav',__LINE__,__FILE__) ) {
        while( $lock_row = $qry->Fetch() ) {
          $this->_locks_found[$lock_row->opaquelocktoken] = $lock_row;
        }
      }
      else {
        $this->DoResponse(500,translate("Database Error"));
        // Does not return.
      }
    }

    foreach( $this->_locks_found AS $lock_token => $lock_row ) {
      if ( $lock_row->depth == DEPTH_INFINITY || $lock_row->dav_name == $this->path ) {
        return $lock_token;
      }
    }

    return false;  // Nothing matched
  }


  /**
  * Checks whether the collection is public
  */
  function IsPublic() {
    if ( isset($this->collection) && isset($this->collection->publicly_readable) && $this->collection->publicly_readable == 't' ) {
      return true;
    }
    return false;
  }


  private static function supportedPrivileges() {
    return array(
      'all' => array(
        'read' => translate('Read the content of a resource or collection'),
        'write' => array(
          'bind' => translate('Create a resource or collection'),
          'unbind' => translate('Delete a resource or collection'),
          'write-content' => translate('Write content'),
          'write-properties' => translate('Write properties')
        ),
        'urn:ietf:params:xml:ns:caldav:read-free-busy' => translate('Read the free/busy information for a calendar collection'),
        'read-acl' => translate('Read ACLs for a resource or collection'),
        'read-current-user-privilege-set' => translate('Read the details of the current user\'s access control to this resource.'),
        'write-acl' => translate('Write ACLs for a resource or collection'),
        'unlock' => translate('Remove a lock'),

        'urn:ietf:params:xml:ns:caldav:schedule-deliver' => array(
          'urn:ietf:params:xml:ns:caldav:schedule-deliver-invite'=> translate('Deliver scheduling invitations from an organiser to this scheduling inbox'),
          'urn:ietf:params:xml:ns:caldav:schedule-deliver-reply' => translate('Deliver scheduling replies from an attendee to this scheduling inbox'),
          'urn:ietf:params:xml:ns:caldav:schedule-query-freebusy' => translate('Allow free/busy enquiries targeted at the owner of this scheduling inbox')
        ),

        'urn:ietf:params:xml:ns:caldav:schedule-send' => array(
          'urn:ietf:params:xml:ns:caldav:schedule-send-invite' => translate('Send scheduling invitations as an organiser from the owner of this scheduling outbox.'),
          'urn:ietf:params:xml:ns:caldav:schedule-send-reply' => translate('Send scheduling replies as an attendee from the owner of this scheduling outbox.'),
          'urn:ietf:params:xml:ns:caldav:schedule-send-freebusy' => translate('Send free/busy enquiries')
        )
      )
    );
  }

  /**
  * Returns the dav_name of the resource in our internal namespace
  */
  function dav_name() {
    if ( isset($this->path) ) return $this->path;
    return null;
  }


  /**
  * Returns the name for this depth: 0, 1, infinity
  */
  function GetDepthName( ) {
    if ( $this->IsInfiniteDepth() ) return 'infinity';
    return $this->depth;
  }

  /**
  * Returns the tail of a Regex appropriate for this Depth, when appended to
  *
  */
  function DepthRegexTail( $for_collection_report = false) {
    if ( $this->IsInfiniteDepth() ) return '';
    if ( $this->depth == 0 && $for_collection_report ) return '[^/]+$';
    if ( $this->depth == 0 ) return '$';
    return '[^/]*/?$';
  }

  /**
  * Returns the locked row, either from the cache or from the database
  *
  * @param string $dav_name The resource which we want to know the lock status for
  */
  function GetLockRow( $lock_token ) {
    if ( isset($this->_locks_found) && isset($this->_locks_found[$lock_token]) ) {
      return $this->_locks_found[$lock_token];
    }

    $qry = new AwlQuery('SELECT * FROM locks WHERE opaquelocktoken = :lock_token', array( ':lock_token' => $lock_token ) );
    if ( $qry->Exec('caldav',__LINE__,__FILE__) ) {
      $lock_row = $qry->Fetch();
      $this->_locks_found = array( $lock_token => $lock_row );
      return $this->_locks_found[$lock_token];
    }
    else {
      $this->DoResponse( 500, translate("Database Error") );
    }

    return false;  // Nothing matched
  }


  /**
  * Checks to see whether the lock token given matches one of the ones handed in
  * with the request.
  *
  * @param string $lock_token The opaquelocktoken which we are looking for
  */
  function ValidateLockToken( $lock_token ) {
    if ( isset($this->lock_token) && $this->lock_token == $lock_token ) {
      dbg_error_log( "caldav", "They supplied a valid lock token.  Great!" );
      return true;
    }
    if ( isset($this->if_clause) ) {
      dbg_error_log( "caldav", "Checking lock token '%s' against '%s'", $lock_token, $this->if_clause );
      $tokens = preg_split( '/[<>]/', $this->if_clause );
      foreach( $tokens AS $k => $v ) {
        dbg_error_log( "caldav", "Checking lock token '%s' against '%s'", $lock_token, $v );
        if ( 'opaquelocktoken:' == substr( $v, 0, 16 ) ) {
          if ( substr( $v, 16 ) == $lock_token ) {
            dbg_error_log( "caldav", "Lock token '%s' validated OK against '%s'", $lock_token, $v );
            return true;
          }
        }
      }
    }
    else {
      @dbg_error_log( "caldav", "Invalid lock token '%s' - not in Lock-token (%s) or If headers (%s) ", $lock_token, $this->lock_token, $this->if_clause );
    }

    return false;
  }


  /**
  * Returns the DB object associated with a lock token, or false.
  *
  * @param string $lock_token The opaquelocktoken which we are looking for
  */
  function GetLockDetails( $lock_token ) {
    if ( !isset($this->_locks_found) && false === $this->IsLocked() ) return false;
    if ( isset($this->_locks_found[$lock_token]) ) return $this->_locks_found[$lock_token];
    return false;
  }


  /**
  * This will either (a) return false if no locks apply, or (b) return the lock_token
  * which the request successfully included to open the lock, or:
  * (c) respond directly to the client with the failure.
  *
  * @return mixed false (no lock) or opaquelocktoken (opened lock)
  */
  function FailIfLocked() {
    if ( $existing_lock = $this->IsLocked() ) { // NOTE Assignment in if() is expected here.
      dbg_error_log( "caldav", "There is a lock on '%s'", $this->path);
      if ( ! $this->ValidateLockToken($existing_lock) ) {
        $lock_row = $this->GetLockRow($existing_lock);
        /**
        * Already locked - deny it
        */
        $response[] = new XMLElement( 'response', array(
            new XMLElement( 'href',   $lock_row->dav_name ),
            new XMLElement( 'status', 'HTTP/1.1 423 Resource Locked')
        ));
        if ( $lock_row->dav_name != $this->path ) {
          $response[] = new XMLElement( 'response', array(
              new XMLElement( 'href',   $this->path ),
              new XMLElement( 'propstat', array(
                new XMLElement( 'prop', new XMLElement( 'lockdiscovery' ) ),
                new XMLElement( 'status', 'HTTP/1.1 424 Failed Dependency')
              ))
          ));
        }
        $response = new XMLElement( "multistatus", $response, array('xmlns'=>'DAV:') );
        $xmldoc = $response->Render(0,'<?xml version="1.0" encoding="utf-8" ?>');
        $this->DoResponse( 207, $xmldoc, 'text/xml; charset="utf-8"' );
        // Which we won't come back from
      }
      return $existing_lock;
    }
    return false;
  }


  /**
  * Coerces the Content-type of the request into something valid/appropriate
  */
  function CoerceContentType() {
    if ( isset($this->content_type) ) {
      $type = explode( '/', $this->content_type, 2);
      /** @todo: Perhaps we should look at the target collection type, also. */
      if ( $type[0] == 'text' ) {
        if ( !empty($type[1]) && ($type[1] == 'vcard' || $type[1] == 'calendar' || $type[1] == 'x-vcard') ) {
          return;
        }
      }
    }

    /** Null (or peculiar) content-type supplied so we have to try and work it out... */
    $first_word = trim(substr( $this->raw_post, 0, 30));
    $first_word = strtoupper( preg_replace( '/\s.*/s', '', $first_word ) );
    switch( $first_word ) {
      case '<?XML':
        dbg_error_log( 'LOG WARNING', 'Application sent content-type of "%s" instead of "text/xml"',
                                        (isset($this->content_type)?$this->content_type:'(null)') );
        $this->content_type = 'text/xml';
        break;
      case 'BEGIN:VCALENDAR':
        dbg_error_log( 'LOG WARNING', 'Application sent content-type of "%s" instead of "text/calendar"',
                                        (isset($this->content_type)?$this->content_type:'(null)') );
        $this->content_type = 'text/calendar';
        break;
      case 'BEGIN:VCARD':
        dbg_error_log( 'LOG WARNING', 'Application sent content-type of "%s" instead of "text/vcard"',
                                        (isset($this->content_type)?$this->content_type:'(null)') );
        $this->content_type = 'text/vcard';
        break;
      default:
        dbg_error_log( 'LOG NOTICE', 'Unusual content-type of "%s" and first word of content is "%s"',
                                        (isset($this->content_type)?$this->content_type:'(null)'), $first_word );
    }
    if ( empty($this->content_type) ) $this->content_type = 'text/plain';
  }


  /**
   * Returns true if the 'Prefer: return=minimal' or 'Brief: t' were present in the request headers.
   */
  function PreferMinimal() {
    if ( empty($this->prefer) ) return false;
    foreach( $this->prefer AS $v ) {
      if ( $v == 'return=minimal' ) return true;
      if ( $v == 'return-minimal' ) return true; // RFC7240 up until draft -15 (Oct 2012)
    }
    return false;
  }

  /**
  * Returns true if the URL referenced by this request points at a collection.
  */
  function IsCollection( ) {
    if ( !isset($this->_is_collection) ) {
      $this->_is_collection = preg_match( '#/$#', $this->path );
    }
    return $this->_is_collection;
  }


  /**
  * Returns true if the URL referenced by this request points at a calendar collection.
  */
  function IsCalendar( ) {
    if ( !$this->IsCollection() || !isset($this->collection) ) return false;
    return $this->collection->is_calendar == 't';
  }


  /**
  * Returns true if the URL referenced by this request points at an addressbook collection.
  */
  function IsAddressBook( ) {
    if ( !$this->IsCollection() || !isset($this->collection) ) return false;
    return $this->collection->is_addressbook == 't';
  }


  /**
  * Returns true if the URL referenced by this request points at a principal.
  */
  function IsPrincipal( ) {
    if ( !isset($this->_is_principal) ) {
      $this->_is_principal = preg_match( '#^/[^/]+/$#', $this->path );
    }
    return $this->_is_principal;
  }


  /**
  * Returns true if the URL referenced by this request is within a proxy URL
  */
  function IsProxyRequest( ) {
    if ( !isset($this->_is_proxy_request) ) {
      $this->_is_proxy_request = preg_match( '#^/[^/]+/calendar-proxy-(read|write)/?[^/]*$#', $this->path );
    }
    return $this->_is_proxy_request;
  }


  /**
  * Returns true if the request asked for infinite depth
  */
  function IsInfiniteDepth( ) {
    return ($this->depth == DEPTH_INFINITY);
  }


  /**
  * Returns the ID of the collection of, or containing this request
  */
  function CollectionId( ) {
    return $this->collection_id;
  }


  /**
  * Returns the array of supported privileges converted into XMLElements
  */
  function BuildSupportedPrivileges( &$reply, $privs = null ) {
    $privileges = array();
    if ( $privs === null ) $privs = self::supportedPrivileges();
    foreach( $privs AS $k => $v ) {
      dbg_error_log( 'caldav', 'Adding privilege "%s" which is "%s".', $k, $v );
      $privilege = new XMLElement('privilege');
      $reply->NSElement($privilege,$k);
      $privset = array($privilege);
      if ( is_array($v) ) {
        dbg_error_log( 'caldav', '"%s" is a container of sub-privileges.', $k );
        $privset = array_merge($privset, $this->BuildSupportedPrivileges($reply,$v));
      }
      else if ( $v == 'abstract' ) {
        dbg_error_log( 'caldav', '"%s" is an abstract privilege.', $v );
        $privset[] = new XMLElement('abstract');
      }
      else if ( strlen($v) > 1 ) {
        $privset[] = new XMLElement('description', $v);
      }
      $privileges[] = new XMLElement('supported-privilege',$privset);
    }
    return $privileges;
  }


  /**
  * Are we allowed to do the requested activity
  *
  * +------------+------------------------------------------------------+
  * | METHOD     | PRIVILEGES                                           |
  * +------------+------------------------------------------------------+
  * | MKCALENDAR | DAV:bind                                             |
  * | REPORT     | DAV:read or CALDAV:read-free-busy (on all referenced |
  * |            | resources)                                           |
  * +------------+------------------------------------------------------+
  *
  * @param string $activity The activity we want to do.
  */
  function AllowedTo( $activity ) {
    global $session;
    dbg_error_log('caldav', 'Checking whether "%s" is allowed to "%s"', $session->principal->username(), $activity);
    if ( isset($this->permissions['all']) ) return true;
    switch( $activity ) {
      case 'all':
        return false; // If they got this far then they don't
        break;

      case "CALDAV:schedule-send-freebusy":
        return isset($this->permissions['read']) || isset($this->permissions['urn:ietf:params:xml:ns:caldav:read-free-busy']);
        break;

      case "CALDAV:schedule-send-invite":
        return isset($this->permissions['read']) || isset($this->permissions['urn:ietf:params:xml:ns:caldav:read-free-busy']);
        break;

      case "CALDAV:schedule-send-reply":
        return isset($this->permissions['read']) || isset($this->permissions['urn:ietf:params:xml:ns:caldav:read-free-busy']);
        break;

      case 'freebusy':
        return isset($this->permissions['read']) || isset($this->permissions['urn:ietf:params:xml:ns:caldav:read-free-busy']);
        break;

      case 'delete':
        return isset($this->permissions['write']) || isset($this->permissions['unbind']);
        break;

      case 'proppatch':
        return isset($this->permissions['write']) || isset($this->permissions['write-properties']);
        break;

      case 'modify':
        return isset($this->permissions['write']) || isset($this->permissions['write-content']);
        break;

      case 'create':
        return isset($this->permissions['write']) || isset($this->permissions['bind']);
        break;

      case 'mkcalendar':
      case 'mkcol':
        if ( !isset($this->permissions['write']) || !isset($this->permissions['bind']) ) return false;
        if ( $this->is_principal ) return false;
        if ( $this->path == '/' ) return false;
        break;

      default:
        $test_bits = privilege_to_bits( $activity );
//        dbg_error_log( 'caldav', 'request::AllowedTo("%s") (%s) against allowed "%s" => "%s" (%s)',
//             (is_array($activity) ? implode(',',$activity) : $activity), decbin($test_bits),
//             decbin($this->privileges), ($this->privileges & $test_bits), decbin($this->privileges & $test_bits) );
        return (($this->privileges & $test_bits) > 0 );
        break;
    }

    return false;
  }



  /**
  * Return the privileges bits for the current session user to this resource
  */
  function Privileges() {
    return $this->privileges;
  }


  /**
   * Check that the incoming Etag matches the one for the existing (or non-existing) resource.
   *
   * @param boolean $exists Whether the destination exists
   * @param string $dest_etag The etag for the destination.
   */
  function CheckEtagMatch( $exists, $dest_etag ) {
    global $c;

    if ( ! $exists ) {
      if ( (isset($this->etag_if_match) && $this->etag_if_match != '') ) {
        /**
        * RFC2068, 14.25:
        * If none of the entity tags match, or if "*" is given and no current
        * entity exists, the server MUST NOT perform the requested method, and
        * MUST return a 412 (Precondition Failed) response.
        */
        $this->PreconditionFailed(412, 'if-match', translate('No resource exists at the destination.'));
      }
    }
    else {

      if ( isset($c->strict_etag_checking) && $c->strict_etag_checking )
         $trim_chars = '\'\\" ';
      else
        $trim_chars = ' ';

      if ( isset($this->etag_if_match) && $this->etag_if_match != '' && trim( $this->etag_if_match, $trim_chars) != trim( $dest_etag, $trim_chars ) ) {
        /**
        * RFC2068, 14.25:
        * If none of the entity tags match, or if "*" is given and no current
        * entity exists, the server MUST NOT perform the requested method, and
        * MUST return a 412 (Precondition Failed) response.
        */
        $this->PreconditionFailed(412,'if-match',sprintf('Existing resource ETag of <<%s>> does not match <<%s>>', $dest_etag, $this->etag_if_match) );
      }
      else if ( isset($this->etag_none_match) && $this->etag_none_match != ''
                   && ($this->etag_none_match == $dest_etag || $this->etag_none_match == '*') ) {
        /**
        * RFC2068, 14.26:
        * If any of the entity tags match the entity tag of the entity that
        * would have been returned in the response to a similar GET request
        * (without the If-None-Match header) on that resource, or if "*" is
        * given and any current entity exists for that resource, then the
        * server MUST NOT perform the requested method.
        */
        $this->PreconditionFailed(412,'if-none-match', translate( 'Existing resource matches "If-None-Match" header - not accepted.'));
      }
    }

  }


  /**
  * Is the user has the privileges to do what is requested.
  */
  function HavePrivilegeTo( $do_what ) {
    $test_bits = privilege_to_bits( $do_what );
//    dbg_error_log( 'caldav', 'request::HavePrivilegeTo("%s") [%s] against allowed "%s" => "%s" (%s)',
//             (is_array($do_what) ? implode(',',$do_what) : $do_what), decbin($test_bits),
//              decbin($this->privileges), ($this->privileges & $test_bits), decbin($this->privileges & $test_bits) );
    return ($this->privileges & $test_bits) > 0;
  }


  /**
  * Sometimes it's a perfectly formed request, but we just don't do that :-(
  * @param array $unsupported An array of the properties we don't support.
  */
  function UnsupportedRequest( $unsupported ) {
    if ( isset($unsupported) && count($unsupported) > 0 ) {
      $badprops = new XMLElement( "prop" );
      foreach( $unsupported AS $k => $v ) {
        // Not supported at this point...
        dbg_error_log("ERROR", " %s: Support for $v:$k properties is not implemented yet", $this->method );
        $badprops->NewElement(strtolower($k),false,array("xmlns" => strtolower($v)));
      }
      $error = new XMLElement("error", $badprops, array("xmlns" => "DAV:") );

      $this->XMLResponse( 422, $error );
    }
  }


  /**
  * Send a need-privileges error response.  This function will only return
  * if the $href is not supplied and the current user has the specified
  * permission for the request path.
  *
  * @param string $privilege The name of the needed privilege.
  * @param string $href The unconstructed URI where we needed the privilege.
  */
  function NeedPrivilege( $privileges, $href=null ) {
    if ( is_string($privileges) ) $privileges = array( $privileges );
    if ( !isset($href) ) {
      if ( $this->HavePrivilegeTo($privileges) ) return;
      $href = $this->path;
    }

    $reply = new XMLDocument( array('DAV:' => '') );
    $privnodes = array( $reply->href(ConstructURL($href)), new XMLElement( 'privilege' ) );
    // RFC3744 specifies that we can only respond with one needed privilege, so we pick the first.
    $reply->NSElement( $privnodes[1], $privileges[0] );
    $xml = new XMLElement( 'need-privileges', new XMLElement( 'resource', $privnodes) );
    $xmldoc = $reply->Render('error',$xml);
    $this->DoResponse( 403, $xmldoc, 'text/xml; charset="utf-8"' );
    exit(0);  // Unecessary, but might clarify things
  }


  /**
  * Send an error response for a failed precondition.
  *
  * @param int $status The status code for the failed precondition.  Normally 403
  * @param string $precondition The namespaced precondition tag.
  * @param string $explanation An optional text explanation for the failure.
  */
  function PreconditionFailed( $status, $precondition, $explanation = '', $xmlns='DAV:') {
    $xmldoc = sprintf('<?xml version="1.0" encoding="utf-8" ?>
<error xmlns="%s">
  <%s/>%s
</error>', $xmlns, str_replace($xmlns.':', '', $precondition), $explanation );

    $this->DoResponse( $status, $xmldoc, 'text/xml; charset="utf-8"' );
    exit(0);  // Unecessary, but might clarify things
  }


  /**
  * Send a simple error informing the client that was a malformed request
  *
  * @param string $text An optional text description of the failure.
  */
  function MalformedRequest( $text = 'Bad request' ) {
    $this->DoResponse( 400, $text );
    exit(0);  // Unecessary, but might clarify things
  }


  /**
  * Send an XML Response.  This function will never return.
  *
  * @param int $status The HTTP status to respond
  * @param XMLElement $xmltree An XMLElement tree to be rendered
  */
  function XMLResponse( $status, $xmltree ) {
    $xmldoc = $xmltree->Render(0,'<?xml version="1.0" encoding="utf-8" ?>');
    $etag = md5($xmldoc);
    if ( !headers_sent() ) header("ETag: \"$etag\"");
    $this->DoResponse( $status, $xmldoc, 'text/xml; charset="utf-8"' );
    exit(0);  // Unecessary, but might clarify things
  }

  public static function kill_on_exit() {
    posix_kill( getmypid(), 28 );
  }

  /**
  * Utility function we call when we have a simple status-based response to
  * return to the client.  Possibly
  *
  * @param int $status The HTTP status code to send.
  * @param string $message The friendly text message to send with the response.
  */
  function DoResponse( $status, $message="", $content_type="text/plain; charset=\"utf-8\"" ) {
    global $session, $c;
    if ( !headers_sent() ) @header( sprintf("HTTP/1.1 %d %s", $status, getStatusMessage($status)) );
    if ( !headers_sent() ) @header( sprintf("X-DAViCal-Version: DAViCal/%d.%d.%d; DB/%d.%d.%d", $c->code_major, $c->code_minor, $c->code_patch, $c->schema_major, $c->schema_minor, $c->schema_patch) );
    if ( !headers_sent() ) header( "Content-type: ".$content_type );

    if ( (isset($c->dbg['ALL']) && $c->dbg['ALL']) || (isset($c->dbg['response']) && $c->dbg['response'])
         || $status == 400  || $status == 402 || $status == 403 || $status > 404 ) {
      @dbg_error_log( "LOG ", 'Response status %03d for %s %s', $status, $this->method, $_SERVER['REQUEST_URI'] );
      $lines = headers_list();
      dbg_error_log( "LOG ", "***************** Response Header ****************" );
      foreach( $lines AS $v ) {
        dbg_error_log( "LOG headers", "-->%s", $v );
      }
      dbg_error_log( "LOG ", "******************** Response ********************" );
      // Log the request in all it's gory detail.
      $lines = preg_split( '#[\r\n]+#', $message);
      foreach( $lines AS $v ) {
        dbg_error_log( "LOG response", "-->%s", $v );
      }
    }

    $script_finish = microtime(true);
    $script_time = $script_finish - $c->script_start_time;
    $message_length = strlen($message);
    if ( $message != '' ) {
      if ( !headers_sent() ) header( "Content-Length: ".$message_length );
      echo $message;
    }

    if ( isset($c->dbg['caldav']) && $c->dbg['caldav'] ) {
      if ( $message_length > 100 || strstr($message, "\n") ) {
        $message = substr( preg_replace("#\s+#m", ' ', $message ), 0, 100) . ($message_length > 100 ? "..." : "");
      }

      dbg_error_log("caldav", "Status: %d, Message: %s, User: %d, Path: %s", $status, $message, $session->principal->user_no(), $this->path);
    }
    if ( isset($c->dbg['statistics']) && $c->dbg['statistics'] ) {
      $memory = '';
      if ( function_exists('memory_get_usage') ) {
        $memory = sprintf( ', Memory: %dk, Peak: %dk', memory_get_usage()/1024, memory_get_peak_usage(true)/1024);
      }
      @dbg_error_log("statistics", "Method: %s, Status: %d, Script: %5.3lfs, Queries: %5.3lfs, URL: %s%s",
                         $this->method, $status, $script_time, $c->total_query_time, $this->path, $memory);
    }
    try {
      @ob_flush(); // Seems like it should be better to do the following but is problematic on PHP5.3 at least: while ( ob_get_level() > 0 ) ob_end_flush();
    }
    catch( Exception $ignored ) {}

    if ( isset($c->metrics_style) && $c->metrics_style !== false ) {
      $flush_time = microtime(true) - $script_finish;
      $this->DoMetrics($status, $message_length, $script_time, $flush_time);
    }

    if ( isset($c->exit_after_memory_exceeds) && function_exists('memory_get_peak_usage') && memory_get_peak_usage(true) > $c->exit_after_memory_exceeds ) { // 64M
      @dbg_error_log("statistics", "Peak memory use exceeds %d bytes (%d) - killing process %d", $c->exit_after_memory_exceeds, memory_get_peak_usage(true), getmypid());
      register_shutdown_function( 'CalDAVRequest::kill_on_exit' );
    }

    exit(0);
  }


  /**
  * Record the metrics related to this request.
  *
  * @param status The HTTP status code for this response
  * @param response_size The size of the response (bytes).
  * @param script_time The time taken to generate the response (pre-sending)
  * @param flush_time The time taken to send the response (buffers flushed)
  */
  function DoMetrics($status, $response_size, $script_time, $flush_time) {
    global $c;
    static $ns = 'metrics';

    $method = (empty($this->method) ? 'UNKNOWN' : $this->method);

    // If they want 'both' or 'all' or something then that's what they will get
    // If they don't want counters, they must want to use memcache!
    if ( $c->metrics_style != 'counters' ) {
      $cache = getCacheInstance();
      if ( $cache->isActive() ) {

        $base_key = $method.':';
        $count_like_this = $cache->increment( $ns, $base_key.$status );
        $cache->increment( $ns, $base_key.'size', $response_size );
        $cache->increment( $ns, $base_key.'script_time', intval($script_time * 1000000) );
        $cache->increment( $ns, $base_key.'flush_time', intval($flush_time * 1000000) );
        $cache->increment( $ns, $base_key.'query_time', intval($c->total_query_time * 1000000) );

        if ( $count_like_this == 1 ) {
          // We need to maintain a set of details regarding the methods and statuses we have
          // encountered, so we know what to retrieve.  Since this is the first one like
          // this, we add it to the index.
          try {
            $index = unserialize($cache->get($ns, 'index'));
          } catch (Exception $e) {
            $index = array('methods' => array(), 'statuses' => array());
          }
          $index['methods'][$method] = 1;
          $index['statuses'][$status] = 1;
          $cache->set($ns, 'index', serialize($index), 0);
        }
      }
      else {
        error_log("Full statistics are only available with a working Memcache configuration");
      }
    }

    // If they don't want memcache, they must want to use counters!
    if ( $c->metrics_style != 'memcache' ) {
      $qstring = "SELECT nextval('%s')";
      switch( $method ) {
        case 'OPTIONS':
        case 'REPORT':
        case 'PROPFIND':
        case 'GET':
        case 'PUT':
        case 'HEAD':
        case 'PROPPATCH':
        case 'POST':
        case 'MKCALENDAR':
        case 'MKCOL':
        case 'DELETE':
        case 'MOVE':
        case 'ACL':
        case 'LOCK':
        case 'UNLOCK':
        case 'MKTICKET':
        case 'DELTICKET':
        case 'BIND':
          $counter = strtolower($this->method);
          break;
        default:
          $counter = 'unknown';
          break;
      }
      $qry = new AwlQuery( "SELECT nextval('metrics_count_" . $counter . "')" );
      $qry->Exec('always',__LINE__,__FILE__);
    }
  }
}

