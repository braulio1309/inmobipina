@extends('layouts.app')

@section('title', 'Clientes')

@section('contents')
    @php($isAdmin = auth()->user()->isAdmin())

    <clients :is-admin='@json($isAdmin)'></clients>
@endsection
