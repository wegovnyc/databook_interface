@extends('layout')

@section('menubar')
	@include('sub.menubar', ['active' => 'orgs'])
@endsection

@section('content')
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.5/js/dataTables.buttons.min.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.5/js/buttons.colVis.min.js"></script>
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.5/css/buttons.dataTables.min.css"/>

	<script>
		function details(d) {
			console.log(d)
			return '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">'+
				(d.description ? '<tr><td>Full description:</td><td>'+d.description+'</td></tr>' : '') +
				(d.main_address ? '<tr><td>Address:</td><td>'+d.main_address+'</td></tr>' : '') +
				(d.main_phone ? '<tr><td>Phone:</td><td>'+d.main_phone+'</td></tr>' : '') +
				(d.Twitter ? '<tr><td>Twitter:</td><td>'+d.Twitter+'</td></tr>' : '') +
				(d.Facebook ? '<tr><td>Facebook:</td><td>'+d.Facebook+'</td></tr>' : '') +
			'</table>';
		}		
		
		var table = null
		$(document).ready(function() {
			table = $('#myTable').DataTable( {
				ajax: {
					url: '{!! $url !!}',	
					dataSrc: 'rows'
				},
				buttons: [{
						extend: 'colvis',
						columnText: function ( dt, idx, title ) {
							return (idx+1)+': '+(title ? title : 'details');
						}
					}],
				dom: 'Blfrtip',
				columns: [
							{
								"className": 'details-control',
								"orderable": false,
								"data":  null,
								"defaultContent": ''
							},
							{data: function (r) { 
													return `<a href="/organization/${r["id"]}">${r["name"]}</a>`
												}}, 
							{data: 'Type'},
							{data: function (r) {
													var t = ''
													if (!r['tags'])
														return ''
													JSON.parse(unescape(r['tags'])).forEach( function (d, j) {
														t = t+'<span class="badge badge-info">'+d+'</span>'
													})
													return t
												}}, 
							{data: function (r) {
													return r['description'].substr(0,100)+
														   (r['description'].length > 100 ? '...' : '')
												}}
						],
				
				initComplete: function () {
					this.api().columns([2]).every(function () {
						var column = this;
						var select = $('<select class="filter" id="filter-' + column[0][0] + '"><option value=""></option></select>')
							.appendTo( $(column.footer()).empty() )
							.on('change', function () {
								var val = $.fn.dataTable.util.escapeRegex(
									$(this).val()
								);
								column
									.search(val ? '^'+val+'$' : '', true, false)
									.draw();
							});
						column.data().unique().sort().each(function (d, j) {
							select.append('<option value="'+d+(d == 'City Agency' ? '" selected>' : '">')+d+'</option>')
						});
						
						setTimeout(function(){
							column
								.search('^City Agency$', true, false)
								.draw();
						}, 1000);
					});
					
					
					this.api().columns([3]).every(function () {
						var column = this;
						var select = $('<select class="filter"><option value=""></option></select>')
							.appendTo( $(column.footer()).empty() )
							.on('change', function () {
								var val = $.fn.dataTable.util.escapeRegex(
									$(this).val()
								);
								console.log('>'+val+'<');
								column
									.search(val ? val : '', false, false)
									.draw();
							});
						
						var tt = []
						dd = column.data()
						
						column.data().each(function (d, j) {
							pp = />([^<]+)</g.exec(d)
							if (pp)
							{
								pp.forEach(function (t, i) {
									if (i > 0)
									{
										tt.push(t)
									}
								});
							}
						})
						tt = [...new Set(tt)]
						
						console.log(tt)
						tt.sort().forEach(function (d, j) {
							select.append( '<option value="'+d+'">'+d+'</option>' )
						} );
					} );
				}
			});
			$('a.toggle-vis').on( 'click', function (e) {
				e.preventDefault();
				var column = table.column( $(this).attr('data-column') );
				column.visible( ! column.visible() );
			});
			
			$('#myTable tbody').on('click', 'td.details-control', function () {
				var tr = $(this).closest('tr');
				var row = table.row( tr );
		 
				if ( row.child.isShown() ) {
					row.child.hide();
					tr.removeClass('shown');
				}
				else {
					row.child( details(row.data()) ).show();
					tr.addClass('shown');
				}
			});
		});
	</script>

	<div class="container">
		<div class="row justify-content-center">
			<div class="col-12 py-3">
				<!--
				<div class="toggle-box">
					Toggle column: <a class="toggle-vis" data-column="1">Name</a> - <a class="toggle-vis" data-column="2">Type</a> - <a class="toggle-vis" data-column="3">Tags</a> - <a class="toggle-vis" data-column="4">Description</a>
				</div>
				-->
				<table id="myTable" class="display table-striped table-hover" style="width:100%">
					<thead>
						<tr>
							<th></th>
							<th>Name</th>
							<th>Type</th>
							<th>Tags</th>
							<th>Description</th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th></th>
							<th></th>
							<th class="filter"></th>
							<th class="filter"></th>
							<th></th>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>
	</div>

@endsection
