@extends('layouts.app')

@section('title', 'Cierres')

@section('contents')
    @php($isAdmin = auth()->user()->isAdmin())
    <create-operations :is-admin='@json($isAdmin)'></create-operations>
@endsection
