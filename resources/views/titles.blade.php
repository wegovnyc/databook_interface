@extends('layout')

@section('menubar')
	@include('sub.menubar', ['active' => 'orgs'])
@endsection

@section('content')

	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.5/js/dataTables.buttons.min.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.5/js/buttons.colVis.min.js"></script>
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.5/css/buttons.dataTables.min.css"/>
	<style>
		.bi-tags {padding-right: .5rem;}
		.tag-label {color:#777777; font-weight:600; padding-left: .1em;}
		.tag-label:hover {color:#171717;}
		.tag-label+.tag-label::before {
			/*float: left;*/
			padding-right: .2rem;
			color: #6c757d;
			content: ", ";
		}
	</style>
	<script>
		var table = null
		
		function tagFlt(e, tag) {
			//console.log(tag)
			$('#filter-tags').val(tag)
			$('#filter-tags').trigger('change')
			e.preventDefault()
		}

		function copyShareLink()
		{
			const url = $('#details-permalink').text()
			const params = new URLSearchParams({
			  search: $('input[type="search"]').val(),
			  type: $('#filter-4').val(),
			  tag: $('#filter-tags').val()
			});
			$('#details-permalink').text(`${url}?${params.toString()}`)
			copyLink()
			$('#details-permalink').text(url)
		}
		
		function loadShareLink()
		{
			const params = {!! $_GET ? json_encode($_GET) : '""' !!}
			if (params) {
				if (params['q']) {
					table({
					  'search': {
						'search': params['q']
					  }
					})
				}
				if (params['type']) {
					$('#filter-4').val(params['type'])
					$('#filter-4').trigger('change')
				}
				if (params['tag']) {
					$('#filter-tags').val(params['tag'])
					$('#filter-tags').trigger('change')
				}
			}
		}

		$(document).ready(function() {
			table = $('#titlesTable').DataTable( {
				pageLength: 20,
				deferRender: true,
				order: [[4, 'desc']],
				//ordering: false,
				dom: '<"toolbar"<"row">>frtip',
				ajax: {
					url: '{!! $url !!}',
					dataSrc: 'rows'
				},
				columns: [
					{data: function (r) { return `<a href="/titles/${r["Title Code"]}">${r["Title Code"]}</a>` }},
					{data: 'Title Description'},
					{data: 'Standard Hours'},
					{data: 'Assignment Level'},
					{data: 'Union Code'},
					{data: 'Union Description'},
					{data: 'Bargaining Unit Short Name'},
					{data: 'Bargaining Unit Description'},
					{data: 'Minimum Salary Rate'},
					{data: 'Maximum Salary Rate'}
                ],
				@if ($defSearch)
					search: {
						'search': '{{ $defSearch }}'
				    },
				@endif	

				initComplete: function () {
					this.api().columns([5]).every(function () {						// Union
						var column = this;
						var select = $('<select class="filter-top" id="filter-' + column[0][0] + '"><option value="">- Select positions by Union -</option></select>')
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

						rg = />([^<]+)</g;
						column.data().each(function (d, j) {
							/*
							while ((t = rg.exec(d)) !== null) {
								tt.push(t[1])
							}
							*/
							tt.push(d)
						})
						tt = [...new Set(tt)]

						tt.sort().forEach(function (d, j) {
							select.append( '<option value="'+d+'">'+d+'</option>' )
						});

						setTimeout(function(){
							select.val('{!! $defUnion !!}')
							select.trigger('change')
						}, 700);
					});

			{{--
					this.api().columns([2]).every(function () {						// tags
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

						rg = />([^<]+)</g;
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
					
			
					// share button
					$('<span class="share_icon_container" data-toggle="popover" data-content="Link copied to clipboard" placement="left" trigger="manual" style="top: 0;font-size: 22px;"><textarea id="details-permalink" class="details">{!! preg_replace('~\?.*~', '', route("orgs")) !!}</textarea><span id="details-addr"></span><a title="Share direct link" onclick="copyShareLink();"><i class="bi bi-share"></i></a></span>').appendTo($('div.toolbar'));
					
					loadShareLink()
			--}}
				}
			});
		});
	</script>
<div class="inner_container">
	<div class="mt-4 mx-3">
		<h4>Civil Service Titles</h4>
		<p>NYC’s government workforce is composed of people who hold “civil service titles”. These are the official descriptions of the work that city employees perform. These titles link individuals to positions, salaries, organizational charts and more.</p>
	</div>
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-md-12 organization_data">
                <div class="table-responsive">
                    <table id="titlesTable" class="display table" style="width:100%;padding-top: 30px;">
                        <thead>
                            <tr>
								<th>Title Code</th>
								<th>Title Description</th>
								<th>Standard Hours</th>
								<th>Assignment Level</th>
								<th>Union Code</th>
								<th>Union Description</th>
								<th>Bargaining Unit Short Name</th>
								<th>Bargaining Unit Description</th>
								<th>Minimum Salary Rate</th>
								<th>Maximum Salary Rate</th>
                            </tr>
                        </thead>
                    </table>
                </div>
			</div>
		</div>
		<div class="col-md-12">
			<div class="bottom_lastupdate">
				<p class="lead"><img src="/img/info.png" alt="" title=""> This data comes from <a href="https://data.cityofnewyork.us/City-Government/NYC-Civil-Service-Titles/nzjr-3966" target="_blank">NYC Civil Service Titles</a><span class="float-right" style="font-weight: 300;"><i>Last updated {{ date('m/d/Y', strtotime('-1 day')) }}</i></span></p>
			</div>
		</div>
    </div>
</div>
@endsection
