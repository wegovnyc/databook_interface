<?php

/**
 * Function to include which handles building a free/busy response to
 * be used in either the REPORT, response to a POST, or response to a
 * a freebusy GET request.
 */

include_once('vCalendar.php');
include_once('RRule.php');


function get_freebusy( $path_match, $range_start, $range_end, $bin_privs = null ) {
  global $request, $c;
  dbg_error_log( 'freebusy', ' Getting freebusy for path %s from %s to %s', $path_match, $range_start, $range_end);

  if ( !isset($bin_privs) ) $bin_privs = $request->Privileges();
  if ( !isset($range_start) || !isset($range_end) ) {
    $request->DoResponse( 400, 'All valid freebusy requests MUST contain a time-range filter' );
  }
  $params = array( ':path_match' => $path_match, ':start' => $range_start->UTC(), ':end' => $range_end->UTC() );
  $where = ' WHERE caldav_data.dav_name ~ :path_match ';
  $where .= 'AND rrule_event_overlaps( dtstart, dtend, rrule, :start, :end) ';
  $where .= "AND caldav_data.caldav_type IN ( 'VEVENT', 'VTODO' ) ";
  $where .= "AND (calendar_item.transp != 'TRANSPARENT' OR calendar_item.transp IS NULL) ";
  $where .= "AND (calendar_item.status != 'CANCELLED' OR calendar_item.status IS NULL) ";
  $where .= "AND collection.is_calendar AND collection.schedule_transp = 'opaque' ";

  if ( $bin_privs != privilege_to_bits('all') ) {
    $where .= "AND (calendar_item.class != 'PRIVATE' OR calendar_item.class IS NULL) ";
  }

  $fbtimes = array();
  $sql = 'SELECT caldav_data.caldav_data, calendar_item.rrule, calendar_item.transp, calendar_item.status, ';
  $sql .= "to_char(calendar_item.dtstart at time zone 'GMT',".AWLDatabase::SqlUTCFormat.') AS start, ';
  $sql .= "to_char(calendar_item.dtend at time zone 'GMT',".AWLDatabase::SqlUTCFormat.') AS finish, ';
  $sql .= "calendar_item.class, calendar_item.dav_id ";
  $sql .= 'FROM caldav_data INNER JOIN calendar_item USING(dav_id,user_no,dav_name,collection_id) ';
  $sql .= 'INNER JOIN collection USING(collection_id)';
  $sql .= $where;
  if ( isset($c->strict_result_ordering) && $c->strict_result_ordering ) $sql .= ' ORDER BY dav_id';
  $qry = new AwlQuery( $sql, $params );
  if ( $qry->Exec("REPORT",__LINE__,__FILE__) && $qry->rows() > 0 ) {
    while( $calendar_object = $qry->Fetch() ) {
      $extra = '';
      if ( $calendar_object->status == 'TENTATIVE' ) {
        $extra = ';BUSY-TENTATIVE';
      }
      else if ( isset($c->_workaround_client_freebusy_bug) && $c->_workaround_client_freebusy_bug ) {
        $extra = ';BUSY';
      }
      //$extra = ';'.$calendar_object->dav_id;
      $ics = new vComponent($calendar_object->caldav_data);
      $expanded = expand_event_instances($ics, $range_start, $range_end);
      $expansion = $expanded->GetComponents( array('VEVENT'=>true,'VTODO'=>true,'VJOURNAL'=>true) );
      dbg_error_log( "freebusy", "===================   $calendar_object->dav_id   ======================== %s -> %s, %s %s", $calendar_object->start, $calendar_object->finish, $calendar_object->class, $extra );
      $dtstart_type = 'DTSTART';
      foreach( $expansion AS $k => $v ) {
        dbg_error_log( "freebusy", " %s: %s", $k, $v->Render() );
        $start_date = $v->GetProperty($dtstart_type);
        if ( !isset($start_date) && $v->GetType() != 'VTODO' ) {
            $dtstart_type = 'DUE';
            $start_date = $v->GetProperty($dtstart_type);
        }
        $start_date = new RepeatRuleDateTime($start_date);
        $duration = $v->GetProperty('DURATION');
        $duration = ( !isset($duration) ? 'P1D' : $duration->Value());
        $end_date = clone($start_date);
        $end_date->modify( $duration );
        if ( $end_date == $start_date || $end_date < $range_start || $start_date > $range_end ) {
          dbg_error_log( "freebusy", "-----------------------------------------------------" );
          continue;
        }
        $thisfb = $start_date->UTC() .'/'. $end_date->UTC() . $extra;
        array_push( $fbtimes, $thisfb );
      }
    }
  }

  $freebusy = new vComponent();
  $freebusy->setType('VFREEBUSY');
  $freebusy->AddProperty('DTSTAMP', date('Ymd\THis\Z'));
  $freebusy->AddProperty('DTSTART', $range_start->UTC());
  $freebusy->AddProperty('DTEND', $range_end->UTC());

  sort( $fbtimes );
  foreach( $fbtimes AS $k => $v ) {
    $text = explode(';',$v,2);
    $freebusy->AddProperty( 'FREEBUSY', $text[0], (isset($text[1]) ? array('FBTYPE' => $text[1]) : null) );
  }

  return $freebusy;
}

