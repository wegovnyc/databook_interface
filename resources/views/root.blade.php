@extends('layout')


@section('menubar')
	@include('sub.menubar', ['active' => null])
@endsection


@section('content')
<div class="inner_container">
	<div class="jumbotron">
        <div class="col-md-5 home_bgcontent">
            <h1>WeGovNYC Databook</h1>
            <p>We collect and join datasets together to create data-driven apps about New York City government.</p>
        </div>
    </div>
    <div class="homeround_content">
        <div class="row">
            <div class="col-md-6">
				<div class="circle_img">
					<img src="/img/people.png">
				</div>
				<div class="content_area">
					<h4>Organizations</h4>
					<p>Profiles of city agencies and relevant groups.</p>
					<a class="btn_org_home" href="{{ route('orgs') }}" role="button">View Profiles</a>

				</div>
            </div>
            <div class="col-md-6">
				<div class="circle_img">
					<img src="/img/request.png">
				</div>
				<div class="content_area">
					<h4>Notices</h4>
					<p>Agency news from the City Record.</p>
					<a class="btn_org_home" href="{{ route('notices') }}" role="button">View Notices</a>
				</div>
            </div>
            <div class="col-md-6">
				<div class="circle_img">
					<img src="/img/projects.png">
				</div>
				<div class="content_area">
					<h4>Capital Projects</h4>
					<p>Profiles with budget and timelines for all city capital project.</p>
					<a class="btn_org_home" href="{{ route('projects') }}" role="button">View Profiles</a>
				</div>
            </div>
            <div class="col-md-6">
				<div class="circle_img">
					<img src="/img/indicators.png">
				</div>
				<div class="content_area">
					<h4>Districts</h4>
					<p>Neighborhood, city council & community district data.</p>
					<a class="btn_org_home" href="{{ route('districts') }}" role="button">View Profiles</a>
				</div>
            </div>
            <div class="col-md-6">
				<div class="circle_img">
					<img src="/img/services.png">
				</div>
				<div class="content_area">
					<h4>Civil Service Titles</h4>
					<p>Job titles, positions, people, contacts, salaries and more.</p>
					<a class="btn_org_home" href="{{ route('titles') }}" role="button">View Profiles</a>
				</div>
            </div>
            <div class="col-md-6">
				<div class="circle_img">
					<img src="/img/jobs.png">
				</div>
				<div class="content_area">
					<h4>Auctions</h4>
					<p>A list of items being sold by the city.</p>
					<a class="btn_org_home" href="{{ route('auctions') }}" role="button" target="_blank">View Auctions</a>
				</div>
            </div>
            <div class="col-md-6">
				<div class="circle_img">
					<img src="/img/profile.png">
				</div>
				<div class="content_area">
					<h4>Participate</h4>
					<p>Tell us what you think on our engagement platform.</p>
					<a class="btn_org_home" href="https://participate.wegov.nyc/assemblies/wegovga" role="button" target="_blank">Join Us</a>
				</div>
            </div>
			
            <div class="col-md-12 mb-3">
				<div class="content_area">
					<h4 class="mb-1">How it Works</h4>
					<p>Our data pipeline takes NYC open data, normalizes it and publishes it as bulk data and via Carto API. <a href="https://www.notion.so/wegovnyc/DataBook-c44e74c262a84b67b7aabb14885e1ec6" target="_blank">Learn More</a></p>
				</div>
            </div>
            <div class="col-md-12">
				<div class="content_area">
					<h4 class="mb-1">Work with Us</h4>
					<p>We create information products and experience for elected officials, journalists, educators, city agencies and others. <a href="https://wegovnyc.notion.site/Contact-Us-54b075fa86ec47ebae48dae1595afc2c" target="_blank">Contacts Us</a></p>
				</div>
            </div>
			
			{{--
            <div class="col-md-6">
				<div class="circle_img">
					<img src="/img/budget.png">
				</div>
				<div class="content_area">
					<h4>Budgets</h4>
					<p>Line items budgets for each agency and how they change over time.</p>
				</div>
            </div>
            <div class="col-md-4">
				<div class="circle_img">
					<img src="/img/facilities.png">
				</div>
				<div class="content_area">
					<h4>Facilities</h4>
					<p>Buildings and real estate controlled by city agencies.</p>
				</div>
            </div>
            <div class="col-md-4">
				<div class="circle_img">
					<img src="/img/jobs.png">
				</div>
				<div class="content_area">
					<h4>Jobs</h4>
					<p>Job opportunities currently offered by city agencies.</p>
				</div>
            </div>
            <div class="col-md-4">
				<div class="circle_img">
					<img src="/img/people.png">
					<img src="/img/profile.png">
				</div>
				<div class="content_area">
					<h4>People</h4>
					<p>Names, titles and contact info of key staff within agencies.</p>
				</div>
            </div>
            <div class="col-md-4">
				<div class="circle_img">
					<img src="/img/request.png">
				</div>
				<div class="content_area">
					<h4>Requests</h4>
					<p>Formal requests from community boards of city agencies.</p>
				</div>
            </div>
			--}}
        </div>
		{{--
        <div class="text-center bottom_text col-md-12">
            <h3>We’re adding data and organizing collaborations all the time.</h3>
            <a href="https://www.notion.so/wegovnyc/DataBook-c44e74c262a84b67b7aabb14885e1ec6" class="learn_more" target="_blank">Learn More</a>
        </div>
		--}}
    </div>
</div>
@endsection
