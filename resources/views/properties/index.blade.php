@extends('layouts.app')

@section('title', 'Propiedades')

@section('contents')
    @php($isAdmin = auth()->user()->isAdmin())

    <properties :is-admin='@json($isAdmin)'></properties>
@endsection
