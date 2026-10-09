@extends('layouts.app')

@section('title', 'Cierres')

@section('contents')
    @php($isAdmin = auth()->user()->isAdmin())

    <operations :is-admin='@json($isAdmin)'></operations>
@endsection
