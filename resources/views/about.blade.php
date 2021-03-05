@extends('layout')

@section('menubar')
	@include('sub.menubar', ['active' => 'about'])
@endsection

@section('content')

	<div class="jumbotron mb-1">
	  <h1 class="display-4 mb-4">About</h1>
	  <p class="lead">WeGovNYC is an organizing initiative bringing public interest and civic technologists together to make New York City the best run municipality in the world.</p>
	  <p class="lead">Through a combination of community building, product development and issue advocacy, WeGov advances a vision of an open source city that efficiently delivers projects and services to its residents, provides leadership to its region and actively contributes its knowledge to improve solutions for cities around the world.</p>
	  <hr class="my-4">
	  <p class="lead">Our initiative’s three main constituencies are:</p>
	  <ul>
		<li class="lead">public servants in a position to advocate for and advance free and open source solutions within city government.</li>
		<li class="lead">concerned citizens who want to help advance an open source digital transformation of New York City.</li>
		<li class="lead">policy makers who want to use technology to improve the lives of the New Yorkers they serve.</li>
	  </ul>
	</div>

@endsection
