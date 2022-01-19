<?php
/**
* DAViCal Timezone Service handler - capabilitis
*
* @package   davical
* @subpackage   tzservice
* @author    Andrew McMillan <andrew@morphoss.com>
* @copyright Morphoss Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once('vCalendar.php');
require_once('RRule.php');

if ( empty($format) ) $format = 'text/calendar';
if ( $format != 'text/calendar' ) {
  $request->PreconditionFailed(403, 'supported-format', 'This server currently only supports text/calendar format.', 'urn:ietf:params:xml:ns:timezone-service' );
}

if ( empty($start) ) $start = sprintf( '%04d-01-01', date('Y'));
if ( empty($end) )   $end   = sprintf( '%04d-12-31', date('Y') + 10);

$sql = 'SELECT our_tzno, tzid, active, olson_name, vtimezone, etag, ';
$sql .= 'to_char(last_modified,\'Dy, DD Mon IYYY HH24:MI:SS "GMT"\') AS last_modified ';
$sql .= 'FROM timezones WHERE tzid=:tzid';
$params = array( ':tzid' => $tzid );
$qry = new AwlQuery($sql,$params);
if ( !$qry->Exec() ) exit(1);
if ( $qry->rows() < 1 ) {
  $sql = 'SELECT our_tzno, tzid, active, olson_name, vtimezone, etag, ';
  $sql .= 'to_char(last_modified,\'Dy, DD Mon IYYY HH24:MI:SS "GMT"\') AS last_modified ';
  $sql .= 'FROM timezones JOIN tz_aliases USING(our_tzno) WHERE tzalias=:tzid';
  if ( !$qry->Exec() ) exit(1);
  if ( $qry->rows() < 1 ) $request->DoResponse(404);
}

$tz = $qry->Fetch();

// define( 'DEBUG_EXPAND', true);
define( 'DEBUG_EXPAND', false );


/**
* Expand the instances for a STANDARD or DAYLIGHT component of a VTIMEZONE
*
* @param object $vResource is a VCALENDAR with a VTIMEZONE containing components needing expansion
* @param object $range_start A RepeatRuleDateTime which is the beginning of the range for events.
* @param object $range_end A RepeatRuleDateTime which is the end of the range for events.
* @param int $offset_from The offset from UTC in seconds at the onset time.
*
* @return array of onset datetimes with UTC from/to offsets
*/
function expand_timezone_onsets( vCalendar $vResource, RepeatRuleDateTime $range_start, RepeatRuleDateTime $range_end ) {
  global $c;
  $vtimezones = $vResource->GetComponents();
  $vtz = $vtimezones[0];
  $components = $vtz->GetComponents();

  $instances = array();
  $dtstart = null;
  $is_date = false;
  $has_repeats = false;
  $zone_tz = $vtz->GetPValue('TZID');

  foreach( $components AS $k => $comp ) {
    if ( DEBUG_EXPAND ) {
      printf( "Starting TZ expansion for component '%s' in timezone '%s'\n", $comp->GetType(), $zone_tz);
      foreach( $instances AS $k => $v ) {
        print ' : '.$k;
      }
      print "\n";
    }
    $dtstart_prop = $comp->GetProperty('DTSTART');
    if ( !isset($dtstart_prop) ) continue;
    $dtstart = new RepeatRuleDateTime( $dtstart_prop );
    $dtstart->setTimeZone('UTC');
    $offset_from = $comp->GetPValue('TZOFFSETFROM');
    $offset_from = (($offset_from / 100) * 3600) + ((abs($offset_from) % 100) * 60 * ($offset_from < 0 ? -1 : 0));
    $offset_from *= -1;
    $offset_from = "$offset_from seconds";
    dbg_error_log( 'tz/update', "%s of offset\n", $offset_from);
    $dtstart->modify($offset_from);
    $is_date = $dtstart->isDate();
    $instances[$dtstart->UTC('Y-m-d\TH:i:s\Z')] = $comp;
    $rrule = $comp->GetProperty('RRULE');
    $has_repeats = isset($rrule);
    if ( !$has_repeats ) continue;

    $recur = $comp->GetProperty('RRULE');
    if ( isset($recur) ) {
      $recur = $recur->Value();
      $this_start = clone($dtstart);
      $rule = new RepeatRule( $this_start, $recur, $is_date );
      $i = 0;
      $result_limit = 1000;
      while( $date = $rule->next() ) {
        $instances[$date->UTC('Y-m-d\TH:i:s\Z')] = $comp;
        if ( $i++ >= $result_limit || $date > $range_end ) break;
      }
      if ( DEBUG_EXPAND ) {
        print( "After rrule_expand");
        foreach( $instances AS $k => $v ) {
          print ' : '.$k;
        }
        print "\n";
      }
    }

    $properties = $comp->GetProperties('RDATE');
    if ( count($properties) ) {
      foreach( $properties AS $p ) {
        $timezone = $p->GetParameterValue('TZID');
        $rdate = $p->Value();
        $rdates = explode( ',', $rdate );
        foreach( $rdates AS $k => $v ) {
          $rdate = new RepeatRuleDateTime( $v, $timezone, $is_date);
          if ( $return_floating_times ) $rdate->setAsFloat();
          $instances[$rdate->UTC('Y-m-d\TH:i:s\Z')] = $comp;
          if ( $rdate > $range_end ) break;
        }
      }

      if ( DEBUG_EXPAND ) {
        print( "After rdate_expand");
        foreach( $instances AS $k => $v ) {
          print ' : '.$k;
        }
        print "\n";
      }
    }
  }

  ksort($instances);

  $onsets = array();
  $start_utc = $range_start->UTC('Y-m-d\TH:i:s\Z');
  $end_utc = $range_end->UTC('Y-m-d\TH:i:s\Z');
  foreach( $instances AS $utc => $comp ) {
    if ( $utc > $end_utc ) {
      if ( DEBUG_EXPAND ) printf( "We're done: $utc is out of the range.\n");
      break;
    }

    if ( $utc < $start_utc ) {
      continue;
    }
    $onsets[$utc] = array(
      'from' => $comp->GetPValue('TZOFFSETFROM'),
      'to' => $comp->GetPValue('TZOFFSETTO'),
      'name' => $comp->GetPValue('TZNAME'),
      'type' => $comp->GetType()
    );
  }

  return $onsets;
}

header( 'ETag: "'.$tz->etag.'"' );
header( 'Last-Modified', $tz->last_modified );
header('Content-Type: application/xml; charset="utf-8"');

$vtz = new vCalendar($tz->vtimezone);

$response = new XMLDocument(array("urn:ietf:params:xml:ns:timezone-service" => ""));
$timezones = $response->NewXMLElement('urn:ietf:params:xml:ns:timezone-service:timezones');
$qry = new AwlQuery('SELECT to_char(max(last_modified),\'YYYY-MM-DD"T"HH24:MI:SS"Z"\') AS dtstamp FROM timezones');
if ( $qry->Exec('tz/list',__LINE__,__FILE__) && $qry->rows() > 0 ) {
  $row = $qry->Fetch();
  $timezones->NewElement('dtstamp', $row->dtstamp);
}
else {
  $timezones->NewElement('dtstamp', gmdate('Y-m-d\TH:i:s\Z'));
}

$from = new RepeatRuleDateTime($start);
$until = new RepeatRuleDateTime($end);

$observances = expand_timezone_onsets($vtz, $from, $until);
$tzdata = array();
$tzdata[] = new XMLElement( 'tzid', $tzid );
$tzdata[] = new XMLElement( 'calscale', 'Gregorian' );

foreach( $observances AS $onset => $details ) {
  $tzdata[] = new XMLElement( 'observance', array(
    new XMLElement('name', (empty($details['name']) ? $details['type'] : $details['name'] ) ),
    new XMLElement('onset', $onset ),
    new XMLElement('utc-offset-from', substr($details['from'],0,-2).':'.substr($details['from'],-2) ),
    new XMLElement('utc-offset-to', substr($details['to'],0,-2).':'.substr($details['to'],-2) )
  ));
}

$timezones->NewElement('tzdata', $tzdata );
echo $response->Render($timezones);

exit(0);
