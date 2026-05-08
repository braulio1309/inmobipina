@php($isAdmin = auth()->user()->isAdmin())

<app-top-bar :logo="{{json_encode(config('settings.application.company_icon'))}}" :is-admin='@json($isAdmin)'></app-top-bar>
