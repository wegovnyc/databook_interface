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
		<div class="my-3 mx-2">
			<h4>Citywide Organizational Chart</h4>
			<p>This hierarchical chart shows the relationship between city officials and agencies.</p>
		</div>
	</div>

	<div id="chart-container"></div>

	<div class="inner_container">
		<div class="container">
			<div class="homeround_content">
				<div class="text-center bottom_text col-md-12">
					This chart was generated using the city’s official (but outdated) organizational chart <a href="https://www1.nyc.gov/office-of-the-mayor/org-chart.page">here</a>. We’ve made a few changes to improve legibility and keep it up to date. If you have ideas for further improvements or notice inaccuracies, please <a href="https://wegovnyc.notion.site/Contact-Us-54b075fa86ec47ebae48dae1595afc2c">let us know</a>.
				</div>
			</div>
		</div>
	</div>
	<script>
			var dd = {
			  name: "Lao Lao", title: "manager",
			  children: [
				{name: "Bo Miao", title: "department manager", className: "middle-level"},
				{
				  name: "Su Miao", title: "department manager", className: "middle-level",
				  children: [
					{name: "Tie Hua", title: "senior engineer", className: "product-dept"},
					{
					  name: "Hei Hei", title: "senior engineer", className: "product-dept",
					  children: [
						{name: "Pang Pang", title: "engineer", className: "pipeline1"},
						{
						  name: "Xiang Xiang", title: "UE engineer", className: "pipeline1",
						  children: [
							{name: "Dan Dan", title: "engineer", className: "pipeline1"},
							{
							  name: "Er Dan", title: "engineer", className: "pipeline1",
							  children: [
								{name: "Xuan Xuan", title: "intern"},
								{name: "Er Xuan", title: "intern"}
							  ]
							}
						  ]
						}
					  ]
					}
				  ]
				},
				{name: "Hong Miao", title: "department manager", className: "middle-level"},
				{
				  name: "Chun Miao", title: "department manager", className: "middle-level",
				  children: [
					{name: "Bing Qin", title: "senior engineer", className: "product-dept"},
					{
					  name: "Yue Yue", title: "senior engineer", className: "product-dept",
					  children: [
						{name: "Er Yue", title: "engineer", className: "pipeline1"},
						{name: "San Yue", title: "UE engineer", className: "pipeline1"}
					  ]
					}
				  ]
				}
			  ]
			};

			var oc = $('#chart-container').orgchart({
			  //'data' : dd,
			  'data' : '/data/orgChart.json',
			  'nodeContent': 'title',
			  verticalLevel: 4,
			  visibleLevel: 20
			});
	</script>

@endsection
