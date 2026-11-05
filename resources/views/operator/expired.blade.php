@extends('layouts.operator')

@section('title', 'Franchise Expired')

@section('header-body')
<div class="op-header-greet">
    @php $operatorFirstName = Auth::user()->nameParts()['first'] ?: Auth::user()->name; @endphp
    <h1 class="op-header-title">{{ $operatorFirstName }}</h1>
    <p class="op-header-sub">Franchise Expired</p>
</div>
@endsection

@section('content')
@include('partials.toda-expired-screen', ['operator' => $operator, 'toda' => null])
@endsection