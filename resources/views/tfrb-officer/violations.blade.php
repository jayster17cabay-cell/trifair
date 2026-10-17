@extends('layouts.tfrb-officer')

@section('title', 'Violation Reports')

@section('content')
    @php $routePrefix = 'tfrb-officer'; @endphp
    @include('partials.admin.violations')
@endsection
