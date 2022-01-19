<?php
/**
* The authentication handling plugins can be used by the Session class to
* provide authentication.
*
* Each authenticate hook needs to:
*   - Accept a username / password
*   - Confirm the username / password are correct
*   - Create (or update) a 'usr' record in our database
*   - Return the 'usr' record as an object
*   - Return === false when authentication fails
*
* It can expect that:
*   - Configuration data will be in $c->authenticate_hook['config'], which might be an array, or whatever is needed.
*
* In order to be called:
*   - This file should be included
*   - $c->authenticate_hook['call'] should be set to the name of the plugin
*   - $c->authenticate_hook['config'] should be set up with any configuration data for the plugin
*
* @package   davical
* @subpackage   authentication
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst IT Ltd, Morphoss Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

require_once("DataUpdate.php");

if ( !function_exists('auth_functions_deprecated') ) {
  /**
   * Warn about deprecated auth functions
   */
  function auth_functions_deprecated( $method, $message = null ) {
      $stack = debug_backtrace();
      array_shift($stack);
      dbg_error_log("ERROR", " auth-functions: Call to deprecated routine '%s'%s", $method, (isset($message)?': '.$message:'') );
      foreach( $stack AS $k => $v ) {
        dbg_error_log( 'ERROR', ' auth-functions: Deprecated call from line %4d of %s', $v['line'], $v['file']);
      }
  }
}

/**
 * @deprecated
 */
function getUserByName( $username, $use_cache=true ) {
  auth_functions_deprecated('getUserByName','replaced by Principal class');
  return new Principal('username', $username, $use_cache);
}

/**
 * @deprecated
 */
function getUserByEMail( $email, $use_cache = true ) {
  auth_functions_deprecated('getUserByEMail','replaced by Principal class');
  return new Principal('email', $email, $use_cache);
}

/**
 * @deprecated
 */
function getUserByID( $user_no, $use_cache = true ) {
  auth_functions_deprecated('getUserByID','replaced by Principal class');
  return new Principal('user_no', $user_no, $use_cache);
}

/**
 * @deprecated
 */
function getPrincipalByID( $principal_id, $use_cache = true ) {
  auth_functions_deprecated('getPrincipalByID','replaced by Principal class');
  return new Principal('principal_id', $principal_id, $use_cache);
}


/**
* Creates some default home collections for the user.
* @param string $username The username of the user we are creating relationships for.
*/
function CreateHomeCollections( $username, $defult_timezone = null ) {
  global $session, $c;

  if ( !isset($c->default_collections) )
  {
    $c->default_collections = array();

    if( !empty($c->home_calendar_name) )
      $c->default_collections[] = array( 'type' => 'calendar', 'name' => $c->home_calendar_name );
    if( !empty($c->home_addressbook_name) )
      $c->default_collections[] = array( 'type' => 'addressbook', 'name' => $c->home_addressbook_name );
  }

  if ( !is_array($c->default_collections) || !count($c->default_collections) ) return true;

  $principal = new Principal('username',$username);

  $user_fullname = $principal->fullname;  // user fullname
  $user_rfullname = implode(' ', array_reverse(explode(' ', $principal->fullname)));  // user fullname in reverse order

  $sql = 'INSERT INTO collection (user_no, parent_container, dav_name, dav_etag, dav_displayname, is_calendar, is_addressbook, default_privileges, created, modified, resourcetypes) ';
  $sql .= 'VALUES( :user_no, :parent_container, :collection_path, :dav_etag, :displayname, :is_calendar, :is_addressbook, :privileges::BIT(24), current_timestamp, current_timestamp, :resourcetypes );';

  foreach( $c->default_collections as $v ) {
    if ( $v['type'] == 'calendar' || $v['type']=='addressbook' ) {
      if ( !empty($v['name']) ) {
        $qry = new AwlQuery( 'SELECT 1 FROM collection WHERE dav_name = :dav_name', array( ':dav_name' => $principal->dav_name().$v['name'].'/') );
        if ( !$qry->Exec() ) {
          $c->messages[] = i18n('There was an error reading from the database.');
          return false;
        }
        if ( $qry->rows() > 0 ) {
          $c->messages[] = i18n('Home '.( $v['type']=='calendar' ? 'calendar' : 'addressbook' ).' already exists.');
          return true;
        }
        else {
          $params[':user_no'] = $principal->user_no();
          $params[':parent_container'] = $principal->dav_name();
          $params[':dav_etag'] = '-1';
          $params[':collection_path'] = $principal->dav_name().$v['name'].'/';
          $params[':displayname'] = ( !isset($v['displayname']) || empty($v['displayname']) ? $user_fullname.( $v['type']=='calendar' ? ' calendar' : ' addressbook' ) : str_replace(array('%fn', '%rfn'), array($user_fullname, $user_rfullname), $v['displayname']) );
          $params[':resourcetypes'] = ( $v['type']=='calendar' ? '<DAV::collection/><urn:ietf:params:xml:ns:caldav:calendar/>' : '<DAV::collection/><urn:ietf:params:xml:ns:carddav:addressbook/>' );
          $params[':is_calendar'] = ( $v['type']=='calendar' ? true : false );
          $params[':is_addressbook'] = ( $v['type']=='addressbook' ? true : false );
          $params[':privileges'] = ( !isset($v['privileges']) || $v['privileges']===null ? null : privilege_to_bits($v['privileges']) );

          $qry = new AwlQuery( $sql, $params );
          if ( $qry->Exec() ) {
            $c->messages[] = i18n('Home '.( $v['type']=='calendar' ? 'calendar' : 'addressbook' ).' added.');
            dbg_error_log("User",":Write: Created user's home ".( $v['type']=='calendar' ? 'calendar' : 'addressbook' )." at '%s'", $params[':collection_path'] );

            // create value for urn:ietf:params:xml:ns:caldav:supported-calendar-component-set property
            if($v['type'] == 'calendar' && isset($v['calendar_components']) && $v['calendar_components'] != null && is_array($v['calendar_components']) && count($v['calendar_components'])) {
                // convert the array to uppercase and allow only real calendar compontents
                $components_clean=array_intersect(array_map("strtoupper", $v['calendar_components']), array('VEVENT', 'VTODO', 'VJOURNAL', 'VTIMEZONE', 'VFREEBUSY', 'VPOLL', 'VAVAILABILITY'));

                // convert the $components_clean array to XML string
                $result_xml='';
                foreach($components_clean as $curr)
                    $result_xml.=sprintf('<comp name="%s" xmlns="urn:ietf:params:xml:ns:caldav"/>', $curr);

                // handle the components XML string as user defined property (see below)
                if($result_xml!='')
                    $v['default_properties']['urn:ietf:params:xml:ns:caldav:supported-calendar-component-set']=$result_xml;
            }

            // store all user defined properties (note: it also handles 'calendar_components' - see above)
            if(isset($v['default_properties']) && $v['default_properties'] != null && is_array($v['default_properties']) && count($v['default_properties'])) {
              $sql2='INSERT INTO property (dav_name, property_name, property_value, changed_on, changed_by) ';
              $sql2.='VALUES (:collection_path, :property_name, :property_value, current_timestamp, :user_no);';
              $params2[':user_no'] = $principal->user_no();
              $params2[':collection_path'] = $principal->dav_name().$v['name'].'/';

              foreach( $v['default_properties'] AS $key => $val ) {
                $params2[':property_name'] = $key;
                $params2[':property_value'] = $val;

                $qry2 = new AwlQuery( $sql2, $params2 );
                if ( $qry2->Exec() ) {
                  dbg_error_log("User",":Write: Created property '%s' for ".( $v['type']=='calendar' ? 'calendar' : 'addressbook' )." at '%s'", $params2[':property_name'], $params2[':collection_path'] );
                }
                else {
                  $c->messages[] = i18n("There was an error writing to the database.");
                  return false;
                }
              }
            }
          }
          else {
            $c->messages[] = i18n("There was an error writing to the database.");
            return false;
          }
        }
      }
    }
  }
  return true;
}

/**
 * @deprecated
 * @param string $username
 */
function CreateHomeCalendar($username) {
  auth_functions_deprecated('CreateHomeCalendar','renamed to CreateHomeCollections');
  return CreateHomeCollections($username);
}

/**
* Create default relationships.
* @param string $username The username of the user we are creating relationships for.
*/
function CreateDefaultRelationships( $username ) {
  global $c;
  if(! isset($c->default_relationships) || count($c->default_relationships) == 0) return true;

  $changes = false;
  $principal = new Principal('username', $username, true);
  foreach($c->default_relationships as $group => $relationships)
  {
    $sql = 'INSERT INTO grants (by_principal, to_principal, privileges) VALUES(:by_principal, :to_principal, :privileges::INT::BIT(24))';
    $params = array(
      ':by_principal' => $principal->principal_id,
      ':to_principal' => $group,
      ':privileges' => privilege_to_bits($relationships)
    );
    $qry = new AwlQuery($sql, $params);

    if ( $qry->Exec() ) {
      $changes = true;
      dbg_error_log("User",":Write: Created user's default relationship by:'%s', to:'%s', privileges:'%s'",$params[':by_principal'],$params[':to_principal'],$params[':privileges']);
    }
    else {
      $c->messages[] = i18n("There was an error writing to the database.");
      return false;
    }
  }

  if($changes)
    $c->messages[] = i18n("Default relationships added.");

  return true;
}

/**
 * Set a new timezone for a user's calendars
 * @param string $username
 * @param string $new_timezone
 */
function UpdateCollectionTimezones( $username, $new_timezone=null ) {
  if ( empty($new_timezone) ) return;
  $qry = new AwlQuery('UPDATE collection SET timezone=? WHERE dav_name LIKE ? AND is_calendar', '/'.$username.'/%', $new_timezone);
  $qry->Exec();
}

/**
* @deprecated
* @param object $usr The user details we read from the remote.
*/
function UpdateUserFromExternal( &$usr ) {
  global $c;

  auth_functions_deprecated('UpdateUserFromExternal','refactor to use the "Principal" class');
  /**
  * When we're doing the create we will usually need to generate a user number
  */
  if ( !isset($usr->user_no) || intval($usr->user_no) == 0 ) {
    $qry = new AwlQuery( "SELECT nextval('usr_user_no_seq');" );
    $qry->Exec('Login',__LINE__,__FILE__);
    $sequence_value = $qry->Fetch(true);  // Fetch as an array
    $usr->user_no = $sequence_value[0];
  }

  $qry = new AwlQuery('SELECT * FROM usr WHERE user_no = :user_no', array(':user_no' => $usr->user_no) );
  if ( $qry->Exec('Login',__LINE__,__FILE__) && $qry->rows() == 1 ) {
    $type = "UPDATE";
    if ( $old = $qry->Fetch() ) {
      $changes = false;
      foreach( $usr AS $k => $v ) {
        if ( $old->{$k} != $v ) {
          $changes = true;
          dbg_error_log("Login","User '%s' field '%s' changed from '%s' to '%s'", $usr->username, $k, $old->{$k}, $v );
          break;
        }
      }
      if ( !$changes ) {
        dbg_error_log("Login","No changes to user record for '%s' - leaving as-is.", $usr->username );
        if ( isset($usr->active) && $usr->active == 'f' ) return false;
        return; // Normal case, if there are no changes
      }
      else {
        dbg_error_log("Login","Changes to user record for '%s' - updating.", $usr->username );
      }
    }
  }
  else
    $type = "INSERT";

  $params = array();
  if ( $type != 'INSERT' ) $params[':user_no'] = $usr->user_no;
  $qry = new AwlQuery( sql_from_object( $usr, $type, 'usr', 'WHERE user_no= :user_no' ), $params );
  $qry->Exec('Login',__LINE__,__FILE__);

  /**
  * We disallow login by inactive users _after_ we have updated the local copy
  */
  if ( isset($usr->active) && ($usr->active === 'f' || $usr->active === false) ) return false;

  if ( $type == 'INSERT' ) {
    $qry = new AwlQuery( 'INSERT INTO principal( type_id, user_no, displayname, default_privileges) SELECT 1, user_no, fullname, :privs::INT::BIT(24) FROM usr WHERE username=(text(:username))',
                          array( ':privs' => privilege_to_bits($c->default_privileges), ':username' => $usr->username) );
    $qry->Exec('Login',__LINE__,__FILE__);
    CreateHomeCalendar($usr->username);
    CreateDefaultRelationships($usr->username);
  }
  else if ( $usr->fullname != $old->{'fullname'} ) {
    // Also update the displayname if the fullname has been updated.
    $qry->QDo( 'UPDATE principal SET displayname=:new_display WHERE user_no=:user_no',
                    array(':new_display' => $usr->fullname, ':user_no' => $usr->user_no)
             );
  }
}


/**
* Authenticate against a different PostgreSQL database which contains a usr table in
* the AWL format.
*
* Use this as in the following example config snippet:
*
* require_once('auth-functions.php');
*  $c->authenticate_hook = array(
*      'call'   => 'AuthExternalAwl',
*      'config' => array(
*           // A PgSQL database connection string for the database containing user records
*          'connection[]' => 'dbname=wrms host=otherhost port=5433 user=general',
*           // Which columns should be fetched from the database
*          'columns'    => "user_no, active, email_ok, joined, last_update AS updated, last_used, username, password, fullname, email",
*           // a WHERE clause to limit the records returned.
*          'where'    => "active AND org_code=7"
*      )
*  );
*
*/
function AuthExternalAWL( $username, $password ) {
  global $c;

  $persistent = isset($c->authenticate_hook['config']['use_persistent']) && $c->authenticate_hook['config']['use_persistent'];

  if ( isset($c->authenticate_hook['config']['columns']) )
    $cols = $c->authenticate_hook['config']['columns'];
  else
    $cols = '*';

  if ( isset($c->authenticate_hook['config']['where']) )
    $andwhere = ' AND '.$c->authenticate_hook['config']['where'];
  else
    $andwhere = '';

  $qry = new AwlQuery('SELECT '.$cols.' FROM usr WHERE lower(username) = :username '. $andwhere, array( ':username' => strtolower($username) ));
  $authconn = $qry->SetConnection($c->authenticate_hook['config']['connection'], ($persistent ? array(PDO::ATTR_PERSISTENT => true) : null));
  if ( ! $authconn ) {
    echo <<<EOERRMSG
  <html><head><title>Database Connection Failure</title></head><body>
  <h1>Database Error</h1>
  <h3>Could not connect to PostgreSQL database</h3>
  </body>
  </html>
EOERRMSG;
    @ob_flush();  exit(1);
  }

  if ( $qry->Exec('Login',__LINE__,__FILE__) && $qry->rows() == 1 ) {
    $usr = $qry->Fetch();
    if ( session_validate_password( $password, $usr->password ) ) {
      $principal = new Principal('username',$username);
      if ( $principal->Exists() ) {
        if ( $principal->modified <= $usr->updated )
          $principal->Update($usr);
      }
      else {
        $principal->Create($usr);
        CreateHomeCollections($username);
	CreateDefaultRelationships($username);
      }

      /**
      * We disallow login by inactive users _after_ we have updated the local copy
      */
      if ( isset($usr->active) && $usr->active == 'f' ) return false;

      return $principal;
    }
  }

  return false;

}
