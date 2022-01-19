<?php

include_once('vCalendar.php');

/**
 * Build the array of properties to include in the report output
 */
$qry_content = $xmltree->GetContent('urn:ietf:params:xml:ns:caldav:calendar-query');

$properties = array();
$include_properties = array();
$need_expansion = false;
while (list($idx, $qqq) = each($qry_content))
{
  $proptype = $qry_content[$idx]->GetNSTag();
  switch( $proptype ) {
    case 'DAV::prop':
      $qry_props = $xmltree->GetPath('/urn:ietf:params:xml:ns:caldav:calendar-query/'.$proptype.'/*');
      foreach( $qry_content[$idx]->GetElements() AS $k => $v ) {
        $propertyname = $v->GetNSTag();
        $properties[$propertyname] = 1;
        if ( $propertyname == 'urn:ietf:params:xml:ns:caldav:calendar-data' ) check_for_expansion($v);
      }
      break;

    case 'DAV::allprop':
      $properties['DAV::allprop'] = 1;
      if ( $qry_content[$idx]->GetNSTag() == 'DAV::include' ) {
        foreach( $qry_content[$idx]->GetElements() AS $k => $v ) {
          $include_properties[] = $v->GetNSTag(); /** $include_properties is referenced in DAVResource where allprop is expanded */
          if ( $v->GetNSTag() == 'urn:ietf:params:xml:ns:caldav:calendar-data' ) check_for_expansion($v);
        }
      }
      break;
  }
}
if ( empty($properties) ) $properties['DAV::allprop'] = 1;


/**
 * There can only be *one* FILTER element, and it must contain *one* COMP-FILTER
 * element.  In every case I can see this contained COMP-FILTER element will
 * necessarily be a VCALENDAR, which then may contain other COMP-FILTER etc.
 */
$qry_filters = $xmltree->GetPath('/urn:ietf:params:xml:ns:caldav:calendar-query/urn:ietf:params:xml:ns:caldav:filter/*');
if ( count($qry_filters) != 1 ) $qry_filters = false;


/**
* While we can construct our SQL to apply some filters in the query, other filters
* need to be checked against the retrieved record.  This is for handling those ones.
*
* @param array $filter An array of XMLElement which is the filter definition
* @param string $item The database row retrieved for this calendar item
*
* @return boolean True if the check succeeded, false otherwise.
*/
function calquery_apply_filter( $filters, $item ) {
  global $session, $c, $request;

  if ( count($filters) == 0 ) return true;

  dbg_error_log("calquery","Applying filter for item '%s'", $item->dav_name );
  $ical = new vCalendar( $item->caldav_data );
  return $ical->StartFilter($filters);
}


/**
 * Process a filter fragment returning an SQL fragment
 * Changed by GitHub user moosemark 2013-11-29 to allow multiple text-matches - SQL parameter now has numeric count appended.
 */
$need_post_filter = false;
$range_filter = null;
$parameter_match_num = 0;
function SqlFilterFragment( $filter, $components, $property = null, $parameter = null) {
  global $need_post_filter, $range_filter, $target_collection, $parameter_match_num;
  $sql = "";
  $params = array();
  if ( !is_array($filter) ) {
    dbg_error_log( "calquery", "Filter is of type '%s', but should be an array of XML Tags.", gettype($filter) );
  }

  foreach( $filter AS $k => $v ) {
    $tag = $v->GetNSTag();
    dbg_error_log("calquery", "Processing $tag into SQL - %d, '%s', %d\n", count($components), $property, isset($parameter) );

    $not_defined = "";
    switch( $tag ) {
      case 'urn:ietf:params:xml:ns:caldav:is-not-defined':
        $not_defined = "not-"; // then fall through to IS-DEFINED case
      case 'urn:ietf:params:xml:ns:caldav:is-defined':
        if ( isset( $parameter ) ) {
          $need_post_filter = true;
          dbg_error_log("calquery", "Could not handle 'is-%sdefined' on property %s, parameter %s in SQL", $not_defined, $property, $parameter );
          return false;  // Not handled in SQL
        }
        if ( isset( $property ) ) {
          switch( $property ) {
            case 'created':
            case 'completed':  /** @todo when it can be handled in the SQL - see around line 200 below */
            case 'dtend':
            case 'dtstamp':
            case 'dtstart':
              if ( ! $target_collection->IsSchedulingCollection() ) {
                $property_defined_match = "IS NOT NULL";
              }
              break;

            case 'priority':
              $property_defined_match = "IS NOT NULL";
              break;

            default:
              $property_defined_match = "LIKE '_%'";  // i.e. contains a single character or more
          }
          $sql .= sprintf( "AND %s %s%s ", $property, $not_defined, $property_defined_match );
        }
        break;

      case 'urn:ietf:params:xml:ns:caldav:time-range':
        /**
        * @todo We should probably allow time range queries against other properties, since
        *  eventually some client may want to do this.
        */
        $start_column = ($components[sizeof($components)-1] == 'VTODO' ? "due" : 'dtend');     // The column we compare against the START attribute
        $finish_column = 'dtstart';  // The column we compare against the END attribute
        $start = $v->GetAttribute("start");
        $finish = $v->GetAttribute("end");
        $start_sql = $finish_sql = '';
        if ( isset($start) ) {
          $params[':time_range_start'] = $start;
          $start_sql .= ' (('.$start_column.' IS NULL AND '.$finish_column.' > :time_range_start) OR '.$start_column.' > :time_range_start) ';
        }
        if ( isset($finish) ) {
          $params[':time_range_end'] = $finish;
          $finish_sql = ' '.$finish_column.' < :time_range_end ';
        }
        if ( isset($start) || isset($finish) ) {
          $sql .= ' AND (rrule IS NOT NULL OR '.$finish_column.' IS NULL OR (';
          if ( isset($start) ) $sql .= $start_sql;
          if ( isset($start) && isset($finish) ) $sql .= ' AND ';
          if ( isset($finish) ) $sql .= $finish_sql;
          $sql .= '))';
        }
        @dbg_error_log('calquery', 'filter-sql: %s', $sql);
        @dbg_error_log('calquery', 'time-range-start: %s,  time-range-end: %s, ', $params[':time_range_start'], $params[':time_range_end']);
        $range_filter = new RepeatRuleDateRange((empty($start) ? null : new RepeatRuleDateTime($start)),
                                    (empty($finish)? null : new RepeatRuleDateTime($finish)));
        break;

      case 'urn:ietf:params:xml:ns:caldav:text-match':
        $search = $v->GetContent();
        $negate = $v->GetAttribute("negate-condition");
        $collation = $v->GetAttribute("collation");
        switch( strtolower($collation) ) {
          case 'i;octet':
            $comparison = 'LIKE';
            break;
          case 'i;ascii-casemap':
          default:
            $comparison = 'ILIKE';
            break;
        }
        /* Append the match number to the SQL parameter, to allow multiple text match conditions within the same query */
        $params[':text_match_'.$parameter_match_num] = '%'.$search.'%';
        $fragment = sprintf( 'AND (%s%s %s :text_match_%s) ',
                        (isset($negate) && strtolower($negate) == "yes" ? $property.' IS NULL OR NOT ': ''),
                                          $property, $comparison, $parameter_match_num );
        $parameter_match_num++;

        dbg_error_log('calquery', ' text-match: %s', $fragment );
        $sql .= $fragment;
        break;

      case 'urn:ietf:params:xml:ns:caldav:comp-filter':
        $comp_filter_name = $v->GetAttribute("name");
        if ( $comp_filter_name != 'VCALENDAR' && count($components) == 0 ) {
          $sql .= "AND caldav_data.caldav_type = :component_name_filter ";
          $params[':component_name_filter'] = $comp_filter_name;
          $components[] = $comp_filter_name;
        }
        $subfilter = $v->GetContent();
        if ( is_array( $subfilter ) ) {
          $success = SqlFilterFragment( $subfilter, $components, $property, $parameter );
          if ( $success === false ) continue; else {
            $sql .= $success['sql'];
            $params = array_merge( $params, $success['params'] );
          }
        }
        break;

      case 'urn:ietf:params:xml:ns:caldav:prop-filter':
        $propertyname = $v->GetAttribute("name");
        switch( $propertyname ) {
          case 'PERCENT-COMPLETE':
            $subproperty = 'percent_complete';
            break;

          case 'UID':
          case 'SUMMARY':
//          case 'LOCATION':
          case 'DESCRIPTION':
          case 'CLASS':
          case 'TRANSP':
          case 'RRULE':  // Likely that this is not much use
          case 'URL':
          case 'STATUS':
          case 'CREATED':
          case 'DTSTAMP':
          case 'DTSTART':
          case 'DTEND':
          case 'DUE':
          case 'PRIORITY':
            $subproperty = 'calendar_item.'.strtolower($propertyname);
            break;

          case 'COMPLETED':  /** @todo this should be moved into the properties supported in SQL. */
          default:
            $need_post_filter = true;
            unset($subproperty);
            dbg_error_log("calquery", "Could not handle 'prop-filter' on %s in SQL", $propertyname );
            continue;
        }
        if ( isset($subproperty) ) {
          $subfilter = $v->GetContent();
          $success = SqlFilterFragment( $subfilter, $components, $subproperty, $parameter );
          if ( $success === false ) continue; else {
            $sql .= $success['sql'];
            $params = array_merge( $params, $success['params'] );
          }
        }
        break;

      case 'urn:ietf:params:xml:ns:caldav:param-filter':
        $need_post_filter = true;
        return false; // Can't handle PARAM-FILTER conditions in the SQL
        $parameter = $v->GetAttribute("name");
        $subfilter = $v->GetContent();
        $success = SqlFilterFragment( $subfilter, $components, $property, $parameter );
        if ( $success === false ) continue; else {
          $sql .= $success['sql'];
          $params = array_merge( $params, $success['params'] );
        }
        break;

      default:
        dbg_error_log("calquery", "Could not handle unknown tag '%s' in calendar query report", $tag );
        break;
    }
  }
  dbg_error_log("calquery", "Generated SQL was '%s'", $sql );
  return array( 'sql' => $sql, 'params' => $params );
}

/**
 * Build an SQL 'WHERE' clause which implements (parts of) the filter. The
 * elements of the filter which are implemented in the SQL will be removed.
 *
 * @param arrayref &$filter A reference to an array of XMLElement defining the filter
 *
 * @return string A string suitable for use as an SQL 'WHERE' clause selecting the desired records.
 */
function BuildSqlFilter( $filter ) {
  $components = array();
  if ( $filter->GetNSTag() == "urn:ietf:params:xml:ns:caldav:comp-filter" && $filter->GetAttribute("name") == "VCALENDAR" )
    $filter = $filter->GetContent();  // Everything is inside a VCALENDAR AFAICS
  else {
    dbg_error_log("calquery", "Got bizarre CALDAV:FILTER[%s=%s]] which does not contain comp-filter = VCALENDAR!!", $filter->GetNSTag(), $filter->GetAttribute("name") );
  }
  return SqlFilterFragment( $filter, $components );
}


/**
* Something that we can handle, at least roughly correctly.
*/

$responses = array();
$target_collection = new DAVResource($request->path);
$bound_from = $target_collection->bound_from();
if ( !$target_collection->Exists() ) {
  $request->DoResponse( 404 );
}

$params = array();

if ( ! ($target_collection->IsCalendar() || $target_collection->IsSchedulingCollection()) ) {
  if ( !(isset($c->allow_recursive_report) && $c->allow_recursive_report) ) {
    $request->DoResponse( 403, translate('The calendar-query report must be run against a calendar or a scheduling collection') );
  }
  else if ( $request->path == '/' || $target_collection->IsPrincipal() || $target_collection->IsAddressbook() ) {
    $request->DoResponse( 403, translate('The calendar-query report may not be run against that URL.') );
  }
  /**
   * We're here because they allow recursive reports, and this appears to be such a location.
   */
  $where = 'WHERE caldav_data.collection_id IN ';
  $where .= '(SELECT bound_source_id FROM dav_binding WHERE dav_binding.dav_name ~ :path_match ';
  $where .= 'UNION ';
  $where .= 'SELECT collection_id FROM collection WHERE collection.dav_name ~ :path_match) ';
  $distinct = 'DISTINCT ON (calendar_item.uid) ';
  $params[':path_match'] = '^'.$target_collection->bound_from();
}
else {
  $where = ' WHERE caldav_data.collection_id = ' . $target_collection->resource_id();
  $distinct = '';
}

if ( is_array($qry_filters) ) {
  dbg_log_array( "calquery", "qry_filters", $qry_filters, true );
  $components = array();
  $filter_fragment =  SqlFilterFragment( $qry_filters, $components );
  if ( $filter_fragment !== false ) {
    $where .= ' '.$filter_fragment['sql'];
    $params = array_merge( $params, $filter_fragment['params']);
  }
}
if ( $target_collection->Privileges() != privilege_to_bits('DAV::all') ) {
  $where .= " AND (calendar_item.class != 'PRIVATE' OR calendar_item.class IS NULL) ";
}

if ( isset($c->hide_TODO) && ($c->hide_TODO === true || (is_string($c->hide_TODO) && preg_match($c->hide_TODO, $_SERVER['HTTP_USER_AGENT']))) && ! $target_collection->HavePrivilegeTo('all') ) {
  $where .= " AND caldav_data.caldav_type NOT IN ('VTODO') ";
}

if ( isset($c->hide_older_than) && intval($c->hide_older_than > 0) ) {
  $where .= " AND (CASE WHEN caldav_data.caldav_type<>'VEVENT' OR calendar_item.dtstart IS NULL THEN true ELSE calendar_item.dtstart > (now() - interval '".intval($c->hide_older_than)." days') END) ";
}

$sql = 'SELECT '.$distinct.' caldav_data.*,calendar_item.*  FROM collection INNER JOIN caldav_data USING(collection_id) INNER JOIN calendar_item USING(dav_id) '. $where;
if ( isset($c->strict_result_ordering) && $c->strict_result_ordering ) $sql .= " ORDER BY caldav_data.dav_id";
$qry = new AwlQuery( $sql, $params );
if ( $qry->Exec("calquery",__LINE__,__FILE__) && $qry->rows() > 0 ) {
  while( $dav_object = $qry->Fetch() ) {
    try {
      if ( !$need_post_filter || calquery_apply_filter( $qry_filters, $dav_object ) ) {
        if ( $bound_from != $target_collection->dav_name() ) {
          $dav_object->dav_name = str_replace( $bound_from, $target_collection->dav_name(), $dav_object->dav_name);
        }
        if ( $need_expansion ) {
          $vResource = new vComponent($dav_object->caldav_data);
          $expanded = getVCalendarRange($vResource);
          if ( !$expanded->overlaps($range_filter) ) continue;

          $expanded = expand_event_instances($vResource, $expand_range_start, $expand_range_end, $expand_as_floating );

          if ( $expanded->ComponentCount() == 0 ) continue;
          if ( $need_expansion ) $dav_object->caldav_data = $expanded->Render();
        }
        else if ( isset($range_filter) ) {
          $vResource = new vComponent($dav_object->caldav_data);
          $expanded = getVCalendarRange($vResource);
          dbg_error_log('calquery', 'Expanded to %s:%s which might overlap %s:%s',
                         $expanded->from, $expanded->until, $range_filter->from, $range_filter->until );
          if ( !$expanded->overlaps($range_filter) ) continue;
        }
        $responses[] = component_to_xml( $properties, $dav_object );
      }
    }
    catch( Exception $e ) {
      dbg_error_log( 'ERROR', 'Exception handling "%s" - skipping', $dav_object->dav_name);
    }
  }
}

$multistatus = new XMLElement( "multistatus", $responses, $reply->GetXmlNsArray() );

$request->XMLResponse( 207, $multistatus );
