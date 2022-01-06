BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Wegov Databook//Upcoming Events//EN
CALSCALE:GREGORIAN
METHOD:PUBLISH
X-WR-CALNAME:NYC Events
X-WR-CALDESC:NYC Events
X-WR-TIMEZONE:America/New_York
BEGIN:VTIMEZONE
TZID:America/New_York
X-LIC-LOCATION:America/New_York
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
@foreach($data as $ev)
BEGIN:VEVENT
LAST-MODIFIED:{{ date('Ymd\THis\Z', strtotime($dataset['Last Updated'])) }}
DTSTAMP:{{ date('Ymd\THis\Z', strtotime($dataset['Last Updated'])) }}
DTSTART:{{ date('Ymd\THis\Z', strtotime($ev['EventDate'])) }}
DTEND:{{ date('Ymd\THis\Z', strtotime($ev['EventDate'] . ' +1 hour')) }}
SUMMARY:{!! html_entity_decode($ev['ShortTitle']) !!}@if($ev['wegov-org-name']) : {!! html_entity_decode($ev['wegov-org-name']) !!}@endif

UID:{{ $ev['RequestID'] }}-event@databook.wegov.nyc
DESCRIPTION:https://a856-cityrecord.nyc.gov/RequestDetail/{{ $ev['RequestID'] }}
URL:https://a856-cityrecord.nyc.gov/RequestDetail/{{ $ev['RequestID'] }}
@if($ev['Email'])ORGANIZER;CN={{ $ev['ContactName'] }}:MAILTO:{{ $ev['Email'] }}
@elseif($ev['ContactPhone'])ORGANIZER;CN={{ $ev['ContactName'] }}:TEL:{{ $ev['ContactPhone'] }}
@endif
@if($ev['EventStreetAddress1'] && ($ev['EventStreetAddress1'] <> 'Address Not Listed In The Dropdown'))@php $rr = [$ev["EventStreetAddress1"], $ev["EventStreetAddress2"], $ev["EventCity"], $ev["EventStateCode"], $ev["EventZipCode"]]; $rr = array_diff($rr, ['']); @endphpLOCATION:{{ implode(', ', $rr) }}
@endif
SEQUENCE:0
END:VEVENT
@endforeach
END:VCALENDAR