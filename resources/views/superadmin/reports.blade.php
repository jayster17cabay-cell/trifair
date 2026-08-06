@extends('layouts.superadmin')

@section('title', 'Reports')

@section('content')
    @php $routePrefix = 'superadmin'; @endphp
    @include('partials.admin.reports')
@endsection
