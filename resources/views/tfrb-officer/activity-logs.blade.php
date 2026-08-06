@extends('layouts.tfrb-officer')

@section('title', 'Activity Logs')

@section('content')
    @php $routePrefix = 'tfrb-officer'; @endphp
    @include('partials.admin.activity-logs')
@endsection
