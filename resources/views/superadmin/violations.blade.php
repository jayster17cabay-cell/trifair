@extends('layouts.superadmin')

@section('title', 'Violation Reports')

@section('content')
    @php $routePrefix = 'superadmin'; @endphp
    @include('partials.admin.violations')
@endsection
