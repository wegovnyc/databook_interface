<?php
/**
* Authentication against IMAP using the imap_open function
*
* @package   davical
* @category  Technical
* @subpackage authentication/drivers
* @author    Oliver Schulze <oliver@samera.com.py>,
*            Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Based on Eric Seigne script drivers_squid_pam.php
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

// The PHP interpreter will die quietly unless satisfied. This provides user feedback instead.
if (!function_exists('imap_open')) {
  die("drivers_rimap: php-imap required.");
}

require_once("auth-functions.php");

/**
 * Plugin to authenticate against IMAP
 */
class rimapPamDriver
{
  /**#@+
  * @access private
  */

  /**#@-*/


  /**
  * The constructor
  *
  * @param string $imap_url formated for imap_open()
  */
  function __construct($imap_url)
  {
      global $c;
      if (empty($imap_url)){
          $c->messages[] = sprintf(i18n('drivers_rimap : imap_url parameter not configured in /etc/davical/*-conf.php'));
          $this->valid=false;
          return ;
      }
  }
}


/**
* Check the username / password against the IMAP server, provision from username and email_base
*/
function RIMAP_check($username, $password ){
  global $c;

  $imap_username = $username;
  if ( function_exists('mb_convert_encoding') ) {
    $imap_username = mb_convert_encoding($imap_username, "UTF7-IMAP",mb_detect_encoding($imap_username));
  }
  else {
    $imap_username = imap_utf7_encode($imap_username);
  }

  //$imap_url = '{localhost:143/imap/notls}';
  //$imap_url = '{localhost:993/imap/ssl/novalidate-cert}';
  $imap_url = $c->authenticate_hook['config']['imap_url'];
  $auth_result = "ERR";

  $imap_stream = @imap_open($imap_url, $imap_username, $password, OP_HALFOPEN);
  //print_r(imap_errors());
  if ( $imap_stream ) {
    // disconnect
    imap_close($imap_stream);
    // login ok
    $auth_result = "OK";
  }

  if ( $auth_result == "OK") {
    $principal = new Principal('username',$username);
    if ( ! $principal->Exists() ) {
      dbg_error_log( "PAM", "Principal '%s' doesn't exist in local DB, we need to create it",$username );
      if ( strstr($username, '@') ) {
        $name_arr = explode('@', $username);
        $fullname = ucfirst(strtolower($name_arr[0]));
        $email = $username;
      }
      else {
        $fullname = ucfirst(strtolower($username));
        $email = $username . "@" . $c->authenticate_hook['config']['email_base'];
      }

      $principal->Create( array(
                      'username' => $username,
                      'user_active' => true,
                      'email' => $email,
                      'fullname' => ucfirst($fullname)
              ));
      if ( ! $principal->Exists() ) {
        dbg_error_log( "PAM", "Unable to create local principal for '%s'", $username );
        return false;
      }
      CreateHomeCollections($username);
      CreateDefaultRelationships($username);
    }
    return $principal;
  }
  else {
    dbg_error_log( "PAM", "User %s is not a valid username (or password was wrong)", $username );
    return false;
  }

}
