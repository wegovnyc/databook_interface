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
	m  = d.toString().substr(0, 2)
	dd  = d.toString().substr(3, 2)
	y  = d.toString().substr(8, 4)
	//console.log(y,m,d)
	return '<span class="text-nowrap">20'+y+'-'+m+'-'+dd+'</span>';
}

function toFin(d)
{
	return '$' + parseFloat(d).toFixed(0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
}

function toFinShort(d)
{
	d = parseFloat(d)
	if (d < 1000)
		return '$' + d.toFixed(0)
	var units = {1: 'K', 2: 'M', 3: 'B'}
	for (let u = 1; u <= 3; u++) {
		d = d / 1000
		if (d < 1000) {
			if (d >= 100) {
				return '$' + d.toFixed(0) + units[u]
			} else if (d >= 10) {
				return '$' + d.toFixed(1) + units[u]
			} else if (d >= 1) {
				return '$' + d.toFixed(2) + units[u]
			}
		}
	}
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

function newMap() {
	mapboxgl.accessToken = 'pk.eyJ1Ijoic291bmRwcmVzcyIsImEiOiJjazY1OTF3cXIwbjZyM3BtcGt3Y3F2NjZwIn0.3hmCJsl0_oBUpoVsNJKZjQ';
	
	var center = (typeof center == 'undefined') ? [-73.957, 40.727] : center;
	var zoom = (typeof zoom == 'undefined') ? 11 : zoom;
	
	//console.log(center, zoom);
	// initial basemap
	map = new mapboxgl.Map({
		container: 'map',
		style: 'mapbox://styles/mapbox/light-v10',
		center: center,
		//pitch: 60,
		zoom: zoom
	});

	map.addControl(new mapboxgl.NavigationControl());
	
}


/** org section map ******************************************/

function orgSectionMapInit(filters, filterType) {
	//console.log(filters, filterType)
	newMap();
	
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
						$('#map-controls div:nth-child(2) input')[0].click();
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
/** /org section map ******************************************/



/** projects map ******************************************/
var popup = null
function projectsMapPopup(e) {
	var pr = e.features[0].properties;
	var description = `
<table><tbody>
	<tr><th scope="row">Name</th><td><a href="/capitalprojects/${pr.PRJ_ID}">${pr.NAME}</a></td></tr>
	<tr><th scope="row">Agency</th><td>${pr.AGENCY}</td></tr>
	<tr><th scope="row">Category</th><td>${pr.CATEGORY}</td></tr>
	<tr><th scope="row">Planned Cost</th><td>$${pr.PLANNEDCOST}</td></tr>
	<tr><th scope="row">Start</th><td>${pr.START_CURR}</td></tr>
	<tr><th scope="row">End</th><td>${pr.END_CURR}</td></tr>
</tbody></table>`;
			 
	map.fitBounds([
		[pr.W,pr.S], // southwestern corner of the bounds
		[pr.E,pr.N] // northeastern corner of the bounds
	]);
	
	popup = new mapboxgl.Popup()
		.setLngLat(e.lngLat)
		.setHTML(description)
		.addTo(map);
}

function projectsMapInit() {
	//console.log(filters, filterType)
	newMap();
	
	map.on('load', function() {
        map.addSource('route', {
				"type": "geojson",
				"data": {
					"type": "FeatureCollection",
					"features": [{"type":"Feature","properties":{"custom_color":"#ccc"},"geometry":{"type":"Point","coordinates":["-73.95098200","40.82387280"]}}]
				}
			});
		
        map.addLayer({
            'id': 'streets',
            'type': 'line',
            'source': 'route',
            'layout': {
                'line-join': 'round',
                'line-cap': 'round'
            },
            'paint': {
                'line-color': ['get', 'custom_color'],
                'line-width': 6
            },
			'filter': ['==', '$type', 'LineString']
        });
		
		map.addLayer({
			'id': 'markers',
			'type': 'circle',
			'source': 'route',
			'paint': {
				'circle-radius': 6,
				'circle-color': ['get', 'custom_color']
			},
			'filter': ['==', '$type', 'Point']
		});		
		
		map.on('click', 'streets', function (e) { projectsMapPopup(e); });
		map.on('click', 'markers', function (e) { projectsMapPopup(e); });
		 
		// Change the cursor to a pointer when the mouse is over the places layer.
		map.on('mouseenter', 'streets', function () { map.getCanvas().style.cursor = 'pointer'; });
		map.on('mouseenter', 'markers', function () { map.getCanvas().style.cursor = 'pointer'; });
		 
		// Change it back to a pointer when it leaves.
		map.on('mouseleave', 'streets', function () { map.getCanvas().style.cursor = ''; });		
		map.on('mouseleave', 'markers', function () { map.getCanvas().style.cursor = ''; });
		
		for (const [code, clr] of Object.entries(zones)) {
			setBoundary(code, clr, clr);
		}
		
	});
}


function projectsMapDrawFeatures(dd) {
	// calculate bounds
	var bounds = [[360, 180], [-360, -180]];
	// https://javier.xyz/cohesive-colors/
	var colors = ['#ecd078', '#d95b43', '#c02942', '#542437', '#53777a', '#f5ae33', '#99ac40', '#ff7c7c', '#78c0a8', '#7a6a53', '#6c5b7b', '#c06c84', '#d2ff0f', '#f2c45a', '#3b2d38', '#b8af03', '#d1e751', '#ff3a31', '#99b59a', '#676970', '#ecd078', '#618eff', '#7dffff', '#f07241', '#bcbcbc'];
	dd.forEach(function (el, i) {
		bounds[0][0] = Math.min(bounds[0][0], el.properties.W - 0.03);
		bounds[0][1] = Math.min(bounds[0][1], el.properties.S - 0.03);
		bounds[1][0] = Math.max(bounds[1][0], el.properties.E + 0.03);
		bounds[1][1] = Math.max(bounds[1][1], el.properties.N + 0.03);
		//dd[i].properties.custom_color = colors[i % 25]
		dd[i].properties.custom_color = colors[4]
	});
	if (bounds[0][0] == 360)
		bounds = [[-74.05395, 40.68309], [-73.944433, 40.797808]]
	var src = map.getSource('route')
	src.setData({"type": "FeatureCollection", "features": dd});
	if (popup)
		popup.remove();
	map.fitBounds(bounds);
}

function fitBounds(bounds) {
	map.fitBounds(bounds);
}


/** /projects map ******************************************/


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
