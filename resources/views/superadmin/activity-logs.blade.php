@extends('layouts.superadmin')

@section('title', 'Activity Logs')

@section('content')
    @php $routePrefix = 'superadmin'; @endphp
    @include('partials.admin.activity-logs')
@endsection
