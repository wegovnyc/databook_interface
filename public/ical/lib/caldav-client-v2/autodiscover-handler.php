<?php
/**
 * Errors are sent as an XML document.
 * @param int $code
 * @param string $message
 * @param string $debugdata
 */

require_once('HTTPAuthSession.php');
$session = new HTTPAuthSession();

require_once('CalDAVRequest.php');
$request = new CalDAVRequest();

if ( !isset($c->enable_autodiscover) || ! $c->enable_autodiscover ) {
  $request->DoResponse( 404 );
  exit(0); // unneccessary
}

$ns_outlook_req_2006 = "http://schemas.microsoft.com/exchange/autodiscover/outlook/requestschema/2006";
$ns_exchange_resp_2006 = "http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006";
$ns_outlook_resp_2006a = "http://schemas.microsoft.com/exchange/autodiscover/outlook/responseschema/2006a";

function errorResponse( $code, $message, $debugdata = '' ) {
  global $request, $ns_exchange_resp_2006;

  $error_time_id = time();
  $error_time = gmdate('h:i:s', $error_time_id);
  $response = <<<ERROR
<?xml version="1.0" encoding="utf-8" ?>
<Autodiscover xmlns="$ns_exchange_resp_2006">
 <Response>
  <Error Time="$error_time" Id="$error_time_id">
   <ErrorCode>$code</ErrorCode>
   <Message>$message</Message>
   <DebugData>$debugdata</DebugData>
  </Error>
 </Response>
</Autodiscover>
ERROR;

  $request->DoResponse( $code, $response, 'text/xml; charset="utf-8"' );
  exit(0); // unneccessary
}


if ( !isset($request->xml_tags) )
  errorResponse( 406, translate("Body contains no XML data!") );

$position = 0;
$xmltree = BuildXMLTree( $request->xml_tags, $position);
if ( !is_object($xmltree) )
  errorResponse( 406, translate("REPORT body is not valid XML data!") );

$user_email = $xmltree->GetPath(
'/'.$ns_outlook_req_2006.':Autodiscover'.
'/'.$ns_outlook_req_2006.':Request'.
'/'.$ns_outlook_req_2006.':EMailAddress');
if ( count($user_email) < 1 ) errorResponse(500,"User not found.");
$user_email = $user_email[0]->GetContent();

$principal = new Principal();

$reply = new XMLDocument( array( $ns_outlook_resp_2006a => "" ) );
$response = array(
  new XMLElement( 'User',
    array(
      new XMLElement( 'DisplayName', $principal->$fullname ),
      new XMLElement('AutoDiscoverSMTPAddress',$user_email),
    )
  )
);

$response[] = new XMLElement('Account',
  array(
    new XMLElement( 'AccountType', 'email' ),  // The only allowed accounttype
    new XMLElement('Action','settings'),
    new XMLElement('Protocol',
      array(
        new XMLElement('Type', 'DAV'),
        new XMLElement('Server', $c->domain_name ),
        new XMLElement('LoginName', $principal->username())
      )
    )
  )
);

$autodiscover = new XMLElement( "Autodiscover", $responses, $reply->GetXmlNsArray(), $ns_exchange_resp_2006 );

$request->XMLResponse( 207, $autodiscover );
