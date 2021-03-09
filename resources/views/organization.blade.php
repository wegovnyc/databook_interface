@extends('layout')

@section('menubar')
	@include('sub.menubar', ['active' => 'orgs'])
@endsection

@section('content')
	@include('sub.orgheader', ['active' => 'about'])

	<div class="container py-4">
        <div class="col-md-12">
            @if ($org['description'] == '')
                <h1 class="display-4">...</h1>
            @else
                <h1 class="display-4">About</h1>
                <p class="lead">
                    {!! nl2br($org['description']) !!}
                </p>
            @endif
        </div>
	</div>


@endsection
