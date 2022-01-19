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

if ( empty($format) ) $format = 'text/calendar';
if ( $format != 'text/calendar' ) {
  $request->PreconditionFailed(403, 'supported-format', 'This server currently only supports text/calendar format.', 'urn:ietf:params:xml:ns:timezone-service');
}

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

$vtz = new vCalendar($tz->vtimezone);
$vtz->AddProperty('TZ-URL', $c->protocol_server_port . $_SERVER['REQUEST_URI']);
$vtz->AddProperty('TZNAME', $tz->olson_name );
if ( $qry->QDo('SELECT * FROM tz_localnames WHERE our_tzno = :our_tzno', array(':our_tzno'=>$tz->our_tzno)) && $qry->rows() ) {
  while( $name = $qry->Fetch() ) {
    if ( strpos($_SERVER['QUERY_STRING'], 'lang='.$name->locale) !== false ) {
      $vtz->AddProperty('TZNAME',$name->localised_name, array('LANGUAGE',str_replace('_','-',$name->locale)));
    }
  }
}

header( 'ETag: "'.$tz->etag.'"' );
header( 'Last-Modified: '. $tz->last_modified );
header( 'Content-Disposition: Attachment; Filename="'.str_replace('/','-',$tzid . '.ics"' ));

$request->DoResponse(200, $vtz->Render(), 'text/calendar; charset=UTF-8');

exit(0);
