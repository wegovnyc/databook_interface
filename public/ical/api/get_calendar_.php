<?php
/*
webagenda-viewer (calendar viewer - ical & dav)
 
Copyright (C) 2017  Noël Martinon

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
?>

<?php
require_once('../inc/config.inc');
require_once('../inc/common.inc');

$id = $_REQUEST["q"];
#$start = $_REQUEST["start"];
#$end = $_REQUEST["end"];

$cal_url = ($cal_https)?"https://":"http://";
$cal_url .= $cal_server;

if ($cal_type === "caldav") {
	include_once('../lib/caldav-client-v2/caldav-client-v2.php');
	
	$cal_url .= "/dav/$email/Calendar";
	$cdc = new CalDAVClient($cal_url, $cal_user, $cal_pass);
	$details = $cdc->GetCalendarDetails();
	if (empty($details->url)) {http_response_code(500); exit;}

	$events = $cdc->GetEvents($start, $end);
	if (empty($events)) return;

	$ret_cal="";
	foreach($events as $key => $value) {
		if (!empty($events[$key]["data"]))
			$ret_cal .= $events[$key]["data"];
	}
	if (empty($ret_cal)) return;

	$ret_cal = str_replace("BEGIN:VCALENDAR", "", $ret_cal);
	$ret_cal = str_replace("END:VCALENDAR", "", $ret_cal);
	$ret_cal = "BEGIN:VCALENDAR".$ret_cal."END:VCALENDAR";
	echo $ret_cal;

}
else if ($cal_type === "ical") {	
	#$cal_url .= "/home/$email/Calendar.ics";
	$cal_url = "http://3.142.130.169/notices/events.ics";
	
	$crl = curl_init();

	curl_setopt($crl, CURLOPT_URL, $cal_url);
	curl_setopt($crl, CURLOPT_USERPWD, $cal_user.":".$cal_pass);
	curl_setopt($crl, CURLOPT_CONNECTTIMEOUT ,30); 
	curl_setopt($crl, CURLOPT_TIMEOUT, 30);
	curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($crl, CURLOPT_SSL_VERIFYHOST,  0);
	
	curl_exec($crl);
	
	$httpCode = curl_getinfo($crl, CURLINFO_HTTP_CODE);
	
	http_response_code($httpCode);
	
	curl_close($crl);
}
else http_response_code(500);
?>
