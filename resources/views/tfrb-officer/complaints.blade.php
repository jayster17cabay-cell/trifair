@extends('layouts.tfrb-officer')

@section('title', 'Complaints')

@section('content')
    @php $routePrefix = 'tfrb-officer'; @endphp
    @include('partials.admin.complaints')
@endsection
