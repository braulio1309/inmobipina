@extends('layouts.app')

@section('title', 'Clientes')

@section('contents')
    @php($isAdmin = auth()->user()->isAdmin())

    <create-clients
        :is-admin='@json($isAdmin)'
        :current-user-id='@json((string) auth()->id())'
    ></create-clients>
@endsection
