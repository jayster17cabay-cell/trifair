@extends('layouts.president')

@section('title', 'Franchise Expired')

@section('content')
@include('partials.toda-expired-screen', ['operator' => $operator, 'toda' => $toda ?? null])
@endsection