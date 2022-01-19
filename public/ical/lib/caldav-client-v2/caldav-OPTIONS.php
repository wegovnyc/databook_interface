<?php
/**
* CalDAV Server - handle OPTIONS method
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst .Net Ltd, Morphoss Ltd <http://www.morphoss.com/>
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/
dbg_error_log("OPTIONS", "method handler");

include_once('DAVResource.php');
$resource = new DAVResource($request->path);

/**
 * The spec calls for this to be controlled by 'read' access, but we expand
 * that a little to also allow read-current-user-privilege-set since we grant that
 * more generally and Mozilla attempts this and gets upset...
 */
$resource->NeedPrivilege( array('DAV::read','DAV::read-current-user-privilege-set'), true );

if ( !$resource->Exists() ) {
  $request->DoResponse( 404, translate("No collection found at that location.") );
}

$allowed = implode( ', ', array_keys($resource->FetchSupportedMethods()) );
header( 'Allow: '.$allowed);

$request->DoResponse( 200, "" );

