@extends('layout')


@section('menubar')
	@include('sub.menubar', ['active' => null])
@endsection


@section('content')
<div class="inner_container">
	<div class="jumbotron">
        <div class="col-md-7 home_bgcontent">
            <h1>WeGovNYC DataBook</h1>
            <p>We collect and join datasets together to create a data-driven view of New York City government.</p>
            <a class="btn_org_home mr-3" href="{{ route('orgs') }}" role="button">Agency Profiles</a>
			<a class="btn_org_home mr-3" href="{{ route('districts') }}" role="button">District Profiles</a>
            <a class="btn_org_home" href="{{ route('projects') }}" role="button">Capital Projects Profiles</a>
        </div>
    </div>
    <div class="homeround_content">
        <div class="row">
            <div class="col-md-4">
				<div class="circle_img">
					<img src="/img/profile.png" title="" alt="">
				</div>
				<div class="content_area">
					<h4>Profiles</h4>
					<p>Organization's descriptions, contact and social media, ratings and more.</p>
				</div>
            </div>
            <div class="col-md-4">
				<div class="circle_img">
					<img src="/img/budget.png" title="" alt="">
				</div>
				<div class="content_area">
					<h4>Budgets</h4>
					<p>Line items budgets for each agency and how they change over time.</p>
				</div>
            </div>
            <div class="col-md-4">
				<div class="circle_img">
					<img src="/img/services.png" title="" alt="">
				</div>
				<div class="content_area">
					<h4>Services</h4>
					<p>Health, human and social media, ratings and more.</p>
				</div>
            </div>
            <div class="col-md-4">
				<div class="circle_img">
					<img src="/img/projects.png" title="" alt="">
				</div>
				<div class="content_area">
					<h4>Projects</h4>
					<p>Capital projects managed by city agencies.</p>
				</div>
            </div>
            <div class="col-md-4">
				<div class="circle_img">
					<img src="/img/facilities.png" title="" alt="">
				</div>
				<div class="content_area">
					<h4>Facilities</h4>
					<p>Buildings and real estate controlled by city agencies.</p>
				</div>
            </div>
            <div class="col-md-4">
				<div class="circle_img">
					<img src="/img/jobs.png" title="" alt="">
				</div>
				<div class="content_area">
					<h4>Jobs</h4>
					<p>Job opportunities currently offered by city agencies.</p>
				</div>
            </div>
            <div class="col-md-4">
				<div class="circle_img">
					<img src="/img/people.png" title="" alt="">
				</div>
				<div class="content_area">
					<h4>People</h4>
					<p>Names, titles and contact info of key staff within agencies.</p>
				</div>
            </div>
            <div class="col-md-4">
				<div class="circle_img">
					<img src="/img/request.png" title="" alt="">
				</div>
				<div class="content_area">
					<h4>Requests</h4>
					<p>Formal requests from community boards of city agencies.</p>
				</div>
            </div>
            <div class="col-md-4">
				<div class="circle_img">
					<img src="/img/indicators.png" title="" alt="">
				</div>
				<div class="content_area">
					<h4>Indicators</h4>
					<p>Key performance indicators (KPIs) for agencies and geographies.</p>
				</div>
            </div>
        </div>
        <div class="text-center bottom_text col-md-12">
            <h3>We’re adding data and organizing collaborations all the time.</h3>
            <a href="https://www.notion.so/wegovnyc/DataBook-c44e74c262a84b67b7aabb14885e1ec6" class="learn_more" target="_blank">Learn More</a>
        </div>
    </div>
</div>
@endsection
