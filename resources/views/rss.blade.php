{!! '<' . '?xml version="1.0" encoding="UTF-8" ?>' !!}
<rss version="2.0">

<channel>
  <title>{{ ($agencyName ?? null) ? $agencyName . ' - ' : '' }}News from CROL via WeGovNYC</title>
  <link>{{ route('root') }}</link>
  <description>{{ ($agencyName ?? null) ? $agencyName . ' - ' : '' }}News from CROL via WeGovNYC</description>
@foreach($data as $ev)
  <item>
	<category>{{ $ev['wegov-org-name'] }}</category>
	<description>{{ trim(preg_replace(['~<[^>]+>~si', '~[ \s]+~si'], ['', ' '], html_entity_decode($ev['AdditionalDescription1']))) }}</description>
	<guid>{{ $ev['RequestID'] }}</guid>
	<link>https://a856-cityrecord.nyc.gov/RequestDetail/{{ $ev['RequestID'] }}</link>
	<pubDate>{{ date('D, d M Y', strtotime($ev['StartDate'])) }}</pubDate>
	<source>{{ ($ev['wegov-org-name'] ?? null) ? $ev['wegov-org-name'] . ' - ' : '' }}CROL News Feed via WeGovNYC</source>
	<title>{!! $ev['TypeOfNoticeDescription'] !!}: {!! html_entity_decode($ev['ShortTitle']) !!}</title>
  </item>
@endforeach
</channel>
</rss>