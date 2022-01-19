<?php
include_once('DAVResource.php');

class WritableCollection extends DAVResource {

  /**
   * Get a TZID string from this VEVENT/VTODO/... component if we can
   * @param vComponent $comp
   * @return The TZID value we found, or null
   */
  private static function GetTZID( vComponent $comp ) {
    $p = $comp->GetProperty('DTSTART');
    if ( !isset($p) && $comp->GetType() == 'VTODO' ) {
      $p = $comp->GetProperty('DUE');
    }
    if ( !isset($p) ) return null;
    return $p->GetParameterValue('TZID');
  }

  /**
   * Writes the data to a member in the collection and returns the segment_name of the
   * resource in our internal namespace.
   *
   * @param vCalendar $vcal The resource to be written.
   * @param boolean $create_resource True if this is a new resource.
   * @param boolean $do_scheduling True if we should also do scheduling for this write. Default false.
   * @param string $segment_name The name of the resource within the collection, or null if this
   *                             call should invent one based on the UID of the vCalendar.
   * @param boolean $log_action Whether to log this action.  Defaults to false since this is normally called
   *                             in situations where one is writing secondary data.
   * @return string The segment_name of the resource within the collection, as written, or false on failure.
   */
  function WriteCalendarMember( vCalendar $vcal, $create_resource, $do_scheduling=false, $segment_name = null, $log_action=false ) {
    if ( !$this->IsSchedulingCollection() && !$this->IsCalendar() ) {
      dbg_error_log( 'PUT', '"%s" is not a calendar or scheduling collection!', $this->dav_name);
      return false;
    }

    global $session, $caldav_context;

    $resources = $vcal->GetComponents('VTIMEZONE',false); // Not matching VTIMEZONE
    $user_no = $this->user_no();
    $collection_id = $this->collection_id();

    if ( !isset($resources[0]) ) {
      dbg_error_log( 'PUT', 'No calendar content!');
      rollback_on_error( $caldav_context, $user_no, $this->dav_name.'/'.$segment_name, translate('No calendar content'), 412 );
      return false;
    }
    else {
      $first = $resources[0];
      $resource_type = $first->GetType();
    }

    $uid = $vcal->GetUID();
    if ( empty($segment_name) ) {
      $segment_name = $uid.'.ics';
    }
    $path = $this->dav_name() . $segment_name;

    $caldav_data = $vcal->Render();
    $etag = md5($caldav_data);
    $weak_etag = null;

    $qry = new AwlQuery();
    $existing_transaction_state = $qry->TransactionState();
    if ( $existing_transaction_state == 0 ) $qry->Begin();


    if ( $create_resource ) {
      $qry->QDo('SELECT nextval(\'dav_id_seq\') AS dav_id');
    }
    else {
      $qry->QDo('SELECT dav_id FROM caldav_data WHERE dav_name = :dav_name ', array(':dav_name' => $path));
    }
    if ( $qry->rows() != 1 || !($row = $qry->Fetch()) ) {
      if ( !$create_resource ) {
        // Looks like we will have to create it, even if the caller thought we wouldn't
        $qry->QDo('SELECT nextval(\'dav_id_seq\') AS dav_id');
        if ( $qry->rows() != 1 || !($row = $qry->Fetch()) ) {
          // No dav_id?  => We're toast!
          trace_bug( 'No dav_id for "%s" on %s!!!', $path, ($create_resource ? 'create': 'update'));
          rollback_on_error( $caldav_context, $user_no, $path);
          return false;
        }
        $create_resource = true;
        dbg_error_log( 'PUT', 'Unexpected need to create resource at "%s"', $path);
      }
    }
    $dav_id = $row->dav_id;

    $calitem_params = array(
        ':dav_name' => $path,
        ':user_no' => $user_no,
        ':etag' => $etag,
        ':dav_id' => $dav_id
    );

    $dav_params = array_merge($calitem_params, array(
        ':dav_data' => $caldav_data,
        ':caldav_type' => $resource_type,
        ':session_user' => $session->user_no,
        ':weak_etag' => $weak_etag
    ) );

    if ( !$this->IsSchedulingCollection() && $do_scheduling ) {
      if ( do_scheduling_requests($vcal, $create_resource ) ) {
        $dav_params[':dav_data'] = $vcal->Render(null, true);
        $etag = null;
      }
    }

    if ( $create_resource ) {
      $sql = 'INSERT INTO caldav_data ( dav_id, user_no, dav_name, dav_etag, caldav_data, caldav_type, logged_user, created, modified, collection_id, weak_etag )
              VALUES( :dav_id, :user_no, :dav_name, :etag, :dav_data, :caldav_type, :session_user, current_timestamp, current_timestamp, :collection_id, :weak_etag )';
      $dav_params[':collection_id'] = $collection_id;
    }
    else {
      $sql = 'UPDATE caldav_data SET caldav_data=:dav_data, dav_etag=:etag, caldav_type=:caldav_type, logged_user=:session_user,
              modified=current_timestamp, weak_etag=:weak_etag WHERE dav_id=:dav_id';
    }
    if ( !$qry->QDo($sql,$dav_params) ) {
      rollback_on_error( $caldav_context, $user_no, $path);
      return false;
    }

    $dtstart = $first->GetPValue('DTSTART');
    $calitem_params[':dtstart'] = $dtstart;
    if ( (!isset($dtstart) || $dtstart == '') && $first->GetPValue('DUE') != '' ) {
      $dtstart = $first->GetPValue('DUE');
    }

    $dtend = $first->GetPValue('DTEND');
    if ( isset($dtend) && $dtend != '' ) {
      dbg_error_log( 'PUT', ' DTEND: "%s", DTSTART: "%s", DURATION: "%s"', $dtend, $dtstart, $first->GetPValue('DURATION') );
      $calitem_params[':dtend'] = $dtend;
      $dtend = ':dtend';
    }
    else {
      $dtend = 'NULL';
      if ( $first->GetPValue('DURATION') != '' AND $dtstart != '' ) {
        $duration = preg_replace( '#[PT]#', ' ', $first->GetPValue('DURATION') );
        $dtend = '(:dtstart::timestamp with time zone + :duration::interval)';
        $calitem_params[':duration'] = $duration;
      }
      elseif ( $first->GetType() == 'VEVENT' ) {
        /**
        * From RFC2445 4.6.1:
        * For cases where a "VEVENT" calendar component specifies a "DTSTART"
        * property with a DATE data type but no "DTEND" property, the events
        * non-inclusive end is the end of the calendar date specified by the
        * "DTSTART" property. For cases where a "VEVENT" calendar component specifies
        * a "DTSTART" property with a DATE-TIME data type but no "DTEND" property,
        * the event ends on the same calendar date and time of day specified by the
        * "DTSTART" property.
        *
        * So we're looking for 'VALUE=DATE', to identify the duration, effectively.
        *
        */
        $value_type = $first->GetProperty('DTSTART')->GetParameterValue('VALUE');
        dbg_error_log('PUT','DTSTART without DTEND. DTSTART value type is %s', $value_type );
        if ( isset($value_type) && $value_type == 'DATE' )
          $dtend = '(:dtstart::timestamp with time zone::date + \'1 day\'::interval)';
        else
          $dtend = ':dtstart';
      }
    }

    $last_modified = $first->GetPValue('LAST-MODIFIED');
    if ( !isset($last_modified) || $last_modified == '' ) {
      $last_modified = gmdate( 'Ymd\THis\Z' );
    }
    $calitem_params[':modified'] = $last_modified;

    $dtstamp = $first->GetPValue('DTSTAMP');
    if ( !isset($dtstamp) || $dtstamp == '' ) {
      $dtstamp = $last_modified;
    }
    $calitem_params[':dtstamp'] = $dtstamp;

    $class = $first->GetPValue('CLASS');
    /*
     * It seems that some calendar clients don't set a class...
     * RFC2445, 4.8.1.3: Default is PUBLIC
     */
    if ( $this->IsPublicOnly() || !isset($class) || $class == '' ) {
      $class = 'PUBLIC';
    }
    $calitem_params[':class'] = $class;

    /** Calculate what timezone to set, first, if possible */
    $last_olson = 'Turkmenikikamukau';  // I really hope this location doesn't exist!
    $tzid = self::GetTZID($first);
    if ( !empty($tzid) ) {
      $tz = $vcal->GetTimeZone($tzid);
      $olson = $vcal->GetOlsonName($tz);

      if ( !empty($olson) && ($olson != $last_olson) ) {
        dbg_error_log( 'PUT', ' Setting timezone to %s', $olson );
        $qry->QDo('SET TIMEZONE TO \''.$olson."'" );
        $last_olson = $olson;
      }
    }

    $created = $first->GetPValue('CREATED');
    if ( $created == '00001231T000000Z' ) $created = '20001231T000000Z';
    $calitem_params[':created'] = $created;

    $calitem_params[':tzid'] = $tzid;
    $calitem_params[':uid'] = $uid;
    $calitem_params[':summary'] = $first->GetPValue('SUMMARY');
    $calitem_params[':location'] = $first->GetPValue('LOCATION');
    $calitem_params[':transp'] = $first->GetPValue('TRANSP');
    $calitem_params[':description'] = $first->GetPValue('DESCRIPTION');
    $calitem_params[':rrule'] = $first->GetPValue('RRULE');
    $calitem_params[':url'] = $first->GetPValue('URL');
    $calitem_params[':priority'] = $first->GetPValue('PRIORITY');
    $calitem_params[':due'] = $first->GetPValue('DUE');
    $calitem_params[':percent_complete'] = $first->GetPValue('PERCENT-COMPLETE');
    $calitem_params[':status'] = $first->GetPValue('STATUS');
    if ( $create_resource ) {
      $sql = <<<EOSQL
INSERT INTO calendar_item (user_no, dav_name, dav_id, dav_etag, uid, dtstamp,
                dtstart, dtend, summary, location, class, transp,
                description, rrule, tz_id, last_modified, url, priority,
                created, due, percent_complete, status, collection_id )
   VALUES ( :user_no, :dav_name, currval('dav_id_seq'), :etag, :uid, :dtstamp,
                :dtstart, $dtend, :summary, :location, :class, :transp,
                :description, :rrule, :tzid, :modified, :url, :priority,
                :created, :due, :percent_complete, :status, $collection_id )
EOSQL;
      $sync_change = 201;
    }
    else {
      $sql = <<<EOSQL
UPDATE calendar_item SET dav_etag=:etag, uid=:uid, dtstamp=:dtstamp,
                dtstart=:dtstart, dtend=$dtend, summary=:summary, location=:location, class=:class, transp=:transp,
                description=:description, rrule=:rrule, tz_id=:tzid, last_modified=:modified, url=:url, priority=:priority,
                created=:created, due=:due, percent_complete=:percent_complete, status=:status
       WHERE user_no=:user_no AND dav_name=:dav_name
EOSQL;
      $sync_change = 200;
    }

    if ( !$this->IsSchedulingCollection() ) {
      $this->WriteCalendarAlarms($dav_id, $vcal);
      $this->WriteCalendarAttendees($dav_id, $vcal);
      $put_action_type = ($create_resource ? 'INSERT' : 'UPDATE');
      if ( $log_action && function_exists('log_caldav_action') ) {
        log_caldav_action( $put_action_type, $first->GetPValue('UID'), $user_no, $collection_id, $path );
      }
      else if ( $log_action  ) {
        dbg_error_log( 'PUT', 'No log_caldav_action( %s, %s, %s, %s, %s) can be called.',
                $put_action_type, $first->GetPValue('UID'), $user_no, $collection_id, $path );
      }
    }

    $qry = new AwlQuery( $sql, $calitem_params );
    if ( !$qry->Exec('PUT',__LINE__,__FILE__) ) {
      rollback_on_error( $caldav_context, $user_no, $path);
      return false;
    }
    $qry->QDo("SELECT write_sync_change( $collection_id, $sync_change, :dav_name)", array(':dav_name' => $path ) );
    if ( $existing_transaction_state == 0 ) $qry->Commit();

    dbg_error_log( 'PUT', 'User: %d, ETag: %s, Path: %s', $session->user_no, $etag, $path);


    return $segment_name;
  }

  /**
   * Writes the data to a member in the collection and returns the segment_name of the
   * resource in our internal namespace.
   *
   * A caller who wants scheduling not to happen for this write must already
   * know they are dealing with a calendar, so should be calling WriteCalendarMember
   * directly.
   *
   * @param $resource mixed The resource to be written.
   * @param $create_resource boolean True if this is a new resource.
   * @param $segment_name The name of the resource within the collection, or false on failure.
   * @param boolean $log_action Whether to log this action.  Defaults to true since this is normally called
   *                             in situations where one is writing primary data.
   * @return string The segment_name that was given, or one that was assigned if null was given.
   */
  function WriteMember( $resource, $create_resource, $segment_name = null, $log_action=true ) {
    if ( ! $this->IsCollection() ) {
      dbg_error_log( 'PUT', '"%s" is not a collection path', $this->dav_name);
      return false;
    }
    if ( ! is_object($resource) ) {
      dbg_error_log( 'PUT', 'No data supplied!' );
      return false;
    }

    if ( $resource instanceof vCalendar ) {
      return $this->WriteCalendarMember($resource,$create_resource,true,$segment_name,$log_action);
    }
    else if ( $resource instanceof VCard )
      trace_bug( "Calling undefined function WriteAddressbookMember!? Please report this to the davical project: davical-general@lists.sourceforge.net" );
      return $this->WriteAddressbookMember($resource,$create_resource,$segment_name, $log_action);

    return $segment_name;
  }


  /**
  * Given a dav_id and an original vCalendar, pull out each of the VALARMs
  * and write the values into the calendar_alarm table.
  *
  * @return null
  */
  function WriteCalendarAlarms( $dav_id, vCalendar $vcal ) {
    $qry = new AwlQuery('DELETE FROM calendar_alarm WHERE dav_id = '.$dav_id );
    $qry->Exec('PUT',__LINE__,__FILE__);

    $components = $vcal->GetComponents();

    $qry->SetSql('INSERT INTO calendar_alarm ( dav_id, action, trigger, summary, description, component, next_trigger )
            VALUES( '.$dav_id.', :action, :trigger, :summary, :description, :component,
                                        :related::timestamp with time zone + :related_trigger::interval )' );
    $qry->Prepare();
    foreach( $components AS $component ) {
      if ( $component->GetType() == 'VTIMEZONE' ) continue;
      $alarms = $component->GetComponents('VALARM');
      if ( count($alarms) < 1 ) return;

      foreach( $alarms AS $v ) {
        $trigger = array_merge($v->GetProperties('TRIGGER'));
        if ( $trigger == null ) continue; // Bogus data.
        $trigger = $trigger[0];
        $related = null;
        $related_trigger = '0M';
        $trigger_type = $trigger->GetParameterValue('VALUE');
        if ( !isset($trigger_type) || $trigger_type == 'DURATION' ) {
          switch ( $trigger->GetParameterValue('RELATED') ) {
            case 'DTEND':  $related = $component->GetPValue('DTEND'); break;
            case 'DUE':    $related = $component->GetPValue('DUE');   break;
            default:       $related = $component->GetPValue('DTSTART');
          }
          $duration = $trigger->Value();
          if ( !preg_match('{^-?P(:?\d+W)?(:?\d+D)?(:?T(:?\d+H)?(:?\d+M)?(:?\d+S)?)?$}', $duration ) ) continue;
          $minus = (substr($duration,0,1) == '-');
          $related_trigger = trim(preg_replace( '#[PT-]#', ' ', $duration ));
          if ( $minus ) {
            $related_trigger = preg_replace( '{(\d+[WDHMS])}', '-$1 ', $related_trigger );
          }
          else {
            $related_trigger = preg_replace( '{(\d+[WDHMS])}', '$1 ', $related_trigger );
          }
        }
        else {
          if ( false === strtotime($trigger->Value()) ) continue; // Invalid date.
        }
        $qry->Bind(':action', $v->GetPValue('ACTION'));
        $qry->Bind(':trigger', $trigger->Render());
        $qry->Bind(':summary', $v->GetPValue('SUMMARY'));
        $qry->Bind(':description', $v->GetPValue('DESCRIPTION'));
        $qry->Bind(':component', $v->Render());
        $qry->Bind(':related', $related );
        $qry->Bind(':related_trigger', $related_trigger );
        $qry->Exec('PUT',__LINE__,__FILE__);
      }
    }
  }


  /**
   * Parse out the attendee property and write a row to the
   * calendar_attendee table for each one.
   * @param int $dav_id The dav_id of the caldav_data we're processing
   * @param vComponent The VEVENT or VTODO containing the ATTENDEEs
   * @return null
   */
  function WriteCalendarAttendees( $dav_id, vCalendar $vcal ) {
    $qry = new AwlQuery('DELETE FROM calendar_attendee WHERE dav_id = '.$dav_id );
    $qry->Exec('PUT',__LINE__,__FILE__);

    $attendees = $vcal->GetAttendees();
    if ( count($attendees) < 1 ) return;

    $qry->SetSql('INSERT INTO calendar_attendee ( dav_id, status, partstat, cn, attendee, role, rsvp, property )
            VALUES( '.$dav_id.', :status, :partstat, :cn, :attendee, :role, :rsvp, :property )' );
    $qry->Prepare();
    $processed = array();
    foreach( $attendees AS $v ) {
      $attendee = $v->Value();
      if ( isset($processed[$attendee]) ) {
        dbg_error_log( 'LOG', 'Duplicate attendee "%s" in resource "%d"', $attendee, $dav_id );
        dbg_error_log( 'LOG', 'Original:  "%s"', $processed[$attendee] );
        dbg_error_log( 'LOG', 'Duplicate: "%s"', $v->Render() );
        continue; /** @todo work out why we get duplicate ATTENDEE on one VEVENT */
      }
      $qry->Bind(':attendee', $attendee );
      $qry->Bind(':status',   $v->GetParameterValue('STATUS') );
      $qry->Bind(':partstat', $v->GetParameterValue('PARTSTAT') );
      $qry->Bind(':cn',       $v->GetParameterValue('CN') );
      $qry->Bind(':role',     $v->GetParameterValue('ROLE') );
      $qry->Bind(':rsvp',     $v->GetParameterValue('RSVP') );
      $qry->Bind(':property', $v->Render() );
      $qry->Exec('PUT',__LINE__,__FILE__);
      $processed[$attendee] = $v->Render();
    }
  }

  /**
   * Writes the data to a member in the collection and returns the segment_name of the
   * resource in our internal namespace.
   *
   * @param vCalendar $member_dav_name The path to the resource to be deleted.
   * @return boolean Success is true, or false on failure.
   */
  function actualDeleteCalendarMember( $member_dav_name ) {
    global $session, $caldav_context;

    // A quick sanity check...
    $segment_name = str_replace( $this->dav_name(), '', $member_dav_name );
    if ( strstr($segment_name, '/') !== false ) {
      @dbg_error_log( "DELETE", "DELETE: Refused to delete member '%s' from calendar '%s'!", $member_dav_name, $this->dav_name() );
      return false;
    }

    // We need to serialise access to this process just for this collection
    $cache = getCacheInstance();
    $myLock = $cache->acquireLock('collection-'.$this->dav_name());

    $qry = new AwlQuery();
    $params = array( ':dav_name' => $member_dav_name );

    if ( $qry->QDo("SELECT write_sync_change(collection_id, 404, caldav_data.dav_name) FROM caldav_data WHERE dav_name = :dav_name", $params )
                    && $qry->QDo("DELETE FROM property WHERE dav_name = :dav_name", $params )
                    && $qry->QDo("DELETE FROM locks WHERE dav_name = :dav_name", $params )
                    && $qry->QDo("DELETE FROM caldav_data WHERE dav_name = :dav_name", $params ) ) {
      @dbg_error_log( "DELETE", "DELETE: Calendar member %s deleted from calendar '%s'", $member_dav_name, $this->dav_name() );

      $cache->releaseLock($myLock);

      return true;
    }

    $cache->releaseLock($myLock);
    return false;

  }


  /**
   *
   * @param unknown_type $some_old_token
   */
  public function whatChangedSince( $some_old_token ) {
    $params = array( ':collection_id' => $this->collection_id() );
    if ( $some_old_token == 0 || empty($some_old_token) ) {
      $sql = <<<EOSQL
  SELECT calendar_item.*, caldav_data.*, addressbook_resource.*, 201 AS sync_status,
         COALESCE(addressbook_resource.uid,calendar_item.uid) AS uid
      FROM caldav_data
      LEFT JOIN calendar_item USING (dav_id)
      LEFT JOIN addressbook_resource USING (dav_id)
      WHERE caldav_data.collection_id = :collection_id
      ORDER BY caldav_data.collection_id, caldav_data.dav_id
EOSQL;
    }
    else {
      $params[':sync_token'] = $some_old_token;
      $sql = <<<EOSQL
  SELECT calendar_item.*, caldav_data.*, addressbook_resource.*, sync_changes.*,
         COALESCE(addressbook_resource.uid,calendar_item.uid) AS uid
      FROM sync_changes
      LEFT JOIN caldav_data USING (collection_id,dav_id)
      LEFT JOIN calendar_item USING (collection_id,dav_id)
      LEFT JOIN addressbook_resource USING (dav_id)
      WHERE sync_changes.collection_id = :collection_id
            AND sync_time >= (SELECT modification_time FROM sync_tokens WHERE sync_token = :sync_token)
      ORDER BY sync_changes.collection_id, sync_changes.dav_id, sync_changes.sync_time
EOSQL;

    }
    $qry = new AwlQuery($sql, $params );

    $changes = array();
    if ( $qry->Exec('WritableCollection') && $qry->rows() ) {
      while( $change = $qry->Fetch() ) {
        $changes[$change->uid] = $change;
      }
    }

    return $changes;
  }
}
