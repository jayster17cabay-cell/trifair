@extends('layouts.superadmin')

@section('title', 'All Ratings')

@section('content')
    @php
        $routePrefix = 'superadmin';
        $reviewRouteName = 'superadmin.ratings.review';
        $showDelete = true;
    @endphp
    @include('partials.admin.ratings')
@endsection
