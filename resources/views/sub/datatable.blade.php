<pre>{{ print_r($details, true) }}</pre>




<script>
	
@if ($details['details'])
	function details(d) {
		// `d` is the original data object for the row
		return '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">'+
			'<tr>'+
				'<td>Full name:</td>'+
				'<td>'+d.url+'</td>'+
			'</tr>'+
			'<tr>'+
				'<td>Extension number:</td>'+
				'<td>'+d.main_phone+'</td>'+
			'</tr>'+
			'<tr>'+
				'<td>Extra info:</td>'+
				'<td>'+d.Facebook+'</td>'+
			'</tr>'+
		'</table>';
	}		
@endif


	var table = null
	$(document).ready(function() {
		table = $('#core-table').DataTable( {
			ajax: {
				url: '{{ $url }}',
				dataSrc: 'rows'
			},
			columns: [
						{
							"className": 'details-control',
							"orderable": false,
							"data":  null,
							"defaultContent": ''
						},
						{data: function (r) { return `<a href="https://a127-jobs.nyc.gov/index_new.html?keyword=${r["id"]}">${r["id"]}</a>` }}, 
						{data: 'name'}, 
						{data: 'Type'}
					],
			
			initComplete: function () {
				this.api().columns([1,2,3]).every( function () {
					var column = this;
					var select = $('<select class="filter"><option value=""></option></select>')
						.appendTo( $(column.footer()).empty() )
						.on( 'change', function () {
							var val = $.fn.dataTable.util.escapeRegex(
								$(this).val()
							);
	 
							column
								.search( val ? '^'+val+'$' : '', true, false )
								.draw();
						} );
	 
					column.data().unique().sort().each( function ( d, j ) {
						select.append( '<option value="'+d+'">'+d+'</option>' )
					} );
				} );
			}
			//dom: '<"top"i>rt<"bottom"flPp><"clear">'     /*'Plfrtip'*//*,
		});
		$('a.toggle-vis').on( 'click', function (e) {
			e.preventDefault();
			var column = table.column( $(this).attr('data-column') );
			column.visible( ! column.visible() );
		});
		
		$('#core-table tbody').on('click', 'td.details-control', function () {
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


<div class="datatable-outer">
	<div class="toggle-box">
        Toggle column: <a class="toggle-vis" data-column="0">Id</a> - <a class="toggle-vis" data-column="1">Name</a> - <a class="toggle-vis" data-column="2">Type</a>
    </div>
	<table id="core-table" class="display table-striped table-hover" style="width:100%">
		<thead>
			<tr>
				<th></th>
				<th>Id</th>
				<th>Name</th>
				<th>Type</th>
			</tr>
		</thead>
		<tfoot>
            <tr>
				<th></th>
				<th class="filter"></th>
				<th class="filter"></th>
				<th class="filter"></th>
            </tr>
        </tfoot>
	</table>
</div>