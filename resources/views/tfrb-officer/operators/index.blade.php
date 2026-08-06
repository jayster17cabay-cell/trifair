@extends('layouts.tfrb-officer')

@section('title', 'Operators')

@section('content')
    @php $routePrefix = 'tfrb-officer'; @endphp
    @include('partials.admin.operators-index')
@endsection
