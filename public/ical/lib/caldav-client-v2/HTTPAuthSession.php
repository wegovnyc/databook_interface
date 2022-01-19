<?php
/**
* A Class for handling HTTP Authentication
*
* @package davical
* @subpackage HTTPAuthSession
* @author Andrew McMillan <andrew@catalyst.net.nz>
* @copyright Catalyst .Net Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2
*/

/**
* A Class for handling a session using HTTP Basic Authentication
*
* @package davical
*/
class HTTPAuthSession {
  /**#@+
  * @access private
  */

  /**
  * Username
  * @var username string
  */
  public $username;

  /**
  * User ID number
  * @var user_no int
  */
  public $user_no;

  /**
  * Principal ID
  * @var principal_id int
  */
  public $principal_id;

  /**
  * User e-mail
  * @var email string
  */
  public $email;

  /**
  * User full name
  * @var fullname string
  */
  public $fullname;

  /**
  * Group rights (not implemented)
  * @todo
  * @var groups array
  */
  public $groups;
  /**#@-*/

  /**
  * The constructor, which just calls the type supplied or configured
  */
  function HTTPAuthSession() {
    global $c;

    if ( ! empty($_SERVER['PHP_AUTH_DIGEST'])) {
      $this->DigestAuthSession();
    }
    else if ( isset($_SERVER['PHP_AUTH_USER']) || isset($_SERVER["AUTHORIZATION"]) ) {
      $this->BasicAuthSession();
    }
    else if ( isset($c->http_auth_mode) && $c->http_auth_mode == "Digest" ) {
      $this->DigestAuthSession();
    }
    else {
      $this->BasicAuthSession();
    }
  }

  /**
  * Authorisation failed, so we send some headers to say so.
  *
  * @param string $auth_header The WWW-Authenticate header details.
  */
  function AuthFailedResponse( $auth_header = "" ) {
    global $c;
    if ( $auth_header == "" ) {
      $auth_realm = $c->system_name;
      if ( isset($c->per_principal_realm) && $c->per_principal_realm && !empty($_SERVER['PATH_INFO']) ) {
        $principal_name = preg_replace( '{^/(.*?)/.*$}', '$1', $_SERVER['PATH_INFO']);
        if ( $principal_name != $_SERVER['PATH_INFO'] ) {
          $auth_realm .= ' - ' . $principal_name;
        }
      }
      dbg_error_log( "HTTPAuth", ":AuthFailedResponse Requesting authentication in the '%s' realm", $auth_realm );
      $auth_header = sprintf( 'WWW-Authenticate: Basic realm="%s"', $auth_realm );
    }

    header('HTTP/1.1 401 Unauthorized', true, 401 );
    header('Content-type: text/plain; ; charset="utf-8"' );
    header( $auth_header );
    echo 'Please log in for access to this system.';
    dbg_error_log( "HTTPAuth", ":Session: User is not authorised: %s ", $_SERVER['REMOTE_ADDR'] );
    @ob_flush();   exit(0);
  }


  /**
  * Handle Basic HTTP Authentication (not secure unless https)
  */
  function BasicAuthSession() {
    global $c;

    /**
    * Get HTTP Auth to work with PHP+FastCGI
    */
    if ( !isset($_SERVER['AUTHORIZATION']) && isset($_SERVER['HTTP_AUTHORIZATION']) && !empty($_SERVER['HTTP_AUTHORIZATION']))
      $_SERVER['AUTHORIZATION'] = $_SERVER['HTTP_AUTHORIZATION'];
    if (isset($_SERVER['AUTHORIZATION']) && !empty($_SERVER['AUTHORIZATION'])) {
      list ($type, $cred) = explode(" ", $_SERVER['AUTHORIZATION']);
      if ($type == 'Basic') {
        list ($user, $pass) = explode(":", base64_decode($cred), 2);
        $_SERVER['PHP_AUTH_USER'] = $user;
        $_SERVER['PHP_AUTH_PW'] = $pass;
      }
    }
    else if ( isset($c->authenticate_hook['server_auth_type'])
              && (  ( isset($_SERVER["REMOTE_USER"]) && !empty($_SERVER["REMOTE_USER"]) )  ||
                    ( isset($_SERVER["REDIRECT_REMOTE_USER"]) && !empty($_SERVER["REDIRECT_REMOTE_USER"]) )  )   ) {
      if ( ( is_array($c->authenticate_hook['server_auth_type'])
                    && in_array( strtolower($_SERVER['AUTH_TYPE']), array_map('strtolower', $c->authenticate_hook['server_auth_type'])) )
         ||
           ( !is_array($c->authenticate_hook['server_auth_type'])
                    && strtolower($c->authenticate_hook['server_auth_type']) == strtolower($_SERVER['AUTH_TYPE']) )
         ) {
        /**
        * The authentication has happened in the server, and we should accept it.
        */
        if (isset($_SERVER["REMOTE_USER"]))
          $_SERVER['PHP_AUTH_USER'] = $_SERVER['REMOTE_USER'];
        else
          $_SERVER['PHP_AUTH_USER'] = $_SERVER['REDIRECT_REMOTE_USER'];
        $_SERVER['PHP_AUTH_PW'] = 'Externally Authenticated';
        if ( ! isset($c->authenticate_hook['call']) ) {
          /**
          * Since we still need to get the user's details from somewhere.  We change the default
          * authentication hook to auth_external which simply retrieves a user row from the DB
          * and does no password checking.
          */
          $c->authenticate_hook['call'] = 'auth_external';
        }
      }
    }


    /**
    * Fall through to the normal PHP authentication variables.
    */
    if ( isset($_SERVER['PHP_AUTH_USER']) ) {
      if ( $p = $this->CheckPassword( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) ) {
        if ( isset($p->active) && !isset($p->user_active) ) {
          trace_bug('Some authentication failed to return a dav_principal record and needs fixing.');
          $p->user_active = $p->active;
        }

        /**
         * Maybe some external authentication didn't return false for an inactive
         * user, so we'll be pedantic here.
         */
        if ( $p->user_active ) {
          $this->AssignSessionDetails($p);
          return;
        }
      }
    }

    if ( isset($c->allow_unauthenticated) && $c->allow_unauthenticated ) {
      $this->AssignSessionDetails('unauthenticated');
      $this->logged_in = false;
      return;
    }

    $this->AuthFailedResponse();
    // Does not return
  }


  /**
  * Handle Digest HTTP Authentication (no passwords were harmed in this transaction!)
  *
  * Note that this will not actually work, unless we can either:
  *   (A) store the password plain text in the database
  *   (B) store an md5( username || realm || password ) in the database
  *
  * The problem is that potentially means that the administrator can collect the sorts
  * of things people use as passwords.  I believe this is quite a bad idea.  In scenario (B)
  * while they cannot see the password itself, they can see a hash which only varies when
  * the password varies, so can see when two users have the same password, or can use
  * some of the reverse lookup sites to attempt to reverse the hash.  I think this is a
  * less bad idea, but not ideal.  Probably better than running Basic auth of HTTP though!
  */
  function DigestAuthSession() {
    global $c;

    $realm = $c->system_name;
    $opaque = $realm;
    if ( isset($_SERVER['HTTP_USER_AGENT']) ) $opaque .= $_SERVER['HTTP_USER_AGENT'];
    if ( isset($_SERVER['REMOTE_ADDR']) )     $opaque .= $_SERVER['REMOTE_ADDR'];
    $opaque = sha1($opaque);

    if ( ! empty($_SERVER['PHP_AUTH_DIGEST'])) {
      // analyze the PHP_AUTH_DIGEST variable
      if ( $data = $this->ParseDigestHeader($_SERVER['PHP_AUTH_DIGEST']) ) {

        if ( $data['uri'] != $_SERVER['REQUEST_URI'] ) {
          dbg_error_log( "ERROR", " DigestAuth: WTF! URI is '%s' and request URI is '%s'!?!" );
          $this->AuthFailedResponse();
          // Does not return
        }

        // generate the valid response
        $test_user = new Principal('username', $data['username']);

        if ( preg_match( '{\*(Digest)?\*(.*)}', $test_user->password, $matches ) ) {
          if ( $matches[1] == 'Digest' )
            $A1 = $matches[2];
          else {
//            dbg_error_log( "HTTPAuth", "Constructing A1 from md5(%s:%s:%s)", $data['username'], $realm, $matches[2] );
            $A1 = md5($data['username'] . ':' . $realm . ':' . $matches[2]);
          }
          $A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
          $auth_string = $A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2;
//          dbg_error_log( "HTTPAuth", "DigestAuthString: %s", $auth_string);
          $valid_response = md5($auth_string);
//          dbg_error_log( "HTTPAuth", "DigestResponse: %s", $valid_response);

          if ( $data['response'] == $valid_response ) {
            $this->AssignSessionDetails($test_user);
//            dbg_error_log( "HTTPAuth", "Success!!!" );
            return;
          }
        }
        else {
          // Their account is not configured for Digest auth so we need to use Basic.
          $this->AuthFailedResponse();
          // Does not return
        }
      }
    }

    $nonce = sha1(uniqid('',true));
    $authheader = sprintf('WWW-Authenticate: Digest realm="%s", qop="auth", nonce="%s", opaque="%s", algorithm="MD5"',
                                     $realm, $nonce, $opaque );
    dbg_error_log( "HTTPAuth", $authheader );
    $this->AuthFailedResponse( $authheader );
    // Does not return
  }


  /**
  * Parse the HTTP Digest Auth Header
  *  - largely sourced from the PHP documentation
  */
  function ParseDigestHeader($auth_header) {
    // protect against missing data
    $needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
    $data = array();

    preg_match_all('{(\w+)="([^"]+)"}', $auth_header, $matches, PREG_SET_ORDER);
    foreach ($matches as $m) {
//      dbg_error_log( "HTTPAuth", 'Match: "%s"', $m[0] );
      $data[$m[1]] = $m[2];
      unset($needed_parts[$m[1]]);
      dbg_error_log( "HTTPAuth", 'Received: %s: %s', $m[1], $m[2] );
    }

    preg_match_all('{(\w+)=([^" ,]+)}', $auth_header, $matches, PREG_SET_ORDER);
    foreach ($matches as $m) {
//      dbg_error_log( "HTTPAuth", 'Match: "%s"', $m[0] );
      $data[$m[1]] = $m[2];
      unset($needed_parts[$m[1]]);
      dbg_error_log( "HTTPAuth", 'Received: %s: %s', $m[1], $m[2] );
    }


    @dbg_error_log( "HTTPAuth", 'Received: nonce: %s, nc: %s, cnonce: %s, qop: %s, username: %s, uri: %s, response: %s',
        $data['nonce'], $data['nc'], $data['cnonce'], $data['qop'], $data['username'], $data['uri'], $data['response']
      );
    return $needed_parts ? false : $data;
  }


  /**
  * CheckPassword does all of the password checking and
  * returns a user record object, or false if it all ends in tears.
  */
  function CheckPassword( $username, $password ) {
    global $c;

    if(isset($c->login_append_domain_if_missing) && $c->login_append_domain_if_missing && !preg_match('/@/',$username))
      $username.='@'.$c->domain_name;

    if ( !isset($c->authenticate_hook) || !isset($c->authenticate_hook['call'])
                      || !function_exists($c->authenticate_hook['call'])
                      || (isset($c->authenticate_hook['optional']) && $c->authenticate_hook['optional']) )
    {
      if ( $principal = new Principal('username', $username) ) {
        if ( isset($c->dbg['password']) ) dbg_error_log( "password", ":CheckPassword: Name:%s, Pass:%s, File:%s, Active:%s", $username, $password, $principal->password, ($principal->user_active?'Yes':'No') );
        if ( $principal->user_active && session_validate_password( $password, $principal->password ) ) {
          return $principal;
        }
      }
    }

    if ( isset($c->authenticate_hook) && isset($c->authenticate_hook['call']) && function_exists($c->authenticate_hook['call']) ) {
      /**
      * The authenticate hook needs to:
      *   - Accept a username / password
      *   - Confirm the username / password are correct
      *   - Create (or update) a 'usr' record in our database
      *   - Return the 'usr' record as an object
      *   - Return === false when authentication fails
      *
      * It can expect that:
      *   - Configuration data will be in $c->authenticate_hook['config'], which might be an array, or whatever is needed.
      */
      $principal = call_user_func( $c->authenticate_hook['call'], $username, $password );
      if ( $principal !== false && !($principal instanceof Principal) ) {
        $principal = new Principal('username', $username);
      }
      return $principal;
    }

    return false;
  }


  /**
  * Checks whether a user is allowed to do something.
  *
  * The check is performed to see if the user has that role.
  *
  * @param string $whatever The role we want to know if the user has.
  * @return boolean Whether or not the user has the specified role.
  */
  function AllowedTo ( $whatever ) {
    return ( isset($this->logged_in) && $this->logged_in && isset($this->roles[$whatever]) && $this->roles[$whatever] );
  }


  /**
  * Internal function used to get the user's roles from the database.
  */
  function GetRoles () {
    $this->roles = array();
    $qry = new AwlQuery( 'SELECT role_name FROM role_member m join roles r ON r.role_no = m.role_no WHERE user_no = :user_no ',
                                array( ':user_no' => $this->user_no) );
    if ( $qry->Exec('BasicAuth') && $qry->rows() > 0 ) {
      while( $role = $qry->Fetch() ) {
        $this->roles[$role->role_name] = true;
      }
    }
  }


  /**
  * Internal function used to assign the session details to a user's new session.
  * @param object $u The user+session object we (probably) read from the database.
  */
  function AssignSessionDetails( $principal ) {
    if ( is_string($principal) ) $principal = new Principal('username',$principal);
    if ( get_class($principal) != 'Principal' ) {
      $principal = new Principal('username',$principal->username);
    }

    // Assign each field in the selected record to the object
    foreach( $principal AS $k => $v ) {
      $this->{$k} = $v;
    }
    if ( !get_class($principal) == 'Principal' ) {
      throw new Exception('HTTPAuthSession::AssignSessionDetails could not find a Principal object');
    }
    $this->username = $principal->username();
    $this->user_no  = $principal->user_no();
    $this->principal_id = $principal->principal_id();
    $this->email = $principal->email();
    $this->fullname = $principal->fullname;
    $this->dav_name = $principal->dav_name();
    $this->principal = $principal;

    $this->GetRoles();
    $this->logged_in = true;
    if ( function_exists("awl_set_locale") && isset($this->locale) && $this->locale != "" ) {
      awl_set_locale($this->locale);
    }
  }


}

