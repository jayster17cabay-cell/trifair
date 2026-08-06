@extends('layouts.tfrb-officer')

@section('title', 'Reports')

@section('content')
    @php $routePrefix = 'tfrb-officer'; @endphp
    @include('partials.admin.reports')
@endsection
