@extends('layouts.operator')

@section('title', 'Suspended')

@section('header-body')
<div class="op-header-greet">
    @php $operatorFirstName = Auth::user()->nameParts()['first'] ?: Auth::user()->name; @endphp
    <h1 class="op-header-title">{{ $operatorFirstName }}</h1>
    <p class="op-header-sub">{{ $toda ? $toda->name : 'Suspended' }}</p>
</div>
@endsection

@section('content')
@include('partials.toda-suspended-screen', ['operator' => $operator, 'toda' => $toda])
@endsection
