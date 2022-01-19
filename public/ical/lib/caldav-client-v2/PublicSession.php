<?php
/**
* A Class for faking sessions which are anonymous access to a resource
*
* @package davical
* @subpackage PublicSession
* @author Andrew McMillan <andrew@morphoss.com>
* @copyright Morphoss Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

/**
* A Class for handling a public (anonymous) session
*
* @package davical
*/
class PublicSession {
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
  * Group rights
  * @var groups array
  */
  public $groups;
  /**#@-*/

  /**
  * The constructor, which just calls the actual type configured
  */
  function PublicSession() {
    global $c;

    $principal = new Principal('username','unauthenticated');

    // Assign each field in the selected record to the object
    foreach( $principal AS $k => $v ) {
      $this->{$k} = $v;
    }

    $this->username = $principal->username();
    $this->user_no  = $principal->user_no();
    $this->principal_id = $principal->principal_id();
    $this->email = $principal->email();
    $this->fullname = $principal->fullname;
    $this->dav_name = $principal->dav_name();
    $this->principal = $principal;

    if ( function_exists("awl_set_locale") && isset($this->locale) && $this->locale != "" ) {
      awl_set_locale($this->locale);
    }

    $this->groups = ( isset($c->public_groups) ? $c->public_groups : array() );
    $this->roles = array( 'Public' => true );
    $this->logged_in = false;
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
    dbg_error_log('session', 'Checking whether "Public" is allowed to "%s"', $whatever);
    return ( isset($this->roles[$whatever]) && $this->roles[$whatever] );
  }

}

