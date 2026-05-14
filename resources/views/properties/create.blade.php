@extends('layouts.app')

@section('title', 'Propiedades')

@section('contents')
    @php($isAdmin = auth()->user()->isAdmin())
    @php($advisorName = trim((auth()->user()->first_name ?? '') . ' ' . (auth()->user()->last_name ?? '')) ?: (auth()->user()->email ?? ''))

    <create-properties
        :is-admin='@json($isAdmin)'
        current-user-name='{{ $advisorName }}'
    ></create-properties>
@endsection
