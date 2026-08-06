@extends('layouts.superadmin')

@section('title', 'Complaints')

@section('content')
    @php $routePrefix = 'superadmin'; @endphp
    @include('partials.admin.complaints')
@endsection
