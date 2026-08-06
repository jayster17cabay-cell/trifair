@extends('layouts.tfrb-officer')

@section('title', 'Invalid Ratings')

@section('content')
    @php $routePrefix = 'tfrb-officer'; @endphp
    @include('partials.admin.invalid-ratings')
@endsection
