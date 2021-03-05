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
                    "className": 'btn_eyeicon',
                    columnText: function ( dt, idx, title ) {
                        return (idx+1)+': '+(title ? title : 'details');
                    }
                }],
				deferRender: true,
				dom: '<"toolbar ml-2">Blfrtip',
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
            $("div.toolbar").html(`
                <div class="drop_dowm_select" id="myTable_dropdown">
                    <select name="myTable_dropdown" aria-controls="myTable" class="">
                        <option value="" disabled selected>Filter By Tag</option>
                        <option value="1">Option A</option>
                        <option value="2">Option B</option>
                        <option value="3">Option C</option>
                        <option value="4">Option D</option>
                    </select>
                </div>

                <div class="drop_dowm_select ml-4" id="myTable_dropdown2">
                    <select name="myTable_dropdown2" aria-controls="myTable" class="">
                        <option value="" disabled="" selected="">Filter By Tag</option>
                        <option value="1">Option A</option>
                        <option value="2">Option B</option>
                        <option value="3">Option C</option>
                        <option value="4">Option D</option>
                    </select>
                </div>

                <div class="drop_dowm_select ml-4" id="myTable_dropdown2">
                    <select name="myTable_dropdown2" aria-controls="myTable" class="">
                        <option value="" disabled="" selected="">Filter By Tag</option>
                        <option value="1">Option A</option>
                        <option value="2">Option B</option>
                        <option value="3">Option C</option>
                        <option value="4">Option D</option>
                    </select>
                </div>
            `);

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
                    tr.next('tr').removeClass('child-row');
				}
				else {
					row.child(details(row.data())).show();
					tr.addClass('shown');
                    tr.next('tr').addClass('child-row');
				}
			});
		});
	</script>

	<div class="container">
		<div class="row justify-content-center">
			<div class="col-md-12 organization_data">
				<div class="col-md-12">
					@if ($details['description'] ?? null)
						<p>{!! nl2br($details['description']) !!}</p>
					@else
						<p>{!! nl2br($dataset['Descripton']) !!}</p>
					@endif
                </div>
                <div class="col-md-12">
                    <div class="table-responsive">
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
                            {{--<tfoot>
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
                            </tfoot>--}}
                        </table>
                    </div>
                </div>
			</div>
		</div>
	</div>


	@if ($dataset['Public Note'] ?? null)
        <div class="col-md-12">
            <h4 class="note_bottom">{{ nl2br($dataset['Public Note']) }}</h4>
		</div>
	@endif
    <div class="col-md-12">
        <div class="bottom_lastupdate">
            <p class="lead"><img src="/img/info.png" alt="" title=""> This data comes from <a href="{{ $dataset['Citation URL'] }}" target="_blank">{{ $dataset['Name'] }}</a><span class="float-right" style="font-weight: 300;"><i>Last updated {{ explode(' ', $dataset['Last Updated'])[0] }}</i></span></p>
            <!--<p>{!! nl2br($org['description']) !!}</p>-->
        </div>
	</div>

@endsection
