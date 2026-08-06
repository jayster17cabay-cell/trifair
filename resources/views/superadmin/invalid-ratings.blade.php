@extends('layouts.superadmin')

@section('title', 'Invalid Ratings')

@section('content')
    @php $routePrefix = 'superadmin'; @endphp
    @include('partials.admin.invalid-ratings')
@endsection
