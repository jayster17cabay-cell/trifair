@extends('layouts.operator')

@section('title', 'Suspended')

@section('header-body')
<div class="op-header-greet">
    <h1 class="op-header-title">{{ explode(' ', Auth::user()->name)[0] }}</h1>
    <p class="op-header-sub">{{ $toda ? $toda->name : 'Suspended' }}</p>
</div>
@endsection

@section('content')
@include('partials.toda-suspended-screen', ['operator' => $operator, 'toda' => $toda])
@endsection
