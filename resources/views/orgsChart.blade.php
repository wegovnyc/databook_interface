@extends('layout')

@section('styles')
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/orgchart/3.1.1/css/jquery.orgchart.min.css" integrity="sha512-bCaZ8dJsDR+slK3QXmhjnPDREpFaClf3mihutFGH+RxkAcquLyd9iwewxWQuWuP5rumVRl7iGbSDuiTvjH1kLw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
@endsection

@section('menubar')
	@include('sub.menubar', ['active' => 'orgs'])
@endsection

@section('content')
	<div class="inner_container">
		<nav class="navbar navbar-expand-lg navbar-light chart_submenu">
			<!-- <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-orgs" aria-controls="navbar-orgs" aria-expanded="true" aria-label="Toggle navigation">
				<img src="/img/menu_icon.png" alt="" title="" style="height: 20px;">
			</button> -->
			<div class="navbar-collapse">
				<ul class="navbar-nav">
					@foreach (['NYC Organizational Chart' => 'orgs', 'Government Agencies' => 'orgsDirectory', 'All Organizations' => 'orgsAll'] as $t=>$route)
						@if ($route == 'orgs')
							<li class="nav-item active">
						@else
							<li class="nav-item">
						@endif
								<a class="nav-link" href="{!! route($route) !!}">{{ $t }}</a>
							</li>
					@endforeach
				</ul>
			</div>
		</nav>

		<script src="https://cdnjs.cloudflare.com/ajax/libs/orgchart/3.1.1/js/jquery.orgchart.min.js" integrity="sha512-alnBKIRc2t6LkXj07dy2CLCByKoMYf2eQ5hLpDmjoqO44d3JF8LSM4PptrgvohTQT0LzKdRasI/wgLN0ONNgmA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.5.0-beta4/html2canvas.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	</div>
	<div class="mt-4 mx-2">
		<h4>Citywide Organizational Chart</h4>
		<p>This interactive hierarchical chart shows the relationship between city officials and agencies. Click on any entity to see its profile.</p>
	</div>

	<div id="chart-container"></div>

	<div class="inner_container">
		<div class="container">
			<div class="homeround_content">
				<div class="text-center bottom_text col-md-12">
					This chart was generated using the city’s official (but outdated) organizational chart <a href="https://www1.nyc.gov/office-of-the-mayor/org-chart.page">here</a>. We’ve made a few changes to improve legibility and keep it up to date.<br/> If you have ideas for further improvements or notice inaccuracies, please <a href="https://wegovnyc.notion.site/Contact-Us-54b075fa86ec47ebae48dae1595afc2c">let us know</a>.
				</div>
			</div>
		</div>
	</div>
	<script>
			var oc = $('#chart-container').orgchart({
			  //'data' : dd,
			  'data' : '/data/orgChart.json',
			  nodeContent: 'title',
			  pan: true,
			  verticalLevel: 4,
			  visibleLevel: 20
			});
			@if ($defId)
				$('#chart-container').on('init.orgchart', function() {
					setTimeout(function () {
						$('a[href*="{{ $defId }}"]').parent().parent().attr('class', 'node node_focused');
						var w = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
						var h = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
						var l = $('.node_focused').offset().left;
						var t = $('.node_focused').offset().top;
						var offX = (w - 160)/2 - l;
						var offY = Math.min(t, h/2) - t;
						$('.orgchart').attr('style', 'cursor:default; transform: matrix(1, 0, 0, 1, '+offX+', '+offY+');')
					}, 1000);
				});
			@endif
	</script>

@endsection
