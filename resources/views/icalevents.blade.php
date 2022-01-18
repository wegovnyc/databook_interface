BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Wegov Databook//Upcoming Events//EN
CALSCALE:GREGORIAN
METHOD:PUBLISH
X-WR-CALNAME:{{ ($agencyName ?? null) ? $agencyName . ' - ' : '' }}Events from CROL via WeGovNYC
X-WR-CALDESC:{{ ($agencyName ?? null) ? $agencyName . ' - ' : '' }}Events from CROL via WeGovNYC
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
LAST-MODIFIED;TZID=America/New_York:{{ date('Ymd\THis\Z', strtotime($dataset['Last Updated']) + 60) }}
DTSTAMP;TZID=America/New_York:{{ date('Ymd\THis\Z', strtotime($ev['StartDate'])) }}
DTSTART;TZID=America/New_York:{{ date('Ymd\THis\Z', strtotime($ev['EventDate'])) }}
DTEND;TZID=America/New_York:{{ date('Ymd\THis\Z', strtotime($ev['EventDate'] . ' +1 hour')) }}
SUMMARY:{!! $ev['TypeOfNoticeDescription'] !!}: {!! html_entity_decode(str_replace('', '', $ev['ShortTitle'])) !!}
UID:{{ $ev['RequestID'] }}-event@databook.wegov.nyc
@php 
	$d = trim(preg_replace(['~<[^>]+>~si', '~[ \s]+~si'], ['', ' '], html_entity_decode(str_replace('', '', $ev['AdditionalDescription1']))));
	$descr = ($d ? $d . '\n' : '') . ($ev['wegov-org-name'] ? "Agency: {$ev['wegov-org-name']} (" . route('orgProfile', ['id' => $ev['wegov-org-id']]) . ')\n' : '') . 'More Info: https://a856-cityrecord.nyc.gov/RequestDetail/' . $ev['RequestID'];
@endphp
DESCRIPTION:{!! $descr !!}
URL:https://a856-cityrecord.nyc.gov/RequestDetail/{{ $ev['RequestID'] }}
ORGANIZER:{{ $ev['wegov-org-name'] }}
@if($ev['EventStreetAddress1'] && ($ev['EventStreetAddress1'] <> 'Address Not Listed In The Dropdown'))@php $rr = [$ev["EventStreetAddress1"], $ev["EventStreetAddress2"], $ev["EventCity"], $ev["EventStateCode"], $ev["EventZipCode"]]; $rr = array_diff($rr, ['']); @endphpLOCATION:{{ implode(', ', $rr) }}
@endif
SEQUENCE:0
END:VEVENT
@endforeach
END:VCALENDAR