@extends('layouts.app')

@section('title', 'Propiedades')

@section('contents')
    @php($isAdmin = auth()->user()->isAdmin())

    <create-properties :is-admin='@json($isAdmin)'></create-properties>
@endsection
