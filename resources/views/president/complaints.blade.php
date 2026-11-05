@extends('layouts.president')

@section('title', 'Complaints Queue')

@section('content')
    @php $routePrefix = 'president'; @endphp
    @include('partials.admin.complaints')
@endsection
