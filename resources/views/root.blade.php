@extends('layout')


@section('menubar')
	@include('sub.menubar', ['active' => null])
@endsection


@section('content')

	<div class="jumbotron">
	  <h1 class="display-4">We can use jumbotron component</h1>
	  <p class="lead">Or remove it if just more simple screen needed.</p>
	  <hr class="my-4">
	  <p>Or transform.</p>
	  <a class="btn btn-info" href="{{ route('orgs') }}" role="button">Organizations</a>
	</div>

@endsection
