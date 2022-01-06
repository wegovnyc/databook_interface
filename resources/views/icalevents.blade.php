BEGIN:VCALENDAR
VERSION:2.0
METHOD:PUBLISH
PRODID:-//Wegov Databook/Upcoming Events//EN
@foreach($data as $ev)
BEGIN:VEVENT
DTSTART:{{ date('Ymd\THis\Z', strtotime($ev['StartDate'])) }}
DTEND:{{ date('Ymd\THis\Z', strtotime($ev['EndDate'])) }}
SUMMARY:{!! html_entity_decode($ev['ShortTitle']) !!}|{!! html_entity_decode($ev['wegov-org-name']) !!}|{{ $ev['EventDate'] }}
UID:{{ $ev['RequestID'] }}
URL:https://a856-cityrecord.nyc.gov/RequestDetail/{{ $ev['RequestID'] }}
@if($ev['Email'])ORGANIZER;CN={{ $ev['ContactName'] }}:MAILTO:{{ $ev['Email'] }}
@elseif($ev['ContactPhone'])ORGANIZER;CN={{ $ev['ContactName'] }}:TEL:{{ $ev['ContactPhone'] }}
@endif
@if($ev['EventStreetAddress1'] && ($ev['EventStreetAddress1'] <> 'Address Not Listed In The Dropdown'))@php $rr = [$ev["EventStreetAddress1"], $ev["EventStreetAddress2"], $ev["EventCity"], $ev["EventStateCode"], $ev["EventZipCode"]]; $rr = array_diff($rr, ['']); @endphpLOCATION:{{ implode(', ', $rr) }}
@endif
END:VEVENT
@endforeach
END:VCALENDAR