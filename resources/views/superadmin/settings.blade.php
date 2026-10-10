@extends('layouts.superadmin')

@section('title', 'Settings')

@section('content')
<div class="tw-page-head">
    <h1 class="tw-page-title"><i class="bi bi-gear-fill mr-2 text-navy-600"></i>Settings</h1>
    <p class="tw-page-sub">Manage your account profile and security</p>
</div>

@include('partials.admin.account-settings', [
    'routePrefix' => 'superadmin',
    'roleLabel' => 'Superadmin',
    'operator' => null,
])
@endsection