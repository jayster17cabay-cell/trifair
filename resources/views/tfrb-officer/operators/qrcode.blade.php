@extends('layouts.tfrb-officer')

@section('title', 'QR Code - ' . $operator->user->name)

@section('content')
    @php $routePrefix = 'tfrb-officer'; @endphp
    @include('partials.admin.operator-qrcode')
@endsection
