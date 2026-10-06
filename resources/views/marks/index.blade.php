@extends('layouts.app')
@section('title', 'My subjects')
@section('subtitle', 'Subjects assigned to you by the head of department.')

@section('content')
    @include('marks._subjects')
@endsection
