<?php
/**
* Functions that are needed for iScheduling requests
*
*  - verifying Domain Key signatures
*  - delivering remote scheduling requests to local users inboxes
*  - Utility functions which we can use to decide whether this
*    is a permitted activity for this user.
*
* @package   davical
* @subpackage   iSchedule
* @author    Rob Ostensen <rob@boxacle.net>
* @copyright Rob Ostensen
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once("XMLDocument.php");

/**
* A class for handling iScheduling requests.
*
* @package   davical
* @subpackage   iSchedule
*/
class iSchedule
{
  public $parsed;
  public $selector;
  public $domain;
  private $dk;
  private $DKSig;
  private $try_anyway = false;
  private $failed = false;
  private $failOnError = true;
  private $subdomainsOK = true;
  private $remote_public_key ;
  private $required_headers = Array ( 'host',  // draft 01 section 7.1 required headers
                                      'originator',
                                      'recipient',
                                      'content-type' );
  private $disallowed_headers = Array ( 'connection',  // draft 01 section 7.1 disallowed headers
                                        'keep-alive',
                                        'dkim-signature',
                                        'proxy-authenticate',
                                        'proxy-authorization',
                                        'te',
                                        'trailers',
                                        'transfer-encoding',
                                        'upgrade' );

  function __construct ( )
  {
    global $c;
    $this->selector = 'cal';
    if ( is_object ( $c ) && isset ( $c->scheduling_dkim_selector ) )
    {
      $this->scheduling_dkim_domain = $c->scheduling_dkim_domain ;
      $this->scheduling_dkim_selector = $c->scheduling_dkim_selector ;
      $this->schedule_private_key = $c->schedule_private_key ;
      if ( ! preg_match ( '/BEGIN RSA PRIVATE KEY/', $this->schedule_private_key ) )
      {
        $key = file_get_contents ( $this->schedule_private_key );
        if ( $key !== false )
          $this->schedule_private_key = $key;
      }
      if ( isset ( $c->scheduling_dkim_algo ) )
        $this->scheduling_dkim_algo = $c->scheduling_dkim_algo;
      else
        $this->scheduling_dkim_algo = 'sha256';
      if ( isset ( $c->scheduling_dkim_valid_time ) )
        $this->valid_time = $c->scheduling_dkim_valid_time;
    }
  }

  /**
  * gets the domainkey TXT record from DNS
  */
  function getTxt ()
  {
    global $icfg;
    // TODO handle parents of subdomains and procuration records
    if ( $icfg [ $this->remote_selector . '._domainkey.' . $this->remote_server ] )
    {
      $this->dk = $icfg [ $this->remote_selector . '._domainkey.' . $this->remote_server ];
      return true;
    }

    $dkim = dns_get_record ( $this->remote_selector . '._domainkey.' . $this->remote_server , DNS_TXT );
    if ( count ( $dkim ) > 0 )
    {
      $this->dk = $dkim [ 0 ] [ 'txt' ];
      if ( $dkim [ 0 ] [ 'entries' ] )
      {
        $this->dk = '';
        foreach ( $dkim [ 0 ] [ 'entries' ] as $v )
        {
          $this->dk .= trim ( $v );
        }
      }
      dbg_error_log( 'ischedule', 'getTxt '. $this->dk . ' XX');
    }
    else
    {
      dbg_error_log( 'ischedule', 'getTxt FAILED '. print_r ( $dkim ) );
      $this->failed = true;
      return false;
    }
    return true;
  }

  /**
  * strictly for testing purposes
  */
  function setTxt ( $dk )
  {
    $this->dk = $dk;
  }

  /**
  * parses DNS TXT record from domainkey lookup
  */
  function parseTxt ( )
  {
    if ( $this->failed == true )
      return false;
    $clean = preg_replace ( '/\s?([;=])\s?/', '$1', $this->dk );
    $pairs = preg_split ( '/;/', $clean );
    $this->parsed = array();
    foreach ( $pairs as $v )
    {
      list($key,$value) = preg_split ( '/=/', $v, 2 );
      $value = trim ( $value, '\\' );
      if ( preg_match ( '/(g|k|n|p|s|t|v)/', $key ) )
        $this->parsed [ $key ] = $value;
      else
        $this->parsed_ignored [ $key ] = $value;
    }
    return true;
  }

  /**
  * validates that domainkey is acceptable for the current request
  */
  function validateKey ( )
  {
    $this->failed = true;
    if ( isset ( $this->parsed [ 's' ] ) )
    {
      if ( ! preg_match ( '/(\*|calendar)/', $this->parsed [ 's' ] ) ) {
        dbg_error_log( 'ischedule', 'validateKey ERROR: bad selector' );
        return false; // not a wildcard or calendar key
      }
    }
    if ( isset ( $this->parsed [ 'k' ] ) && $this->parsed [ 'k' ] != 'rsa' ) {
      dbg_error_log( 'ischedule', 'validateKey ERROR: bad key algorythm, algo was:' . $this->parsed [ 'k' ] );
      return false; // we only speak rsa for now
    }
    if ( isset ( $this->parsed [ 't' ] ) && ! preg_match ( '/^[y:s]+$/', $this->parsed [ 't' ] ) ) {
      dbg_error_log( 'ischedule', 'validateKey ERROR: type mismatch' );
      return false;
    }
    else
    {
      if ( preg_match ( '/y/', $this->parsed [ 't' ] ) )
        $this->failOnError = false;
      if ( preg_match ( '/s/', $this->parsed [ 't' ] ) )
        $this->subdomainsOK = false;
    }
    if ( isset ( $this->parsed [ 'g' ] ) )
      $this->remote_user_rule = $this->parsed [ 'g' ];
    else
      $this->remote_user_rule = '*';
    if ( isset ( $this->parsed [ 'p' ] ) )
    {
      if ( preg_match ( '/[^A-Za-z0-9_=+\/]/', $this->parsed [ 'p' ] ) )
        return false;
      $data = "-----BEGIN PUBLIC KEY-----\n" . implode ("\n",str_split ( $this->parsed [ 'p' ], 64 )) . "\n-----END PUBLIC KEY-----";
      if ( $data === false )
        return false;
      $this->remote_public_key = $data;
    }
    else {
      dbg_error_log( 'ischedule', 'validateKey ERROR: no key in dns record' . $this->parsed [ 'p' ] );
      return false;
    }
    $this->failed = false;
    return true;
  }

  /**
  * finds a remote calendar server via DNS SRV records
  */
  function getServer ( )
  {
    global $icfg;
    if ( $icfg [ $this->domain ] )
    {
      $this->remote_server = $icfg [ $this->domain ] [ 'server' ];
      $this->remote_port = $icfg [ $this->domain ] [ 'port' ];
      $this->remote_ssl = $icfg [ $this->domain ] [ 'ssl' ];
      return true;
    }
    $this->remote_ssl = false;
    $parts = explode ( '.', $this->domain );
    $tld = $parts [ count ( $parts ) - 1 ];
    $len = 2;
    if ( strlen ( $tld ) == 2 && in_array ( $tld, Array ( 'uk', 'nz' ) ) )
      $len = 3; // some country code tlds should have 3 components
    if ( $this->domain == 'mycaldav' || $this->domain == 'altcaldav' )
      $len = 1;
    while ( count ( $parts ) >= $len )
    {
      $r = dns_get_record ( '_ischedules._tcp.' . implode ( '.', $parts ) , DNS_SRV );
      if ( 0 < count ( $r ) )
      {
        $remote_server            = $r [ 0 ] [ 'target' ];
        $remote_port              = $r [ 0 ] [ 'port' ];
        $this->remote_ssl = true;
        break;
      }
      if ( ! isset ( $remote_server ) )
      {
        $r = dns_get_record ( '_ischedule._tcp.' . implode ( '.', $parts ) , DNS_SRV );
        if ( 0 < count ( $r ) )
        {
          $remote_server            = $r [ 0 ] [ 'target' ];
          $remote_port              = $r [ 0 ] [ 'port' ];
          break;
        }
      }
      array_shift ( $parts );
    }
    if ( ! isset ( $remote_server ) )
    {
      if ( $this->try_anyway == true )
      {
        if ( ! isset ( $remote_server ) )
          $remote_server = $this->domain;
        if ( ! isset ( $remote_port ) )
          $remote_port = 80;
      }
      else {
        dbg_error_log('ischedule', 'Domain %s did not have srv records for iSchedule', $this->domain );
        return false;
      }
    }
    dbg_error_log('ischedule', $this->domain . ' found srv records for ' . $remote_server . ':' . $remote_port );
    $this->remote_server = $remote_server;
    $this->remote_port = $remote_port;
    return true;
  }

  /**
  * get capabilities from remote server
  */
  function getCapabilities ( $domain = null )
  {
    if ( $domain != null && $this->domain != $domain )
      $this->domain = $domain;
    if ( ! isset ( $this->remote_server ) && isset ( $this->domain ) && ! $this->getServer ( ) )
      return false;
    $this->remote_url = 'http'. ( $this->remote_ssl ? 's' : '' ) . '://' .
      $this->remote_server . ':' . $this->remote_port . '/.well-known/ischedule';
    $remote_capabilities = file_get_contents ( $this->remote_url . '?query=capabilities' );
    if ( $remote_capabilities === false )
      return false;
    $xml_parser = xml_parser_create_ns('UTF-8');
    $this->xml_tags = array();
    xml_parser_set_option ( $xml_parser, XML_OPTION_SKIP_WHITE, 1 );
    xml_parser_set_option ( $xml_parser, XML_OPTION_CASE_FOLDING, 0 );
    $rc = xml_parse_into_struct( $xml_parser, $remote_capabilities, $this->xml_tags );
    if ( $rc == false ) {
      dbg_error_log( 'ERROR', 'XML parsing error: %s at line %d, column %d',
                  xml_error_string(xml_get_error_code($xml_parser)),
                  xml_get_current_line_number($xml_parser), xml_get_current_column_number($xml_parser) );
      dbg_error_log('ischedule', $this->domain . ' iSchedule error parsing remote xml' );
      return false;
    }
    xml_parser_free($xml_parser);
    $xmltree = BuildXMLTree( $this->xml_tags );
    if ( !is_object($xmltree) ) {
      dbg_error_log('ischedule', $this->domain . ' iSchedule error in remote xml' );
      $request->DoResponse( 406, translate("REPORT body is not valid XML data!") );
      return false;
    }
    dbg_error_log('ischedule', $this->domain . ' got capabilites' );
    $this->capabilities_xml = $xmltree;
    return true;
  }

  /**
  *  query capabilities retrieved from server
  */
  function queryCapabilities ( $capability, $domain = null )
  {
    if ( ! isset ( $this->capabilities_xml ) )
    {
      dbg_error_log('ischedule', $this->domain . ' capabilities not set, quering for capability:' . $capability );
      if ( $domain == null )
        return false;
      if ( $this->domain != $domain )
        $this->domain = $domain;
      if ( ! $this->getCapabilities ( ) )
        return false;
    }
    switch ( $capability )
    {
      case 'VEVENT':
      case 'VFREEBUSY':
      case 'VTODO':
        $comp = $this->capabilities_xml->GetPath ( 'urn:ietf:params:xml:ns:ischedule:supported-scheduling-message-set/urn:ietf:params:xml:ns:ischedule:comp' );
        foreach ( $comp as $c )
        {
          if ( $c->GetAttribute ( 'name' ) == $capability )
            return true;
        }
        return false;
      case 'VFREEBUSY/REQUEST':
      case 'VTODO/ADD':
      case 'VTODO/REQUEST':
      case 'VTODO/REPLY':
      case 'VTODO/CANCEL':
      case 'VEVENT/ADD':
      case 'VEVENT/REQUEST':
      case 'VEVENT/REPLY':
      case 'VEVENT/CANCEL':
      case 'VEVENT/PUBLISH':
      case 'VEVENT/COUNTER':
      case 'VEVENT/DECLINECOUNTER':
        dbg_error_log('ischedule', $this->domain . ' xml query' );
        $comp = $this->capabilities_xml->GetPath ( 'urn:ietf:params:xml:ns:ischedule:supported-scheduling-message-set/urn:ietf:params:xml:ns:ischedule:comp' );
        list ( $component, $method ) = explode ( '/', $capability );
        dbg_error_log('ischedule', $this->domain . ' quering for capability:' . count ( $comp ) . ' ' . $component );
        foreach ( $comp as $c )
        {
          dbg_error_log('ischedule', $this->domain . ' quering for capability:' . $c->GetAttribute ( 'name' ) . ' == ' . $component );
          if ( $c->GetAttribute ( 'name' ) == $component )
          {
            $methods = $c->GetElements ( 'urn:ietf:params:xml:ns:ischedule:method' );
            if ( count ( $methods ) == 0 )
              return true; // seems like we should accept everything if there are no children
            foreach ( $methods as $m )
            {
              if ( $m->GetAttribute ( 'name' ) == $method )
                return true;
            }
          }
        }
        return false;
      default:
        return false;
    }
  }

  /**
  * signs a POST body and headers
  *
  * @param string $body the body of the POST
  * @param array  $headers the headers to sign as passed to header ();
  */
  function signDKIM ( $headers, $body )
  {
    if ( $this->scheduling_dkim_domain == null )
      return false;
    $b = '';
    if ( is_array ( $headers ) !== true )
      return false;
    foreach ( $headers as $key => $value )
    {
      $b .= $key . ': ' . $value . "\r\n";
    }
    $dk['v'] = '1';
    $dk['a'] = 'rsa-' . $this->scheduling_dkim_algo;
    $dk['s'] = $this->selector;
    $dk['d'] = $this->scheduling_dkim_domain;
    $dk['c'] = 'simple-http'; // implied canonicalization of simple-http/simple from rfc4871 Section-3.5
    if ( isset ( $_SERVER['SERVER_NAME'] ) && strstr ( $_SERVER['SERVER_NAME'], $this->domain ) !== false ) // don't use when testing
      $dk['i'] = '@' . $_SERVER['SERVER_NAME']; //optional
    $dk['q'] = 'dns/txt'; // optional, dns/txt is the default if missing
    $dk['l'] = strlen ( $body ); //optional
    $dk['t'] = time ( ); // timestamp of signature, optional
    if ( isset ( $this->valid_time ) )
      $dk['x'] = $this->valid_time; // unix timestamp expiriation of signature, optional
    $dk['h'] = implode ( ':', array_keys ( $headers ) );
    $dk['bh'] = base64_encode ( hash ( 'sha256', $body , true ) );
    $value = '';
    foreach ( $dk as $key => $val )
      $value .= "$key=$val; ";
    $value .= 'b=';
    $tosign = $b . 'DKIM-Signature: ' . $value;
    openssl_sign ( $tosign, $sig, $this->schedule_private_key, $this->scheduling_dkim_algo );
    $this->tosign = $tosign;
    $value .= base64_encode ( $sig );
    return $value;
  }

  /**
  * send request to remote server
  * $address should be an email address or an array of email addresses all with the same domain
  * $type should be in the format COMPONENT/METHOD eg (VFREEBUSY, VEVENT/REQUEST, VEVENT/REPLY, etc. )
  * $data is the vcalendar data N.B. must already be rendered into text format
  */
  function sendRequest ( $address, $type, $data )
  {
    global $session;
    if ( empty($this->scheduling_dkim_domain) )
      return false;
    if ( is_array ( $address ) )
      list ( $user, $domain ) = explode ( '@', $address[0] );
    else
      list ( $user, $domain ) = explode ( '@', $address );
    if ( ! $this->getCapabilities ( $domain ) )
    {
      dbg_error_log('ischedule', $domain . ' did not have iSchedule capabilities for ' . $type );
      return false;
    }
    dbg_error_log('ischedule', $domain . ' trying with iSchedule capabilities for ' . $type );
    if ( $this->queryCapabilities ( $type ) )
    {
      dbg_error_log('ischedule', $domain . ' trying with iSchedule capabilities for ' . $type . ' OK');
      list ( $component, $method ) = explode ( '/', $type );
      $headers = array ( );
      $headers['iSchedule-Version'] = '1.0';
      $headers['Originator'] = 'mailto:' . $session->email;
      if ( is_array ( $address ) )
        $headers['Recipient'] = implode ( ', ' , $address );
      else
        $headers['Recipient'] = $address;
      $headers['Content-Type'] = 'text/calendar; component=' . $component ;
      if ( $method )
        $headers['Content-Type'] .= '; method=' . $method;
      $headers['DKIM-Signature'] = $this->signDKIM ( $headers, $body );
      if ( $headers['DKIM-Signature'] == false )
        return false;
      $request_headers = array ( );
      foreach ( $headers as $k => $v )
        $request_headers[] = $k . ': ' . $v;
      $curl = curl_init ( $this->remote_url );
      curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, true );
      curl_setopt ( $curl, CURLOPT_HTTPHEADER, array() ); // start with no headers set
      curl_setopt ( $curl, CURLOPT_HTTPHEADER, $request_headers );
      curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt ( $curl, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt ( $curl, CURLOPT_POST, 1);
      curl_setopt ( $curl, CURLOPT_POSTFIELDS, $data);
      curl_setopt ( $curl, CURLOPT_CUSTOMREQUEST, 'POST' );
      $xmlresponse = curl_exec ( $curl );
      $info = curl_getinfo ( $curl );
      curl_close ( $curl );
      if ( $info['http_code'] >= 400 )
      {
        dbg_error_log ( 'ischedule', 'remote server returned error (%s)', $info['http_code'] );
        return false;
      }

      error_log ( 'remote response '. $xmlresponse . print_r ( $info, true ) );
      $xml_parser = xml_parser_create_ns('UTF-8');
      $xml_tags = array();
      xml_parser_set_option ( $xml_parser, XML_OPTION_SKIP_WHITE, 1 );
      xml_parser_set_option ( $xml_parser, XML_OPTION_CASE_FOLDING, 0 );
      $rc = xml_parse_into_struct( $xml_parser, $xmlresponse, $xml_tags );
      if ( $rc == false ) {
        dbg_error_log( 'ERROR', 'XML parsing error: %s at line %d, column %d',
                    xml_error_string(xml_get_error_code($xml_parser)),
                    xml_get_current_line_number($xml_parser), xml_get_current_column_number($xml_parser) );
        return false;
      }
      $xmltree = BuildXMLTree( $xml_tags );
      xml_parser_free($xml_parser);
      if ( !is_object($xmltree) ) {
        dbg_error_log( 'ERROR', 'iSchedule RESPONSE body is not valid XML data!' );
        return false;
      }
      $resp = $xmltree->GetPath ( '/*/urn:ietf:params:xml:ns:ischedule:response' );
      $result = array();
      foreach ( $resp as $r )
      {
        $recipient     = $r->GetElements ( 'urn:ietf:params:xml:ns:ischedule:recipient' );
        $status        = $r->GetElements ( 'urn:ietf:params:xml:ns:ischedule:request-status' );
        $calendardata  = $r->GetElements ( 'urn:ietf:params:xml:ns:ischedule:calendar-data' );
        if ( count ( $recipient ) < 1 )
          continue; // this should be an error
        if ( count ( $calendardata ) > 0 )
        {
          $result [ $recipient[0]->GetContent() ] = $calendardata[0]->GetContent();
        }
        else
        {
          $result [ $recipient[0]->GetContent() ] = $status[0]->GetContent();
        }
      }
      if ( count ( $result ) < 1 )
        return false;
      else
        return $result;
    }
    else
      return false;
  }

  /**
  * parses and validates DK header
  *
  * @param string $sig the value of the DKIM-Signature header
  */
  function parseDKIM ( $sig )
  {

    $this->failed = true;
    $tags = preg_split ( '/;[\s\t]/', $sig );
    foreach ( $tags as $v )
    {
      list($key,$value) = preg_split ( '/=/', $v, 2 );
      $dkim[$key] = $value;
    }
    // the canonicalization method is currently undefined as of draft-01 of the iSchedule spec
    // but it does define the value, it should be simple-http.  RFC4871 also defines two methods
    // simple and relaxed, simple is probably the same as simple http
    // relaxed allows for header case folding and whitespace folding, see section 3.4.4 of RFC4871
    if ( ! preg_match ( '{(simple|simple-http|relaxed)(/(simple|simple-http|relaxed))?}', $dkim['c'], $matches ) ) // canonicalization method
      return 'bad canonicalization:' . $dkim['c'] ;
    if ( count ( $matches ) > 2 )
      $this->body_cannon = $matches[2];
    else
      $this->body_cannon = $matches[1];
    $this->header_cannon = $matches[1];
    // signing algorythm REQUIRED
    if ( $dkim['a'] != 'rsa-sha1' && $dkim['a'] != 'rsa-sha256' ) // we only support the minimum required
      return 'bad signing algorythm:' . $dkim['a'] ;
    // query method to retrieve public key, could/should we add https to the spec?  REQUIRED
    if ( $dkim['q'] != 'dns/txt' )
      return 'bad query method';
    // domain of the signing entity REQUIRED
    if ( ! isset ( $dkim['d'] ) )
      return 'missing signing domain';
    $this->remote_server = $dkim['d'];
    // identity of signing AGENT, OPTIONAL
    if ( isset ( $dkim['i'] ) )
    {
      // if present, domain of the signing agent must be a match or a subdomain of the signing domain
      if ( ! stristr ( $dkim['i'], $dkim['d'] ) ) // RFC4871 does not specify a case match requirement
        return 'signing domain mismatch';
      // grab the local part of the signing agent if it's an email address
      if ( strstr ( $dkim [ 'i' ], '@' ) )
        $this->remote_user = substr ( $dkim [ 'i' ], 0, strpos ( $dkim [ 'i' ], '@' ) - 1 );
    }
    // selector used to retrieve public key REQUIRED
    if ( ! isset ( $dkim['s'] ) )
      return 'missing selector';
    $this->remote_selector = $dkim['s'];
    // signed header fields, colon seperated  REQUIRED
    if ( ! isset ( $dkim['h'] ) )
      return 'missing list of signed headers';
    $this->signed_headers = preg_split ( '/:/', $dkim['h'] );

    $sh = Array ();
    foreach ( $this->signed_headers as $h )
    {
      $sh[] = strtolower ( $h );
      if ( in_array ( strtolower ( $h ), $this->disallowed_headers ) )
        return "$h is NOT allowed in signed header fields per RFC4871 or iSchedule";
    }
    foreach ( $this->required_headers as $h )
      if ( ! in_array ( strtolower ( $h ), $sh ) )
        return "$h is REQUIRED but missing in signed header fields per iSchedule";
    // body hash REQUIRED
    if ( ! isset ( $dkim['bh'] ) )
      return 'missing body signature';
    // signed header hash REQUIRED
    if ( ! isset ( $dkim['b'] ) )
      return 'missing signature in b field';
    // length of body used for signing
    if ( isset ( $dkim['l'] ) )
      $this->signed_length = $dkim['l'];
    $this->failed = false;
    $this->DKSig = $dkim;
    return true;
  }

  /**
  * split up a mailto uri into domain and user components
  * TODO handle other uri types (eg http)
  */
  function parseURI ( $uri )
  {
    if ( preg_match ( '/^mailto:([^@]+)@([^\s\t\n]+)/', $uri, $matches ) )
    {
      $this->remote_user = $matches[1];
      $this->domain = $matches[2];
    }
    else
      return false;
  }

  /**
  * verifies parsed DKIM header is valid for current message with a signature from the public key in DNS
  * TODO handle multiple headers of the same name
  */
  function verifySignature ( )
  {
    global $request,$c;
    $this->failed = true;
    $signed = '';
    foreach ( $this->signed_headers as $h )
      if ( isset ( $_SERVER['HTTP_' . strtoupper ( strtr ( $h, '-', '_' ) ) ] ) )
        $signed .= "$h: " . $_SERVER['HTTP_' . strtoupper ( strtr ( $h, '-', '_' ) ) ] . "\r\n";
      else
        $signed .= "$h: " . $_SERVER[ strtoupper ( strtr ( $h, '-', '_' ) ) ] . "\r\n";
    if ( ! isset ( $_SERVER['HTTP_ORIGINATOR'] ) || stripos ( $signed, 'Originator' ) === false ) //required header, must be signed
      return "missing Originator";
    if ( ! isset ( $_SERVER['HTTP_RECIPIENT'] ) || stripos ( $signed, 'Recipient' ) === false ) //required header, must be signed
      return "missing Recipient";
    if ( ! isset ( $_SERVER['HTTP_ISCHEDULE_VERSION'] ) || $_SERVER['HTTP_ISCHEDULE_VERSION'] != '1' ) //required header and we only speak version 1 for now
      return "missing or mismatch ischedule-version header";
    $body = $request->raw_post;
    if ( ! isset ( $this->signed_length ) ) // Should we use the Content-Length header if the signed length is missing?
      $this->signed_length = strlen ( $body );
    else
      $body = substr ( $body, 0, $this->signed_length );
    if ( isset ( $this->remote_user_rule ) )
      if ( $this->remote_user_rule != '*' && ! stristr ( $this->remote_user, $this->remote_user_rule ) )
        return "remote user rule failure";
    $hash_algo = preg_replace ( '/^.*(sha1|sha256).*/','$1', $this->DKSig['a'] );
    $body_hash = base64_encode ( hash ( $hash_algo, $body , true ) );
    if ( $this->DKSig['bh'] != $body_hash )
      return "body hash mismatch";
    $sig = $_SERVER['HTTP_DKIM_SIGNATURE'];
    $sig = preg_replace ( '/ b=[^;\s\r\n\t]+/', ' b=', $sig );
    $signed .= 'DKIM-Signature: ' . $sig;
    $verify = openssl_verify ( $signed, base64_decode ( $this->DKSig['b'] ), $this->remote_public_key, $hash_algo );
    if (  $verify != 1 )
    {
      openssl_sign ( $signed, $sigb, $this->schedule_private_key, $hash_algo );
      $sigc = base64_encode ( $sigb );
      $verify1 = openssl_verify ( $signed, $sigc, $this->remote_public_key, $hash_algo );
      return "signature verification failed " . $this->remote_public_key . " \n\n". $sig . " \n" . $hash_algo . "\n". print_r ($verify,1) . " XX " . $verify1 . "\n";
    }
    $this->failed = false;
    return true;
  }

  /**
  * checks that current request has a valid DKIM signature signed by a currently valid key from DNS
  */
  function validateRequest ( )
  {
    global $request;
    if ( isset ( $_SERVER['HTTP_DKIM_SIGNATURE'] ) )
      $sig = $_SERVER['HTTP_DKIM_SIGNATURE'];
    else
    {
      $request->DoResponse( 403, translate('DKIM signature missing') );
      return false;
    }
    if ( isset ( $_SERVER['HTTP_ORGANIZER'] ) )
      $request->DoResponse( 403, translate('Organizer Missing') );

    dbg_error_log ('ischedule','beginning validation');
    $err = $this->parseDKIM ( $sig );
    if ( $err !== true || $this->failed )
      $request->DoResponse( 412, 'DKIM signature invalid ' . "\n" . $err . "\n" );
    if ( ! $this->getTxt () || $this->failed ) // this could also be a 424 failed dependency response
      $request->DoResponse( 400, translate('DKIM signature validation failed(DNS ERROR)') );
    if ( ! $this->parseTxt () || $this->failed )
      $request->DoResponse( 400, translate('DKIM signature validation failed(KEY Parse ERROR)') );
    if ( ! $this->validateKey () || $this->failed )
      $request->DoResponse( 400, translate('DKIM signature validation failed(KEY Validation ERROR)') );
    $err = $this->verifySignature ();
    if ( $err !== true || $this->failed )
      $request->DoResponse( 412, translate('DKIM signature validation failed(Signature verification ERROR)') . '\n' . $err );
    dbg_error_log ('ischedule','signature ok');
    return true;
  }
}

