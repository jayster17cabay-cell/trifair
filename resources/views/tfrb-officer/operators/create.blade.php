@extends('layouts.tfrb-officer')

@section('title', 'Add Operator')

@section('content')
    @php $routePrefix = 'tfrb-officer'; @endphp
    <div class="tw-page-head">
        <div>
            <h1 class="tw-page-title"><i class="bi bi-person-plus mr-2 text-gold"></i>Add New Operator</h1>
            <p class="tw-page-sub">Register a new motorcycle operator</p>
        </div>
        <a href="{{ route($routePrefix . '.operators') }}" class="tw-btn tw-btn-outline">
            <i class="bi bi-arrow-left"></i>Back
        </a>
    </div>

    <div class="tw-card tw-card-pad">
        @include('partials.admin.operator-create-form')
    </div>
@endsection
