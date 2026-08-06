@extends('layouts.tfrb-officer')

@section('title', 'Ratings')

@section('content')
    @php
        $routePrefix = 'tfrb-officer';
        $reviewRouteName = 'tfrb-officer.ratings.review';
        $showDelete = false;
    @endphp
    @include('partials.admin.ratings')
@endsection
