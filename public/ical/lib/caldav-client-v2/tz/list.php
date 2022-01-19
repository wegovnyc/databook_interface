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

require_once('vComponent.php');

$response = new XMLDocument( array("urn:ietf:params:xml:ns:timezone-service" => "") );
$tzlist = $response->NewXMLElement('timezone-list');
$qry = new AwlQuery('SELECT to_char(max(last_modified),\'YYYY-MM-DD"T"HH24:MI:SS"Z"\') AS dtstamp FROM timezones');
if ( $qry->Exec('tz/list',__LINE__,__FILE__) && $qry->rows() > 0 ) {
  $row = $qry->Fetch();
  $tzlist->NewElement('dtstamp', $row->dtstamp);
}
else {
  $tzlist->NewElement('dtstamp', gmdate('Y-m-d\TH:i:s\Z'));
}

$sql = 'SELECT our_tzno, tzid, active, to_char(last_modified,\'YYYY-MM-DD"T"HH24:MI:SS"Z"\') AS last_modified, olson_name, vtimezone FROM timezones';
$params = array();
$where = '';
if ( $returnall !== true ) {
  $where = 'active';
}
if ( !empty($changedsince) ) {
  if ( !empty($where) ) $where .= ' AND ';
  $where .= 'last_modified > :changedsince';
  $params[':changedsince'] = $changedsince;
}
if ( !empty($tzid) ) {
  if ( !empty($where) ) $where .= ' AND ';
  $where .= '(tzid = :tzid OR our_tzno IN (SELECT our_tzno FROM tz_aliases WHERE tzalias = :tzid))';
  $params[':tzid'] = $tzid;
}
if ( !empty($where)) $sql .= ' WHERE '.$where;

if ( !empty($c->strict_result_ordering) && $c->strict_result_ordering ) {
  $sql .= ' ORDER BY tzid';
}
/*
<dtstamp>2009-10-11T09:32:11Z</dtstamp>
<summary>
<tzid>America/New_York</tzid>
<last-modified>2009-09-17T01:39:34Z</last-modified>
<alias>US/Eastern</alias>
<local-name lang="en_US">America/New_York</local-name>
<summary>
*/
$q2 = new AwlQuery();
$qry = new AwlQuery($sql,$params);
if ( $qry->Exec('tz/list',__LINE__,__FILE__) && $qry->rows() > 0 ) {
  while( $tz = $qry->Fetch() ) {
    $elements = array(
      new XMLElement('tzid', $tz->tzid),
      new XMLElement('last-modified', $tz->last_modified)
    );
    if ( $tz->active != 't' ) {
      $elements[] = new XMLElement('inactive' );
    }
    if ( $tz->tzid != $tz->olson_name ) {
      $elements[] = new XMLElement('alias', $tz->olson_name );
    }
    if ( $q2->QDo('SELECT * FROM tz_aliases WHERE our_tzno = ?', array($tz->our_tzno)) ) {
      while( $alias = $q2->Fetch() ) {
        $elements[] = new XMLElement('alias', $alias->tzalias );
      }
    }
    if ( !empty($lang) && $q2->QDo('SELECT * FROM tz_localnames WHERE our_tzno = ? AND locale = ?', array($tz->our_tzno, $lang)) && $q2->rows() > 0 ) {
      while( $local = $q2->Fetch() ) {
        $attr = array( 'lang' => $local->locale );
        if ( $local->preferred == 't' ) $attr['preferred'] = 'true';
        $elements[] = new XMLElement('local-name', $local->localised_name, $attr );
      }
    }
    else {
      $elements[] = new XMLElement('local-name', $tz->tzid, ( empty($lang) ? null : array( 'lang' => $lang ) ) );
    }
    $tzlist->NewElement('summary', $elements);
  }
}

header('Content-Type: application/xml; charset="utf-8"');

echo $response->Render($tzlist);
exit(0);