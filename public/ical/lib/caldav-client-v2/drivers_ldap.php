<?php
/**
* Manages LDAP repository connection
*
* @package   davical
* @category  Technical
* @subpackage authentication/drivers
* @author    Maxime Delorme <mdelorme@tennaxia.net>,
*            Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Maxime Delorme
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

require_once("auth-functions.php");

/**
 * Plugin to authenticate and sync with LDAP
 */
class ldapDriver
{
  /**#@+
  * @access private
  */

  /**
  * Holds the LDAP connection parameters
  */
  var $connect;

  /**#@-*/


  /**
  * Initializes the LDAP connection
  *
  * @param array $config The configuration data
  */
  function __construct($config)
  {
      global $c;
      $host=$config['host'];
      $port=$config['port'];
      if(!function_exists('ldap_connect')){
          $c->messages[] = i18n("drivers_ldap : function ldap_connect not defined, check your php_ldap module");
          $this->valid=false;
          return ;
      }

      //Set LDAP protocol version
      if (isset($config['protocolVersion']))
          ldap_set_option($this->connect, LDAP_OPT_PROTOCOL_VERSION, $config['protocolVersion']);
      if (isset($config['optReferrals']))
          ldap_set_option($this->connect, LDAP_OPT_REFERRALS, $config['optReferrals']);
      if (isset($config['networkTimeout']))
          ldap_set_option($this->connect, LDAP_OPT_NETWORK_TIMEOUT, $config['networkTimeout']);

      if ($port)
          $this->connect=ldap_connect($host, $port);
      else
          $this->connect=ldap_connect($host);

      if (! $this->connect){
          $c->messages[] = sprintf(translate( 'drivers_ldap : Unable to connect to LDAP with port %s on host %s'), $port, $host );
          $this->valid=false;
          return ;
      }

      dbg_error_log( "LDAP", "drivers_ldap : Connected to LDAP server %s",$host );

      // Start TLS if desired (requires protocol version 3)
      if (isset($config['startTLS'])) {
        if (!ldap_set_option($this->connect, LDAP_OPT_PROTOCOL_VERSION, 3)) {
          $c->messages[] = i18n('drivers_ldap : Failed to set LDAP to use protocol version 3, TLS not supported');
          $this->valid=false;
          return;
        }
        if (!ldap_start_tls($this->connect)) {
          $c->messages[] = i18n('drivers_ldap : Could not start TLS: ldap_start_tls() failed');
          $this->valid=false;
          return;
        }
      }

      //Set the search scope to be used, default to subtree.  This sets the functions to be called later.
      if (!isset($config['scope'])) $config['scope'] = 'subtree';
      switch (strtolower($config['scope'])) {
      case "base":
        $this->ldap_query_one = 'ldap_read';
        $this->ldap_query_all = 'ldap_read';
        break;
      case "onelevel":
        $this->ldap_query_one = 'ldap_list';
        $this->ldap_query_all = 'ldap_search';
        break;
      default:
        $this->ldap_query_one = 'ldap_search';
        $this->ldap_query_all = 'ldap_search';
        break;
      }

      //connect as root
      if (!ldap_bind($this->connect, (isset($config['bindDN']) ? $config['bindDN'] : null), (isset($config['passDN']) ? $config['passDN'] : null) ) ){
          $bindDN = isset($config['bindDN']) ? $config['bindDN'] : 'anonymous';
          $passDN = isset($config['passDN']) ? $config['passDN'] : 'anonymous';
          dbg_error_log( "LDAP", i18n('drivers_ldap : Failed to bind to host %1$s on port %2$s with bindDN of %3$s'), $host, $port, $bindDN );
          $c->messages[] = i18n( 'drivers_ldap : Unable to bind to LDAP - check your configuration for bindDN and passDN, and that your LDAP server is reachable');
          $this->valid=false;
          return ;
      }
      $this->valid = true;
      //root to start search
      $this->baseDNUsers  = is_string($config['baseDNUsers']) ? array($config['baseDNUsers']) : $config['baseDNUsers'];
      $this->filterUsers  = (isset($config['filterUsers'])  ? $config['filterUsers']  : null);
      $this->baseDNGroups = is_string($config['baseDNGroups']) ? array($config['baseDNGroups']) : $config['baseDNGroups'];
      $this->filterGroups = (isset($config['filterGroups']) ? $config['filterGroups'] : null);
  }

  /**
  * Retrieve all users from the LDAP directory
  */
  function getAllUsers($attributes){
    global $c;

    $query = $this->ldap_query_all;
    $ret = array();

    foreach($this->baseDNUsers as $baseDNUsers) {
      $entry = $query($this->connect,$baseDNUsers,$this->filterUsers,$attributes);

      if (!ldap_first_entry($this->connect,$entry)) {
        $c->messages[] = sprintf(translate('Error NoUserFound with filter >%s<, attributes >%s< , dn >%s<'),
                                 $this->filterUsers,
                                 join(', ', $attributes),
                                 $baseDNUsers);
      }
      $row = array();
      for($i = ldap_first_entry($this->connect,$entry);
          $i && $arr = ldap_get_attributes($this->connect,$i);
          $i = ldap_next_entry($this->connect,$i) ) {
        $row = array();
        for ($j=0; $j < $arr['count']; $j++) {
          $row[$arr[$j]] = $arr[$arr[$j]][0];
        }
        $ret[]=$row;
      }
    }
    return $ret;
  }

  /**
  * Retrieve all groups from the LDAP directory
  */
  function getAllGroups($attributes){
    global $c;

    $query = $this->ldap_query_all;
    $ret = array();

    foreach($this->baseDNGroups as $baseDNGroups) {
      $entry = $query($this->connect,$baseDNGroups,$this->filterGroups,$attributes);

      if (!ldap_first_entry($this->connect,$entry)) {
        $c->messages[] = sprintf(translate('Error NoGroupFound with filter >%s<, attributes >%s< , dn >%s<'),
                                 $this->filterGroups,
                                 join(', ', $attributes),
                                 $baseDNGroups);
      }
      $row = array();
      for($i = ldap_first_entry($this->connect,$entry);
          $i && $arr = ldap_get_attributes($this->connect,$i);
          $i = ldap_next_entry($this->connect,$i) ) {
        for ($j=0; $j < $arr['count']; $j++) {
          $row[$arr[$j]] = count($arr[$arr[$j]])>2?$arr[$arr[$j]]:$arr[$arr[$j]][0];
        }
        $ret[]=$row;
        unset($row);
      }
    }
    return $ret;
  }

  /**
    * Returns the result of the LDAP query
    *
    * @param string $filter The filter used to search entries
    * @param array $attributes Attributes to be returned
    * @param string $passwd password to check
    * @return array Contains selected attributes from all entries corresponding to the given filter
    */
  function requestUser( $filter, $attributes=NULL, $username, $passwd) {
    global $c;

    $entry=NULL;
    // We get the DN of the USER
    $query = $this->ldap_query_one;

    foreach($this->baseDNUsers as $baseDNUsers) {
      $entry = $query($this->connect, $baseDNUsers, $filter, $attributes);

      if (ldap_first_entry($this->connect,$entry) )
        break;

      dbg_error_log( "LDAP", "drivers_ldap : Failed to find user with baseDN: %s", $baseDNUsers );
    }

    if ( !ldap_first_entry($this->connect, $entry) ){
      dbg_error_log( "ERROR", "drivers_ldap : Unable to find the user with filter %s",$filter );
      return false;
    } else {
      dbg_error_log( "LDAP", "drivers_ldap : Found a user using filter %s",$filter );
    }

    $dnUser = ldap_get_dn($this->connect, ldap_first_entry($this->connect,$entry));

    if ( isset($c->authenticate_hook['config']['i_use_mode_kerberos']) && $c->authenticate_hook['config']['i_use_mode_kerberos'] == "i_know_what_i_am_doing") {
      if (isset($_SERVER["REMOTE_USER"])) {
        dbg_error_log( "LOG", "drivers_ldap : Skipping password Check for user %s which should be the same as %s",$username , $_SERVER["REMOTE_USER"]);
        if ($username != $_SERVER["REMOTE_USER"]) {
          return false;
        }
      } else {
        dbg_error_log( "LOG", "drivers_ldap : Skipping password Check for user %s which should be the same as %s",$username , $_SERVER["REDIRECT_REMOTE_USER"]);
        if ($username != $_SERVER["REDIRECT_REMOTE_USER"]) {
          return false;
        }
      }
    }
    else if ( empty($passwd) || preg_match('/[\x00-\x19]/',$passwd) ) {
      // See http://www.php.net/manual/en/function.ldap-bind.php#73718 for more background
      dbg_error_log( 'LDAP', 'drivers_ldap : user %s supplied empty or invalid password: login rejected', $dnUser );
      return false;
    }
    else {
      if ( !@ldap_bind($this->connect, $dnUser, $passwd) ) {
        dbg_error_log( "LDAP", "drivers_ldap : Failed to bind to user %s ", $dnUser );
        return false;
      }
    }

    dbg_error_log( "LDAP", "drivers_ldap : Bound to user %s using password %s", $dnUser,
          (isset($c->dbg['password']) && $c->dbg['password'] ? $passwd : 'another delicious password for the debugging monster!') );

    $i = ldap_first_entry($this->connect,$entry);
    $arr = ldap_get_attributes($this->connect,$i);
    for( $i=0; $i<$arr['count']; $i++ ) {
      $ret[$arr[$i]]=$arr[$arr[$i]][0];
    }
    return $ret;

  }
}


/**
* A generic function to create and fetch static objects
*/
function getStaticLdap() {
  global $c;
  // Declare a static variable to hold the object instance
  static $instance;

  // If the instance is not there, create one
  if(!isset($instance)) {
    $ldapDriver = new ldapDriver($c->authenticate_hook['config']);

    if ($ldapDriver->valid) {
        $instance = $ldapDriver;
    }
  }
  else {
      $ldapDriver = $instance;
  }
  return $ldapDriver;
}


/**
* Synchronise a cached user with one from LDAP
* @param object $principal A Principal object to be updated (or created)
*/
function sync_user_from_LDAP( Principal &$principal, $mapping, $ldap_values ) {
  global $c;

  dbg_error_log( "LDAP", "Going to sync the user from LDAP" );

  $fields_to_set = array();
  $updateable_fields = Principal::updateableFields();
  foreach( $updateable_fields AS $field ) {
    if ( isset($mapping[$field]) ) {
      $tab_part_fields = explode(',',$mapping[$field]);
      foreach( $tab_part_fields as $part_field ) {
        if ( isset($ldap_values[$part_field]) ) {
          if (isset($fields_to_set[$field]) ) {
            $fields_to_set[$field] .= ' '.$ldap_values[$part_field];
          }
          else {
            $fields_to_set[$field] = $ldap_values[$part_field];
          }
        }
      }
      dbg_error_log( "LDAP", "Setting usr->%s to %s from LDAP field %s", $field, $fields_to_set[$field], $mapping[$field] );
    }
    else if ( isset($c->authenticate_hook['config']['default_value']) && is_array($c->authenticate_hook['config']['default_value'])
              && isset($c->authenticate_hook['config']['default_value'][$field] ) ) {
      $fields_to_set[$field] = $c->authenticate_hook['config']['default_value'][$field];
      dbg_error_log( "LDAP", "Setting usr->%s to %s from configured defaults", $field, $c->authenticate_hook['config']['default_value'][$field] );
    }
  }

  if ( $principal->Exists() ) {
    $principal->Update($fields_to_set);
  }
  else {
    $principal->Create($fields_to_set);
    CreateHomeCollections($principal->username());
    CreateDefaultRelationships($principal->username());
  }
}

/**
* explode the multipart mapping
*/
function array_values_mapping($mapping){
  $attributes=array();
  foreach ( $mapping as $field ) {
    $tab_part_field = explode(",",$field);
    foreach( $tab_part_field as $part_field ) {
      $attributes[] = $part_field;
    }
  }
  return $attributes;
}

/**
* Check the username / password against the LDAP server
*/
function LDAP_check($username, $password ){
  global $c;

  $ldapDriver = getStaticLdap();
  if ( !$ldapDriver->valid ) {
    sleep(1);  // Sleep very briefly to try and survive intermittent issues
    $ldapDriver = getStaticLdap();
    if ( !$ldapDriver->valid ) {
      dbg_error_log( "ERROR", "Couldn't contact LDAP server for authentication" );
      foreach($c->messages as $msg) {
          dbg_error_log( "ERROR", "-> ".$msg );
      }
      header( sprintf("HTTP/1.1 %d %s", 503, translate("Authentication server unavailable.")) );
      exit(0);
    }
  }

  $mapping = $c->authenticate_hook['config']['mapping_field'];
  if ( isset($mapping['active']) && !isset($mapping['user_active']) ) {
    // Backward compatibility: now 'user_active'
    $mapping['user_active'] = $mapping['active'];
    unset($mapping['active']);
  }
  if ( isset($mapping['updated']) && !isset($mapping['modified']) ) {
    // Backward compatibility: now 'modified'
    $mapping['modified'] = $mapping['updated'];
    unset($mapping['updated']);
  }
  $attributes = array_values_mapping($mapping);

  /**
  * If the config contains a filter that starts with a ( then believe
  * them and don't modify it, otherwise wrap the filter.
  */
  $filter_munge = "";
  if ( preg_match( '/^\(/', $ldapDriver->filterUsers ) ) {
    $filter_munge = $ldapDriver->filterUsers;
  }
  else if ( isset($ldapDriver->filterUsers) && $ldapDriver->filterUsers != '' ) {
    $filter_munge = "($ldapDriver->filterUsers)";
  }

  $filter = "(&$filter_munge(".$mapping['username']."=$username))";
  $valid = $ldapDriver->requestUser( $filter, $attributes, $username, $password );

  // is a valid user or not
  if ( !$valid ) {
    dbg_error_log( "LDAP", "user %s is not a valid user",$username );
    return false;
  }

  if ( $mapping['modified'] != "" && in_array($mapping['modified'], $valid)) {
    $ldap_timestamp = $valid[$mapping['modified']];
  } else {
    $ldap_timestamp = '19700101000000';
  }

  /**
  * This splits the LDAP timestamp apart and assigns values to $Y $m $d $H $M and $S
  */
  foreach($c->authenticate_hook['config']['format_updated'] as $k => $v)
    $$k = substr($ldap_timestamp,$v[0],$v[1]);

  $ldap_timestamp = "$Y"."$m"."$d"."$H"."$M"."$S";
  if ($mapping['modified'] != "" && in_array($mapping['modified'], $valid)) {
    $valid[$mapping['modified']] = "$Y-$m-$d $H:$M:$S";
  }

  $principal = new Principal('username',$username);
  if ( $principal->Exists() ) {
    // should we update it ?
    $db_timestamp = $principal->modified;
    $db_timestamp = substr(strtr($db_timestamp, array(':' => '',' '=>'','-'=>'')),0,14);
    if( $ldap_timestamp <= $db_timestamp ) {
        return $principal; // no need to update
    }
    // we will need to update the user record
  }
  else {
    dbg_error_log( "LDAP", "user %s doesn't exist in local DB, we need to create it",$username );
  }
  $principal->setUsername($username);

  // The local cached user doesn't exist, or is older, so we create/update their details
  sync_user_from_LDAP( $principal, $mapping, $valid );

  return $principal;

}

/**
* turn a list of uniqueMember into member strings
*/
function fix_unique_member($list) {
  $fixed_list = array();
  foreach ( $list as $member ){
    list( $mem, $rest ) = explode(",", $member );
    $member = str_replace( 'uid=', '', $mem );
    array_unshift( $fixed_list, $member );
  }
  return $fixed_list;
}

/**
* sync LDAP Groups against the DB
*/
function sync_LDAP_groups(){
  global $c;
  $ldapDriver = getStaticLdap();
  if ( ! $ldapDriver->valid ) return;

  $mapping = $c->authenticate_hook['config']['group_mapping_field'];
  //$attributes = array('cn','modifyTimestamp','memberUid');
  $attributes = array_values_mapping($mapping);
  $ldap_groups_tmp = $ldapDriver->getAllGroups($attributes);

  if ( sizeof($ldap_groups_tmp) == 0 ) return;

  $member_field = $mapping['members'];
  $dnfix = isset($c->authenticate_hook['config']['group_member_dnfix']) && $c->authenticate_hook['config']['group_member_dnfix'];

  foreach($ldap_groups_tmp as $key => $ldap_group){
    $group_mapping = $ldap_group[$mapping['username']];
    $ldap_groups_info[$group_mapping] = $ldap_group;
    if ( is_array($ldap_groups_info[$group_mapping][$member_field]) ) {
      unset( $ldap_groups_info[$group_mapping][$member_field]['count'] );
    }
    else {
      $ldap_groups_info[$group_mapping][$member_field] = array($ldap_groups_info[$group_mapping][$member_field]);
    }
    unset($ldap_groups_tmp[$key]);
  }
  $db_groups = array();
  $db_group_members = array();
  $qry = new AwlQuery( "SELECT g.username AS group_name, member.username AS member_name FROM dav_principal g LEFT JOIN group_member ON (g.principal_id=group_member.group_id) LEFT JOIN dav_principal member  ON (member.principal_id=group_member.member_id) WHERE g.type_id = 3");
  $qry->Exec('sync_LDAP',__LINE__,__FILE__);
  while($db_group = $qry->Fetch()) {
    $db_groups[$db_group->group_name] = $db_group->group_name;
    $db_group_members[$db_group->group_name][] = $db_group->member_name;
  }

  $ldap_groups = array_keys($ldap_groups_info);
  // users only in ldap
  $groups_to_create = array_diff($ldap_groups,$db_groups);
  // users only in db
  $groups_to_deactivate = array_diff($db_groups,$ldap_groups);
  // users present in ldap and in the db
  $groups_to_update = array_intersect($db_groups,$ldap_groups);

  if ( sizeof ( $groups_to_create ) ){
    $c->messages[] = sprintf(i18n('- creating groups : %s'),join(', ',$groups_to_create));
    $validUserFields = awl_get_fields('usr');
    foreach ( $groups_to_create as $k => $group ){
      $user = (object) array();

      if ( isset($c->authenticate_hook['config']['default_value']) && is_array($c->authenticate_hook['config']['default_value']) ) {
        foreach ( $c->authenticate_hook['config']['default_value'] as $field => $value ) {
          if ( isset($validUserFields[$field]) ) {
            $user->{$field} =  $value;
            dbg_error_log( "LDAP", "Setting usr->%s to %s from configured defaults", $field, $value );
          }
        }
      }
      $user->user_no = 0;
      $ldap_values = $ldap_groups_info[$group];
      foreach ( $mapping as $field => $value ) {
        dbg_error_log( "LDAP", "Considering copying %s", $field );
        if ( isset($validUserFields[$field]) ) {
          $user->{$field} =  $ldap_values[$value];
          dbg_error_log( "LDAP", "Setting usr->%s to %s from LDAP field %s", $field, $ldap_values[$value], $value );
        }
      }
      if ($user->fullname=="") {
        $user->fullname = $group;
      }
      if ($user->displayname=="") {
        $user->displayname = $group;
      }
      $user->username = $group;
      $user->updated = "now";  /** @todo Use the 'updated' timestamp from LDAP for groups too */

      $principal = new Principal('username',$group);
      if ( $principal->Exists() ) {
        $principal->Update($user);
      }
      else {
        $principal->Create($user);
      }

      $qry = new AwlQuery( "UPDATE dav_principal set type_id = 3 WHERE username=:group ",array(':group'=>$group) );
      $qry->Exec('sync_LDAP',__LINE__,__FILE__);
      Principal::cacheDelete('username', $group);
      $c->messages[] = sprintf(i18n('- adding users %s to group : %s'),join(',',$ldap_groups_info[$group][$mapping['members']]),$group);
      foreach ( $ldap_groups_info[$group][$mapping['members']] as $member ){
        if ( $member_field == 'uniqueMember' || $dnfix ) {
          list( $mem, $rest ) = explode(",", $member );
          $member = str_replace( 'uid=', '', $mem );
        }
        $qry = new AwlQuery( "INSERT INTO group_member SELECT g.principal_id AS group_id,u.principal_id AS member_id FROM dav_principal g, dav_principal u WHERE g.username=:group AND u.username=:member;",array (':group'=>$group,':member'=>$member) );
        $qry->Exec('sync_LDAP_groups',__LINE__,__FILE__);
        Principal::cacheDelete('username', $member);
      }
    }
  }

  if ( sizeof ( $groups_to_update ) ){
    $c->messages[] = sprintf(i18n('- updating groups : %s'),join(', ',$groups_to_update));
    foreach ( $groups_to_update as $group ){
      $db_members = array_values ( $db_group_members[$group] );
      $ldap_members = array_values ( $ldap_groups_info[$group][$member_field] );
      if ( $member_field == 'uniqueMember' || $dnfix ) {
        $ldap_members = fix_unique_member( $ldap_members );
      }
      $add_users = array_diff ( $ldap_members, $db_members );
      if ( sizeof ( $add_users ) ){
        $c->messages[] = sprintf(i18n('- adding %s to group : %s'),join(', ', $add_users ), $group);
        foreach ( $add_users as $member ){
          $qry = new AwlQuery( "INSERT INTO group_member SELECT g.principal_id AS group_id,u.principal_id AS member_id FROM dav_principal g, dav_principal u WHERE g.username=:group AND u.username=:member",array (':group'=>$group,':member'=>$member) );
          $qry->Exec('sync_LDAP_groups',__LINE__,__FILE__);
          Principal::cacheDelete('username', $member);
        }
      }
      $remove_users = @array_flip( @array_flip( array_diff( $db_members, $ldap_members ) ));
      if ( sizeof ( $remove_users ) ){
        $c->messages[] = sprintf(i18n('- removing %s from group : %s'),join(', ', $remove_users ), $group);
        foreach ( $remove_users as $member ){
          $qry = new AwlQuery( "DELETE FROM group_member USING dav_principal g,dav_principal m WHERE group_id=g.principal_id AND member_id=m.principal_id AND g.username=:group AND m.username=:member",array (':group'=>$group,':member'=>$member) );
          $qry->Exec('sync_LDAP_groups',__LINE__,__FILE__);
          Principal::cacheDelete('username', $member);
        }
      }
    }
  }

  if ( sizeof ( $groups_to_deactivate ) ){
    $c->messages[] = sprintf(i18n('- deactivate groups : %s'),join(', ',$groups_to_deactivate));
    foreach ( $groups_to_deactivate as $group ){
      $qry = new AwlQuery( 'UPDATE dav_principal SET user_active=FALSE WHERE username=:group AND type_id = 3',array(':group'=>$group) );
      $qry->Exec('sync_LDAP',__LINE__,__FILE__);
      Principal::cacheFlush('username=:group AND type_id = 3', array(':group'=>$group) );
    }
  }

}

/**
* sync LDAP against the DB
*/
function sync_LDAP(){
  global $c;
  $ldapDriver = getStaticLdap();
  if ( ! $ldapDriver->valid ) return;

  $mapping = $c->authenticate_hook['config']['mapping_field'];
  $attributes = array_values_mapping($mapping);
  $ldap_users_tmp = $ldapDriver->getAllUsers($attributes);

  if ( sizeof($ldap_users_tmp) == 0 ) return;

  foreach($ldap_users_tmp as $key => $ldap_user){
    $ldap_users_info[$ldap_user[$mapping['username']]] = $ldap_user;
    unset($ldap_users_tmp[$key]);
  }
  $qry = new AwlQuery( "SELECT username, user_no, modified as updated FROM dav_principal where type_id=1");
  $qry->Exec('sync_LDAP',__LINE__,__FILE__);
  while($db_user = $qry->Fetch()) {
    $db_users[] = $db_user->username;
    $db_users_info[$db_user->username] = array('user_no' => $db_user->user_no, 'updated' => $db_user->updated);
  }

  // all users from ldap
  $ldap_users = array_keys($ldap_users_info);
  // users only in ldap
  $users_to_create = array_diff($ldap_users,$db_users);
  // users only in db
  $users_to_deactivate = array_diff($db_users,$ldap_users);
  // users present in ldap and in the db
  $users_to_update = array_intersect($db_users,$ldap_users);

  // creation of all users;
  if ( sizeof($users_to_create) ) {
    $c->messages[] = sprintf(i18n('- creating record for users :  %s'),join(', ',$users_to_create));

    foreach( $users_to_create as $username ) {
      $principal = new Principal( 'username', $username );
      $valid = $ldap_users_info[$username];
      if ( $mapping['modified'] != "" && in_array($mapping['modified'], $valid)) {
        $ldap_timestamp = $valid[$mapping['modified']];
      } else {
        $ldap_timestamp = '19700101000000';
      }

      if ( !empty($c->authenticate_hook['config']['format_updated']) ) {
        /**
        * This splits the LDAP timestamp apart and assigns values to $Y $m $d $H $M and $S
        */
        foreach($c->authenticate_hook['config']['format_updated'] as $k => $v)
            $$k = substr($ldap_timestamp,$v[0],$v[1]);
        $ldap_timestamp = $Y.$m.$d.$H.$M.$S;
      }
      else if ( preg_match('{^(\d{8})(\d{6})(Z)?$', $ldap_timestamp, $matches ) ) {
        $ldap_timestamp = $matches[1].'T'.$matches[2].$matches[3];
      }
      else if ( empty($ldap_timestamp) ) {
        $ldap_timestamp = date('c');
      }
      if ( $mapping['modified'] != "" && in_array($mapping['modified'], $valid)) {
        $valid[$mapping['modified']] = $ldap_timestamp;
      }

      sync_user_from_LDAP( $principal, $mapping, $valid );
    }
  }

  // deactivating all users
  $params = array();
  $i = 0;
  $paramstring = '';
  foreach( $users_to_deactivate AS $v ) {
    if ( isset($c->do_not_sync_from_ldap) && isset($c->do_not_sync_from_ldap[$v]) ) continue;
    if ( $i > 0 ) $paramstring .= ',';
    $paramstring .= ':u'.$i.'::text';
    $params[':u'.$i++] = strtolower($v);
  }
  if ( count($params) > 0 ) {
    $c->messages[] = sprintf(i18n('- deactivating users : %s'),join(', ',$users_to_deactivate));
    $qry = new AwlQuery( 'UPDATE usr SET active = FALSE WHERE lower(username) IN ('.$paramstring.')', $params);
    $qry->Exec('sync_LDAP',__LINE__,__FILE__);

    Principal::cacheFlush('lower(username) IN ('.$paramstring.')', $params);
  }

  // updating all users
  if ( sizeof($users_to_update) ) {
    foreach ( $users_to_update as $key=> $username ) {
      $principal = new Principal( 'username', $username );
      $valid=$ldap_users_info[$username];
      if ( $mapping['modified'] != "" && in_array($mapping['modified'], $valid)) {
        $ldap_timestamp = $valid[$mapping['modified']];
      } else {
        $ldap_timestamp = '19700101000000';
      }

      $valid['user_no'] = $db_users_info[$username]['user_no'];
      $mapping['user_no'] = 'user_no';

      /**
      * This splits the LDAP timestamp apart and assigns values to $Y $m $d $H $M and $S
      */
      foreach($c->authenticate_hook['config']['format_updated'] as $k => $v) {
        $$k = substr($ldap_timestamp,$v[0],$v[1]);
      }
      $ldap_timestamp = $Y.$m.$d.$H.$M.$S;
      $valid[$mapping['modified']] = "$Y-$m-$d $H:$M:$S";

      $db_timestamp = substr(strtr($db_users_info[$username]['updated'], array(':' => '',' '=>'','-'=>'')),0,14);
      if ( $ldap_timestamp > $db_timestamp ) {
        sync_user_from_LDAP($principal, $mapping, $valid );
      }
      else {
        unset($users_to_update[$key]);
        $users_nothing_done[] = $username;
      }
    }
    if ( sizeof($users_to_update) )
      $c->messages[] = sprintf(i18n('- updating user records : %s'),join(', ',$users_to_update));
    if ( sizeof($users_nothing_done) )
      $c->messages[] = sprintf(i18n('- nothing done on : %s'),join(', ', $users_nothing_done));
  }

  $admins = 0;
  $qry = new AwlQuery( "SELECT count(*) AS admins FROM usr JOIN role_member USING ( user_no ) JOIN roles USING (role_no) WHERE usr.active=TRUE AND role_name='Admin'");
  $qry->Exec('sync_LDAP',__LINE__,__FILE__);
  while ( $db_user = $qry->Fetch() ) {
    $admins = $db_user->admins;
  }
  if ( $admins == 0 ) {
    $c->messages[] = sprintf(i18n('Warning: there are no active admin users! You should fix this before logging out.  Consider using the $c->do_not_sync_from_ldap configuration setting.'));
  }
}
