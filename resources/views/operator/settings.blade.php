@extends('layouts.operator')

@section('title', 'Settings')

@section('header-body')
<div class="op-header-greet">
    <h1 class="op-header-title">Settings</h1>
    <p class="op-header-sub">Account and security</p>
</div>
@endsection

@section('content')
@include('partials.admin.account-settings', [
    'routePrefix' => 'operator',
    'roleLabel' => 'Operator',
    'operator' => $operator,
])
@endsection