function unescape(t)
{
	return t.replace(/""/g, '"').replace(/''/g, "'")
}

function toDashDate(d)
{
	if (!d)
		return ''
	//console.log(s)
	y  = d.toString().substr(0, 4)
	m  = d.toString().substr(4, 2)
	d  = d.toString().substr(6, 2)
	return '<span class="text-nowrap">'+y+'-'+m+'-'+d+'</span>';
}

function usToDashDate(d)
{
	if (!d)
		return ''
	console.log(d)
	dd  = d.toString().substr(0, 2)
	m  = d.toString().substr(3, 2)
	y  = d.toString().substr(8, 4)
	//console.log(y,m,d)
	return '<span class="text-nowrap">20'+y+'-'+m+'-'+dd+'</span>';
}

function toFin(d)
{
	return '$' + parseFloat(d).toFixed(0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
}

window.onscroll = function() {scrollFunction()}

function scrollFunction() {
  if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
    $('#return-to-top').show()
  } else {
    $('#return-to-top').hide()
  }
}

function topFunction() {
  document.body.scrollTop = 0; // For Safari
  document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
}

function subscribe_newsletter()
{
	var email = $('#newsletter-email').val()
	$.get(`/api/newsletter_subscription`, {'key': 'as9s8d6d78as6f9sdf876', 'email': email}, function (data) {
		var jj = JSON.parse(data)
		if (jj['success']) {
			$('#newsletter-subs div.row').html('<div class="col-sm-12 col-form-label">Successfull. Thank you for subscribing.</div>');
			$('#newsletter-subs small').html('Your email address');
			$('#newsletter-subs small').attr('style', 'color:white;');
		}
		else {
			$('#newsletter-subs small').html('Failed. Please try again');
			$('#newsletter-subs small').attr('style', 'color:red;');
		}
	})
}

function intWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}


/** maps ******************************************/


var map = null
var zones = {'cd': '#bc2b32', 'ed': '#a881c2', 'pp': '#be7957', 'dsny': '#d2ac6d', 'fb': '#77aa98', 'sd': '#3e7864', 'hc': '#085732', 'cc': '#9abe0c', 'nycongress': '#f3bd1c', 'sa': '#f5912f', 'ss': '#dc2118', 'bid': '#39a6a5', 'nta': '#185892', 'zipcode': '#7a7e5a'}
var filtFields = {'cd': 'nameCol', 'cc': 'nameCol', 'nta': 'nameAlt'}

function mapInit(filters, filterType) {
	//console.log(filters, filterType)
	mapboxgl.accessToken = 'pk.eyJ1Ijoic291bmRwcmVzcyIsImEiOiJjazY1OTF3cXIwbjZyM3BtcGt3Y3F2NjZwIn0.3hmCJsl0_oBUpoVsNJKZjQ';

	var center = (typeof center == 'undefined') ? [-73.957, 40.727] : center;
	var zoom = (typeof zoom == 'undefined') ? 11 : zoom;

	// initial basemap
	map = new mapboxgl.Map({
		container: 'map',
		style: 'mapbox://styles/mapbox/light-v10',
		center: center,
		//pitch: 60,
		zoom: zoom
	});

	map.addControl(new mapboxgl.NavigationControl());

	$('select, option').click(function(e) {
		e.stopPropagation();
	});

	var geojson = {'type': 'FeatureCollection', 'features': []};

	map.on('load', function() {

		for (const [code, clr] of Object.entries(zones)) {
			setBoundary(code, clr, clr);
		}
		
		for (const [code, col] of Object.entries(filters)) {
			setFilter(code, col);
		}
		
		if (typeof filterType == 'undefined')
			window.setTimeout(function (){
				//	enable 1st filter
				if (!$('#map-controls div:nth-child(2) input:checked').length)
						$('#map-controls div:nth-child(2) input').click();
				}, 500
			)
		else
			window.setTimeout(function (){
				//	enable preset filter
					$(`#${filterType}-filter-switch`).click();
				}, 500
			)
		
		map.on('click', function(e) {
			var chckbox = $('#map-controls input:checked')
			var code = chckbox.attr('id').replace('-filter-switch', '')
			var col = chckbox.attr('param')
			var filtField = filtFields[code]

			//console.log('script', code, col)
			// set bbox as 5px reactangle area around clicked point
			var bbox = [
				//[e.point.x - 5, e.point.y - 5],
				//[e.point.x + 5, e.point.y + 5]
				[e.point.x, e.point.y],
				[e.point.x, e.point.y]
			];
			var features = map.queryRenderedFeatures(bbox, {
				layers: [code + 'FHH']
			});
			 
			// Run through the selected features and set a filter
			// to match features with unique FIPS codes to activate
			// the `counties-highlighted` layer.
			var filter = features.reduce(
				function(memo, feature) {
					memo.push(feature.properties[filtField]);
					return memo;
				},
				['in', filtField]
			);

			mapAction(filter, code, col);		// maps are used in orgsection.blade and districts.blade, each map has its own mapAction
			
			map.setFilter(code + 'FH', filter);
		});
		
	});
	
}

function setBoundary(code, lineClr, symbClr) {

    map.addSource(code, {
        type: "geojson",
        data: `/data/${code}.geojson`
    });

    map.addLayer({
        "id": code + 'L',
        "type": "line",
        "source": code,
        "layout": {
			'visibility': 'none',
        },
        "paint": {
            "line-color": lineClr,
            "line-width": 1
        }
    });

    map.addLayer({
        "id": code + 'S',
        "type": "symbol",
        "source": code,
        "layout": {
            'text-field': '{nameCol}',
			'visibility': 'none',
            'text-size': {
                "base": 1,
                "stops": [
                    [12, 12],
                    [16, 16]
                ]
            },
        },
        "paint": {
            "text-color": symbClr,
            "text-halo-color": "hsl(0, 0%, 100%)",
            "text-halo-width": 0.5,
            "text-halo-blur": 1
        }
    });
	
	$(`#${code}-switch`).change(function() {
		if ($(this).is(':checked')) {
			map.setLayoutProperty(code + 'L', 'visibility', 'visible');
			map.setLayoutProperty(code + 'S', 'visibility', 'visible');
		} else {
			map.setLayoutProperty(code + 'L', 'visibility', 'none');
			map.setLayoutProperty(code + 'S', 'visibility', 'none');
		}
	});
	
	$(`label[for="${code}-switch"] hr`).attr('style', `background-color: ${lineClr};`);
}

function setFilter(code, col) {
	var clr = zones[code]
	var filtField = filtFields[code]
    map.addLayer({
			"id": code + 'FH',
			"type": "fill",
			"source": code,
			"layout": {
				'visibility': 'none',
			},
			'paint': {
				'fill-outline-color': clr,
				'fill-color': clr,
				'fill-opacity': 0.4
			},
			'filter': ['in', filtField, '']
		},
		'settlement-label'
    );

	var filter = ['has', 'nameCol']
	
	if (typeof datatable != 'undefined') {
		datatable.columns([col]).every(function (c,a,i) {
			var vv = []
			this.data().each(function (d, j) {
				d = typeof d == 'string' ? d.replace(/<[^>]+>/gi, '') : d
				if (d)
					vv.push(d)
			})
			vv = [...new Set(vv)]
			filter = vv.reduce(
				function(memo, v) {
					memo.push(v);
					return memo;
				},
				['in', filtField]
			);
			console.log(filter);
		});
	}
	
	map.addLayer({
			"id": code + 'FHH',
			"type": "fill",
			"source": code,
			"layout": {
				'visibility': 'none',
			},
			'paint': {
				'fill-color': clr,
				'fill-opacity': 0.3
			},
			'filter': filter
		},
		'settlement-label'
	);
	map.addLayer({
			"id": code + 'FL',
			"type": "line",
			"source": code,
			"layout": {
				'visibility': 'none',
			},
			"paint": {
				"line-color": clr,
				"line-width": 1
			},
			'filter': filter
		},
		'settlement-label'
	);
	map.addLayer({
			"id": code + 'FS',
			"type": "symbol",
			"source": code,
			"layout": {
				'text-field': '{nameCol}',
				'visibility': 'none',
				'text-size': {
					"base": 1,
					"stops": [
						[12, 12],
						[16, 16]
					]
				},
			},
			"paint": {
				"text-color": clr,
				"text-halo-color": "hsl(0, 0%, 100%)",
				"text-halo-width": 0.5,
				"text-halo-blur": 1
			},
			'filter': filter
		},
		'settlement-label'
	);
		
	$(`#${code}-filter-switch`).change(function() {
		//console.log($(this))
		if ($(this).is(':checked')) {
			map.setLayoutProperty(code + 'FL', 'visibility', 'visible');
			map.setLayoutProperty(code + 'FS', 'visibility', 'visible');
			//map.setLayoutProperty(code + 'FF', 'visibility', 'visible');
			map.setLayoutProperty(code + 'FH', 'visibility', 'visible');
			map.setLayoutProperty(code + 'FHH', 'visibility', 'visible');
			
			['cd', 'cc', 'nta'].forEach(function(i) {
				if ((i != code) && ($(`#${i}-filter-switch`).length)) {
					map.setLayoutProperty(i + 'FL', 'visibility', 'none');
					map.setLayoutProperty(i + 'FS', 'visibility', 'none');
					//map.setLayoutProperty(i + 'FF', 'visibility', 'none');
					map.setLayoutProperty(i + 'FH', 'visibility', 'none');
					map.setLayoutProperty(i + 'FHH', 'visibility', 'none');
				}
			});
		}
	});
	
}


/** share button ******************************************/

function copyLink() {
	var el = document.getElementById("details-permalink");
	el.select();
	el.setSelectionRange(0, 99999);
	document.execCommand("copy");
	console.log(el.value)
	$('.share_icon_container').popover('show')
	setTimeout(function(){
		$('.share_icon_container').popover('hide')
	}, 3000);
}
