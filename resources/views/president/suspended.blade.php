@extends('layouts.president')

@section('title', 'Suspended')

@section('content')
@include('partials.toda-suspended-screen', ['operator' => $operator, 'toda' => $toda])
@endsection
