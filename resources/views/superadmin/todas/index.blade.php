@extends('layouts.superadmin')

@section('title', 'TODA')

@section('content')
    @php
        $routePrefix = 'superadmin';
        $showManage = true;
    @endphp
    @include('partials.admin.todas-index')
@endsection
