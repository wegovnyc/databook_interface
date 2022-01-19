<?php
/**
* Authentication against PAM with Squid
*
* @package   davical
* @category  Technical
* @subpackage authentication/drivers
* @author    Eric Seigne <eric.seigne@ryxeo.com>,
*            Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Eric Seigne
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

require_once("auth-functions.php");

/**
 * Plugin to authenticate with the help of Squid
 */
class squidPamDriver
{
  /**#@+
  * @access private
  */

  /**#@-*/


  /**
  * The constructor
  *
  * @param string $config path where /usr/lib/squid/pam_auth is
  */
  function __construct($config) {
      global $c;
      if (! file_exists($config)){
          $c->messages[] = sprintf(i18n( 'drivers_squid_pam : Unable to find %s file'), $config );
          $this->valid=false;
          return ;
      }
  }
}


/**
* Check the username / password against PAM using the Squid helper script
*/
function SQUID_PAM_check($username, $password ){
  global $c;

  $script = $c->authenticate_hook['config']['script'];
  if ( empty($script) ) $script = $c->authenticate_hook['config']['path'];
  $cmd = sprintf( 'echo %s %s | %s -n common-auth', escapeshellarg($username), escapeshellarg($password),
                                 $script);
  $auth_result = exec($cmd);
  if ( $auth_result == "OK") {
    dbg_error_log('PAM', 'User %s successfully authenticated', $username);
    $principal = new Principal('username',$username);
    if ( !$principal->Exists() ) {
      dbg_error_log('PAM', 'User %s does not exist in local db, creating', $username);
      $pwent = posix_getpwnam($username);
      $gecos = explode(',',$pwent['gecos']);
      $fullname = $gecos[0];
      $principal->Create( array(
                            'username' => $username,
                            'user_active' => 't',
                            'email' => sprintf('%s@%s', $username, $email_base),
                            'fullname' => $fullname
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
