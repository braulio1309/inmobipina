@extends('layouts.app')

@section('title', trans('default.report'))

@section('contents')
    @php($isAdmin = auth()->user()->isAdmin())

    <report :is-admin='@json($isAdmin)'></report>
@endsection
