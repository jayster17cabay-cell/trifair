@extends('layouts.superadmin')

@section('title', 'All Ratings')

@section('content')
    @php
        $routePrefix = 'superadmin';
        $reviewRouteName = 'superadmin.complaints.review';
        $showDelete = true;
    @endphp
    @include('partials.admin.ratings')
@endsection
