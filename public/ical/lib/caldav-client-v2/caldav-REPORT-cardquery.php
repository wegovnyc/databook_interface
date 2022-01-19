<?php

require_once('vcard.php');

$address_data_properties = array();
function get_address_properties( $address_data_xml ) {
  global $address_data_properties;
  $expansion = $address_data_xml->GetElements();
  foreach( $expansion AS $k => $v ) {
    if ( $v instanceof XMLElement )
      $address_data_properties[strtoupper($v->GetAttribute('name'))] = true;
  }
}


/**
 * Build the array of properties to include in the report output
 */
$qry_content = $xmltree->GetContent('urn:ietf:params:xml:ns:carddav:addressbook-query');
$proptype = $qry_content[0]->GetNSTag();
$properties = array();
switch( $proptype ) {
  case 'DAV::prop':
    $qry_props = $xmltree->GetPath('/urn:ietf:params:xml:ns:carddav:addressbook-query/'.$proptype.'/*');
    foreach( $qry_content[0]->GetElements() AS $k => $v ) {
      $properties[$v->GetNSTag()] = 1;
      if ( $v->GetNSTag() == 'urn:ietf:params:xml:ns:carddav:address-data' ) get_address_properties($v);
    }
    break;

  case 'DAV::allprop':
    $properties['DAV::allprop'] = 1;
    if ( $qry_content[1]->GetNSTag() == 'DAV::include' ) {
      foreach( $qry_content[1]->GetElements() AS $k => $v ) {
        $include_properties[] = $v->GetNSTag(); /** $include_properties is referenced in DAVResource where allprop is expanded */
        if ( $v->GetNSTag() == 'urn:ietf:params:xml:ns:carddav:address-data' ) get_address_properties($v);
      }
    }
    break;

  default:
    $properties[$proptype] = 1;
}
if ( empty($properties) ) $properties['DAV::allprop'] = 1;

/**
 * There can only be *one* FILTER element.
 */
$qry_filters = $xmltree->GetPath('/urn:ietf:params:xml:ns:carddav:addressbook-query/urn:ietf:params:xml:ns:carddav:filter/*');
if ( count($qry_filters) == 0 ) {
  $qry_filters = false;
}

$qry_limit = -1; // everything
$qry_filters_combination='OR';
if ( is_array($qry_filters) ) {
  $filters_parent = $xmltree->GetPath('/urn:ietf:params:xml:ns:carddav:addressbook-query/urn:ietf:params:xml:ns:carddav:filter');
  $filters_parent = $filters_parent[0];
  // only anyof (OR) or allof (AND) allowed, if missing anyof is default (RFC6352 10.5)
  if ( $filters_parent->GetAttribute("test") == 'allof' ) {
    $qry_filters_combination='AND';
  }

  $limits = $xmltree->GetPath('/urn:ietf:params:xml:ns:carddav:addressbook-query/urn:ietf:params:xml:ns:carddav:limit/urn:ietf:params:xml:ns:carddav:nresults');
  if ( count($limits) == 1) {
    $qry_limit = intval($limits[0]->GetContent());
  }
}

/**
* While we can construct our SQL to apply some filters in the query, other filters
* need to be checked against the retrieved record.  This is for handling those ones.
*
* @param array $filter An array of XMLElement which is the filter definition
* @param string $item The database row retrieved for this calendar item
* @param string $filter_type possible values AND or OR (for OR only one filter fragment must match)
*
* @return boolean True if the check succeeded, false otherwise.
*/
function cardquery_apply_filter( $filters, $item, $filter_type) {
  global $session, $c, $request;

  if ( count($filters) == 0 ) return true;

  dbg_error_log("cardquery","Applying filter for item '%s'", $item->dav_name );
  $vcard = new vComponent( $item->caldav_data );

  if ( $filter_type === 'AND' ) {
    return $vcard->TestFilter($filters);
  } else {
    foreach($filters AS $filter) {
      $filter_fragment[0] = $filter;
      if ( $vcard->TestFilter($filter_fragment) ) {
        return true;
      }
    }
    return false;
  }
}


/**
 * Process a filter fragment returning an SQL fragment
 */
$post_filters = array();
$matchnum = 0;
function SqlFilterCardDAV( $filter, $components, $property = null, $parameter = null ) {
  global $post_filters, $target_collection, $matchnum;
  $sql = "";
  $params = array();

  $tag = $filter->GetNSTag();
  dbg_error_log("cardquery", "Processing $tag into SQL - %d, '%s', %d\n", count($components), $property, isset($parameter) );

  $not_defined = "";
  switch( $tag ) {
    case 'urn:ietf:params:xml:ns:carddav:is-not-defined':
      $sql .= $property . 'IS NULL';
      break;

    case 'urn:ietf:params:xml:ns:carddav:text-match':
      if ( empty($property) ) {
        return false;
      }

      $collation = $filter->GetAttribute("collation");
      switch( strtolower($collation) ) {
        case 'i;octet':
          $comparison = 'LIKE';
          break;
        case 'i;ascii-casemap':
        case 'i;unicode-casemap':
        default:
          $comparison = 'ILIKE';
          break;
      }

      $search = $filter->GetContent();
      $match  = $filter->GetAttribute("match-type");
      switch( strtolower($match) ) {
        case 'equals':
          break;
        case 'starts-with':
          $search = $search.'%';
          break;
        case 'ends-with':
          $search = $search.'%';
          break;
        case 'contains':
        default:
          $search = '%'.$search.'%';
          break;
      }

      $pname = ':text_match_'.$matchnum++;
      $params[$pname] = $search;

      $negate = $filter->GetAttribute("negate-condition");
      $negate = ( (isset($negate) && strtolower($negate) ) == "yes" ) ? "NOT " : "";
      dbg_error_log("cardquery", " text-match: (%s%s %s '%s') ", $negate, $property, $comparison, $search );
      $sql .= sprintf( "(%s%s %s $pname)", $negate, $property, $comparison );
      break;

    case 'urn:ietf:params:xml:ns:carddav:prop-filter':
      $propertyname = $filter->GetAttribute("name");
      switch( $propertyname ) {
        case 'VERSION':
        case 'UID':
        case 'NICKNAME':
        case 'FN':
        case 'NOTE':
        case 'ORG':
        case 'URL':
        case 'FBURL':
        case 'CALADRURI':
        case 'CALURI':
          $property = strtolower($propertyname);
          break;

        case 'N':
          $property = 'name';
          break;

        default:
          $post_filters[] = $filter;
          dbg_error_log("cardquery", "Could not handle 'prop-filter' on %s in SQL", $propertyname );
          return false;
      }

      $test_type = $filter->GetAttribute("test");
      switch( $test_type ) {
        case 'allOf':
          $test_type = 'AND';
          break;
        case 'anyOf':
        default:
          $test_type = 'OR';
      }

      $subfilters = $filter->GetContent();
      if (count($subfilters) <= 1) {
        $success = SqlFilterCardDAV( $subfilters[0], $components, $property, $parameter );
        if ( $success !== false ) {
            $sql .= $success['sql'];
            $params = array_merge( $params, $success['params'] );
        }
      } else {
        $subfilter_added_counter=0;
        foreach ($subfilters as $subfilter) {
          $success = SqlFilterCardDAV( $subfilter, $components, $property, $parameter );
          if ( $success === false ) continue; else {
            if ($subfilter_added_counter <= 0) {
              $sql .= '(' . $success['sql'];
            } else {
              $sql .= $test_type . ' ' . $success['sql'];
            }
            $params = array_merge( $params, $success['params'] );
            $subfilter_added_counter++;
          }
        }
        if ($subfilter_added_counter > 0) {
          $sql .= ')';
        }
      }
      break;

    case 'urn:ietf:params:xml:ns:carddav:param-filter':
      $post_filters[] = $filter;
      return false; /** Figure out how to handle PARAM-FILTER conditions in the SQL */
      /*
      $parameter = $filter->GetAttribute("name");
      $subfilter = $filter->GetContent();
      $success = SqlFilterCardDAV( $subfilter, $components, $property, $parameter );
      if ( $success === false ) continue; else {
        $sql .= $success['sql'];
        $params = array_merge( $params, $success['params'] );
      }
      break;
      */

    default:
      dbg_error_log("cardquery", "Could not handle unknown tag '%s' in calendar query report", $tag );
      break;
  }
  dbg_error_log("cardquery", "Generated SQL was '%s'", $sql );
  return array( 'sql' => $sql, 'params' => $params );
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
if ( ! $target_collection->IsAddressbook() ) {
  $request->DoResponse( 403, translate('The addressbook-query report must be run against an addressbook collection') );
}

/**
* @todo Once we are past DB version 1.2.1 we can change this query more radically.  The best performance to
* date seems to be:
*   SELECT caldav_data.*,address_item.* FROM collection JOIN address_item USING (collection_id,user_no)
*         JOIN caldav_data USING (dav_id) WHERE collection.dav_name = '/user1/home/'
*              AND caldav_data.caldav_type = 'VEVENT' ORDER BY caldav_data.user_no, caldav_data.dav_name;
*/

$params = array();
$where = ' WHERE caldav_data.collection_id = ' . $target_collection->resource_id();
if ( is_array($qry_filters) ) {
  dbg_log_array( 'cardquery', 'qry_filters', $qry_filters, true );

  $appended_where_counter=0;
  foreach ($qry_filters as $qry_filter) {
    $components = array();
    $filter_fragment =  SqlFilterCardDAV( $qry_filter, $components );
    if ( $filter_fragment !== false ) {
      $filter_fragment_sql = $filter_fragment['sql'];
      if ( empty($filter_fragment_sql) ) {
        continue;
      }

      if ( $appended_where_counter == 0 ) {
        $where .= ' AND (' . $filter_fragment_sql;
        $params = $filter_fragment['params'];
      } else {
        $where .= ' ' . $qry_filters_combination . ' ' . $filter_fragment_sql;
        $params = array_merge( $params, $filter_fragment['params'] );
      }
      $appended_where_counter++;
    }
  }
  if ( $appended_where_counter > 0 ) {
    $where .= ')';
  }
}
else {
  dbg_error_log( 'cardquery', 'No query filters' );
}

$need_post_filter = !empty($post_filters);
if ( $need_post_filter && ( $qry_filters_combination == 'OR' )) {
  // we need a post_filter step, and it should be sufficient, that only one
  // filter is enough to display the item => we can't prefilter values via SQL
  $where = '';
  $params = array();
  $post_filters = $qry_filters;
}

$sql = 'SELECT * FROM caldav_data INNER JOIN addressbook_resource USING(dav_id)'. $where;
if ( isset($c->strict_result_ordering) && $c->strict_result_ordering ) $sql .= " ORDER BY dav_id";
$qry = new AwlQuery( $sql, $params );
if ( $qry->Exec("cardquery",__LINE__,__FILE__) && $qry->rows() > 0 ) {
  while( $address_object = $qry->Fetch() ) {
    if ( !$need_post_filter || cardquery_apply_filter( $post_filters, $address_object, $qry_filters_combination ) ) {
      if ( $bound_from != $target_collection->dav_name() ) {
        $address_object->dav_name = str_replace( $bound_from, $target_collection->dav_name(), $address_object->dav_name);
      }
      if ( count($address_data_properties) > 0 ) {
        $vcard = new VCard($address_object->caldav_data);
        $vcard->MaskProperties($address_data_properties);
        $address_object->caldav_data = $vcard->Render();
      }
      $responses[] = component_to_xml( $properties, $address_object );
      if ( ($qry_limit > 0) && ( count($responses) >= $qry_limit ) ) {
        break;
      }
    }
  }
}
$multistatus = new XMLElement( "multistatus", $responses, $reply->GetXmlNsArray() );

$request->XMLResponse( 207, $multistatus );
