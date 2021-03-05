@extends('layout')


@section('menubar')
	@include('sub.menubar', ['active' => null])
@endsection


@section('content')
	<div class="jumbotron">
        <div class="col-md-6 home_bgcontent">
            <h1>WeGovNYC DataBook</h1>
            <p>We create, collect and connect open datasets together to give you a data-driven view of New York City government. </p>
            <a class="btn_org_home" href="{{ route('orgs') }}" role="button">Organization Profiles</a>
        </div>
    </div>
    <div class="homeround_content">
        <div class="row">
            <div class="col-md-4">
                <a href="#">
                    <div class="circle_img">
                        <img src="/img/profile.png" title="" alt="">
                    </div>
                    <div class="content_area">
                        <h4>Profile</h4>
                        <p>Descriptions, contact and social media, ratings and more.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="#">
                    <div class="circle_img">
                        <img src="/img/budget.png" title="" alt="">
                    </div>
                    <div class="content_area">
                        <h4>Budgets</h4>
                        <p>Line items budgets for each agency and how they change over time.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="#">
                    <div class="circle_img">
                        <img src="/img/services.png" title="" alt="">
                    </div>
                    <div class="content_area">
                        <h4>Services</h4>
                        <p>Health, human and social media, ratings and more.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="#">
                    <div class="circle_img">
                        <img src="/img/projects.png" title="" alt="">
                    </div>
                    <div class="content_area">
                        <h4>Projects</h4>
                        <p>Capital projects managed by city agencies.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="#">
                    <div class="circle_img">
                        <img src="/img/facilities.png" title="" alt="">
                    </div>
                    <div class="content_area">
                        <h4>Facilities</h4>
                        <p>Buildings and real estate controlled by city agencies.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="#">
                    <div class="circle_img">
                        <img src="/img/jobs.png" title="" alt="">
                    </div>
                    <div class="content_area">
                        <h4>Jobs</h4>
                        <p>Job opportunities currently offered by city agencies.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="#">
                    <div class="circle_img">
                        <img src="/img/people.png" title="" alt="">
                    </div>
                    <div class="content_area">
                        <h4>People</h4>
                        <p>Names, titles and contact info of key staff within agencies.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="#">
                    <div class="circle_img">
                        <img src="/img/request.png" title="" alt="">
                    </div>
                    <div class="content_area">
                        <h4>Requests</h4>
                        <p>Formal requests from community boards of city agencies.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="#">
                    <div class="circle_img">
                        <img src="/img/indicators.png" title="" alt="">
                    </div>
                    <div class="content_area">
                        <h4>Indicators</h4>
                        <p>Key performance indicators (KPIs) for agencies and geographies.</p>
                    </div>
                </a>
            </div>
        </div>
        <div class="text-center bottom_text col-md-12">
            <h3>We’re adding data all the time.</h3>
            <a href="#" class="learn_more">Learn More</a>
        </div>
    </div>

@endsection
