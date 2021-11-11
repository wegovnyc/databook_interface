@extends('layout')

@section('menubar')
	@include('sub.menubar', ['active' => 'orgs'])
@endsection

@section('content')

	<nav class="navbar navbar-expand-lg navbar-light" id="orgs-menu" style="background-color: #e3f2fd;">
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-orgs" aria-controls="navbar-orgs" aria-expanded="true" aria-label="Toggle navigation">
			<img src="/img/menu_icon.png" alt="" title="" style="height: 20px;">
		</button>
		<div class="collapse navbar-collapse" id="navbar-orgs">
			<ul class="navbar-nav">
				@foreach (['NYC Organizational Chart' => 'orgs', 'Government Directory' => 'orgsDirectory', 'All Organizations' => 'orgsAll'] as $t=>$route)
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

	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/orgchart/3.1.1/js/jquery.orgchart.min.js" integrity="sha512-alnBKIRc2t6LkXj07dy2CLCByKoMYf2eQ5hLpDmjoqO44d3JF8LSM4PptrgvohTQT0LzKdRasI/wgLN0ONNgmA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.5.0-beta4/html2canvas.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/orgchart/3.1.1/css/jquery.orgchart.min.css" integrity="sha512-bCaZ8dJsDR+slK3QXmhjnPDREpFaClf3mihutFGH+RxkAcquLyd9iwewxWQuWuP5rumVRl7iGbSDuiTvjH1kLw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

	<div id="chart-container"></div>
	<div class="container">
        <div class="homeround_content">
            <div class="text-center bottom_text col-md-12">
                <h3>We’re adding data all the time.</h3>
                <a href="#" class="learn_more">Learn More</a>
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
