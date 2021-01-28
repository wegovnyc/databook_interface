@extends('layout')

@section('menubar')
	@include('sub.menubar', ['active' => 'orgs'])
@endsection

@section('content')
	@include('sub.orgheader', ['active' => $section])

	{{-- 
		$id, $org, $section,$slist => $ds->list, $icons => $ds->socicons, 
		$url => $model->url(.....),
	--}}

	{{-- 
		$details => [
			'table' => 'nycjobs',
			'hdrs' => ['Job ID', 'Title', 'Job Category', 'Salary From', 'Salary To', 'Last Updated'], 
			'visible' => [true, true, false, true, false, true],
			'flds' => [
					'function (r) { return `<a href="https://a127-jobs.nyc.gov/index_new.html?keyword=${r[\"Job ID\"]}">${r[\"Job ID\"]}</a>` }', 
					'"Business Title"', '"Job Category"', '"Salary Range From"', '"Salary Range To"', '"Posting Updated"'
				], 
			'filters' => [2 => null, 3 => null],
			'details' => ['Job ID' => 'Job ID'],
			'detFlag' => 1,
			'fltsCols' => '2,3',
		]
	--}}
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.5/js/dataTables.buttons.min.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.5/js/buttons.colVis.min.js"></script>
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.5/css/buttons.dataTables.min.css"/>
	
	<script>
		function details(d) {
			return '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">'+
			  @foreach ((array)$details['details'] as $h=>$f)
				(d["{{ $f }}"] ? '<tr><td>{{ $h }}:</td><td>'+d["{{ $f }}"]+'</td></tr>' : '') +
			  @endforeach
			'</table>';
		}		
		
		var table = null
		$(document).ready(function() {
			table = $('#myTable').DataTable({
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
							@if ($details['detFlag'])
								{
									"className": 'details-control',
									"orderable": false,
									"data":  null,
									"defaultContent": ''
								},
							@endif
							@foreach ($details['flds'] as $i=>$f)
							  @if ($i > 0)
							     ,
							  @endif
							  {
								data: {!! $f !!}, 
								@if ($details['visible'][$i])
									visible: true
								@else
									visible: false
							    @endif
							  }
							@endforeach
						],
				
				initComplete: function () {
					this.api().columns([{{ $details['fltsCols'] }}]).every(function () {
						var column = this;
						var select = $('<select class="filter" id="filter-' + column[0][0] + '"><option value=""></option></select>')
							.appendTo($(column.footer()).empty())
							.on('change', function () {
								//var val = $.fn.dataTable.util.escapeRegex(
									//$(this).val()
								//);
								var val = $(this).val()
								column
									.search(val ? val : '', false, false)
									.draw();
							});
						
						var tt = []
						dd = column.data()
						
						column.data().each(function (d, j) {
							d = typeof d == 'string' ? d.replace(/<[^>]+>/gi, '') : d
							tt.push(d)
						})
						tt = [...new Set(tt)]
						
						tt.sort().forEach(function (d, j) {
							select.append('<option value="'+d+'">'+d+'</option>')
						});
					});
					
					@foreach ($details['filters'] as $i=>$v)
						@if ($v)
							setTimeout(function(){
								$('#filter-{{ $i }}').find('[value*="{!! $v !!}"]').prop('selected',true).trigger('change')
							}, 500 + 1000 * {{ $i }});
						@endif
					@endforeach
				}
			});
			
			$('#filter-1').find('[value*="20190619"]').prop('selected',true).trigger('change');
			
			$('a.toggle-vis').on('click', function (e) {
				e.preventDefault();
				var column = table.column($(this).attr('data-column'));
				column.visible(!column.visible());
			});
			
			$('#myTable tbody').on('click', 'td.details-control', function () {
				var tr = $(this).closest('tr');
				var row = table.row(tr);
		 
				if (row.child.isShown()) {
					row.child.hide();
					tr.removeClass('shown');
				}
				else {
					row.child(details(row.data())).show();
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
					Toggle column: 
					@foreach ($details['hdrs'] as $i=>$name)
					  @if ($i == 0)
						<a class="toggle-vis" data-column="{{ $i + $details['detFlag'] }}">{{ $name }}</a> 
					  @else	
						- <a class="toggle-vis" data-column="{{ $i + $details['detFlag'] }}">{{ $name }}</a> 
					  @endif
				    @endforeach
				</div>
				-->
				<table id="myTable" class="display table-striped table-hover" style="width:100%;">
					<thead>
						<tr>
						  @if ($details['detFlag'])
							<th></th>
						  @endif
						  @foreach ($details['hdrs'] as $name)
							<th>{{ $name }}</th>
						  @endforeach
						</tr>
					</thead>
					<tfoot>
						<tr>
						  @if ($details['detFlag'])
							<th></th>
						  @endif
						  @foreach ($details['hdrs'] as $i=>$name)
							@if (isset($details['filters'][$i + $details['detFlag']]))
							  <th class="filter"></th>
							@else  
							  <th></th>
							@endif
						  @endforeach
						</tr>
					</tfoot>
				</table>
			</div>
		</div>
	</div>


	@if ($dataset['Public Note'] ?? null)
		<div style="padding: 0.75rem 1rem!important;">
			<p>{{ nl2br($dataset['Public Note']) }}</p>
		</div>
	@endif
	<div class="jumbotron mb-5" style="padding: 0.75rem 1rem!important;">
	  <p class="lead">This data comes from <strong><a href="{{ $dataset['Citation URL'] }}" target="_blank">{{ $dataset['Name'] }}</a></strong>. Last updated {{ explode(' ', $dataset['Last Updated'])[0] }}.</p>
	  <p>{!! nl2br($org['description']) !!}</p>
	</div>

@endsection