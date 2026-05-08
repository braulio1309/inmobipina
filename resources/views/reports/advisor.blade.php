@extends('layouts.app')

@section('title', 'Reportes de Asesor')

@section('contents')
    @php($isAdmin = auth()->user()->isAdmin())

    <advisor-reports :is-admin='@json($isAdmin)'></advisor-reports>
@endsection
