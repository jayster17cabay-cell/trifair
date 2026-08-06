@extends('layouts.superadmin')

@section('title', 'QR Code - ' . $operator->user->name)

@section('content')
    @php $routePrefix = 'superadmin'; @endphp
    @include('partials.admin.operator-qrcode')
@endsection
