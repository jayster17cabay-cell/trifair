@extends('layouts.tfrb-officer')

@section('title', 'TODA')

@section('content')
    @php
        $routePrefix = 'tfrb-officer';
        $showManage = false;
    @endphp
    @include('partials.admin.todas-index')
@endsection
