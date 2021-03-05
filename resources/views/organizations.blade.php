@extends('layout')

@section('menubar')
	@include('sub.menubar', ['active' => 'orgs'])
@endsection

@section('content')
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.5/js/dataTables.buttons.min.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.5/js/buttons.colVis.min.js"></script>
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.5/css/buttons.dataTables.min.css"/>

	<script>
		function tagFlt(e, tag) {
			console.log(tag)
			$('#filter-tags').val(tag)
			$('#filter-tags').trigger('change')
			e.preventDefault()
		}

		var table = null
		$(document).ready(function() {
			table = $('#orgsTable').DataTable( {
				pageLength: 18,
				deferRender: true,
				order: [[2, 'asc']],
				ordering: false,
				dom: '<"toolbar">frtip',
				ajax: {
					url: '{!! $url !!}',
					dataSrc: 'rows'
				},
				columns: [
                    {data: 'id'},
                    {data: function (r) {
                        return r['Logo']
                            ? JSON.parse(unescape(r['Logo']))[0]['url']
                            : '';
                    }},
                    {data: 'name'},
                    {data: 'tags'},
                    {data: 'Type'},
                    {data: function (r) {
                        return r['description'].substr(0,100)+
                        (r['description'].length > 100 ? '...' : '')
                    }},
                    {
                        className: 'record',
                        data:  null,
                        defaultContent: null,
                        searchable: false
                    }
                ],

				initComplete: function () {
					this.api().columns([4]).every(function () {						// Type
						var column = this;
						var select = $('<select class="filter-top" id="filter-' + column[0][0] + '"><option value="">- Select organizations by type -</option></select>')
							.appendTo($('div.toolbar'))
							.on('change', function () {
								var val = $.fn.dataTable.util.escapeRegex(
									$(this).val()
								);
								column
									.search(val ? '^'+val+'$' : '', true, false)
									.draw();
							});
						column.data().unique().sort().each(function (d, j) {
							//select.append('<option value="'+d+(d == 'City Agency' ? '" selected>' : '">')+d+'</option>')
							select.append('<option value="'+d+'">'+d+'</option>')
						});

						setTimeout(function(){
							select.val('{!! $defType !!}')
							select.trigger('change')
						}, 700);
					});


					this.api().columns([3]).every(function () {						// tags
						var column = this;
						var select = $('<select class="filter-top" id="filter-tags"><option value="">- Select organizations by tag -</option></select>')
							.appendTo($('div.toolbar'))
							.on('change', function () {
								var val = $.fn.dataTable.util.escapeRegex(
									$(this).val()
								);
								column
									.search(val ? val : '', false, false)
									.draw();
							});

						var tt = []

						rg = /""([^"]+)""/g;
						column.data().each(function (d, j) {
							while ((t = rg.exec(d)) !== null) {
								tt.push(t[1])
							}
						})
						tt = [...new Set(tt)]

						tt.sort().forEach(function (d, j) {
							select.append( '<option value="'+d+'">'+d+'</option>' )
						});
						@if ($defTag)
						  setTimeout(function(){
							select.val('{!! $defTag !!}')
							select.trigger('change')
						  }, 1000);
						@endif
					});
				}
			});

			table.on('preDraw', function () {
                $('#orgsTable tbody').hide();
                return true
            });

			table.on('draw', function () {
                var api = $('#orgsTable').dataTable().api();
                var modifier = {
                    order:  'current',  // 'current', 'applied', 'index',  'original'
                    page:   'current',      // 'all',     'current'
                    search: 'applied',     // 'none',    'applied', 'removed'
                }
                var td = $('<td></td>')
                var div = $('<div></div>')

                api.cells('.record', modifier).data().each(function (r, i) {

                    div = $('<div class="card-body"></div>')

                    if (r['Logo'])
                        div.append(`<div class="inner_logoimg"><div class="inside_org_logo"><img src="${JSON.parse(unescape(r['Logo']))[0]['url']}"></div></div>`)

                    if (r['name'] && (!r['Logo'] || !r['description']))
                        div.append(`<h6>${r['name']}</h6>`)

                    var descr = r['description'].substr(0,100)+(r['description'].length > 100 ? '...' : '')
                    div.append(`<p class="card-text">${descr}</p>`)

                    if (r['tags']) {
                        var tags = ''
                        JSON.parse(unescape(r['tags'])).forEach(function (d, j) {
                            tags = tags+'<span class="badge badge-info" onclick="tagFlt(event, \''+d+'\');">'+d+'</span>'
                        })
                        div.append(`<p class="tag_org">Tags:</p> ${tags}`)
                    }

                    td.append($(`<div class="col-md-3"><a href="/organization/${r['id']}"><div class="card  w-33"><div class="card-body">${div.html()}</div></div></a></div>`))
                });
                $('#orgsTable tbody').html('<tr><td colspan="7" class="p-0"><div class="row">'+td.html()+'</div></td></tr>')
                $('#orgsTable tbody').show();
            });

		});
	</script>

	<div class="container">
		<div class="row justify-content-center">
			<div class="col-md-12 organization_data">
                <div class="col-md-12">
                    <table id="orgsTable" class="display table" style="width:100%;padding-top: 30px;">
                        <thead>
                            <tr>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
			</div>
		</div>
        <div class="homeround_content">
            <div class="text-center bottom_text col-md-12">
                <h3>We’re adding data all the time.</h3>
                <a href="#" class="learn_more">Learn More</a>
            </div>
        </div>
    </div>

@endsection
