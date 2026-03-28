@extends('adminlte::page')

@section('title', trim(($pageTitle ?? 'Painel administrativo').' | '.config('app.name')))

@section('adminlte_css_pre')
    @parent
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/minified/introjs.min.css">
@stop

@section('adminlte_css')
    @parent
    <link rel="stylesheet" href="{{ asset('css/admin-panel.css') }}">
@stop

@section('footer')
    @include('admin.partials.footer')
@stop

@section('adminlte_js')
    @parent
    <script src="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/minified/intro.min.js"></script>
    <script src="{{ asset('js/admin-panel.js') }}"></script>
    @include('admin.partials.onboarding')
@stop
