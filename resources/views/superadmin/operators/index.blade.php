@extends('layouts.superadmin')

@section('title', 'Operators')

@section('content')
    @php $routePrefix = 'superadmin'; @endphp
    @include('partials.admin.operators-index')
@endsection
