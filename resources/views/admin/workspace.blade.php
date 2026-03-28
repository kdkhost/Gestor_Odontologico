@extends('admin.layouts.panel')

@php($pageTitle = $module['label'])

@section('content_header')
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center admin-flex-gap">
        <div>
            <h1 class="m-0 text-dark">{{ $module['label'] }}</h1>
            <p class="text-muted mb-0">{{ $module['description'] }}</p>
        </div>
        <div class="d-flex flex-wrap admin-flex-gap-sm">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i>Dashboard
            </a>
            <a href="{{ $iframeUrl }}" target="_blank" rel="noreferrer" class="btn btn-primary">
                <i class="fas fa-up-right-from-square mr-1"></i>Abrir tela original
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="card card-outline card-primary admin-workspace-card" data-admin-tour="workspace-frame">
        <div class="card-header border-0 pb-0">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center admin-flex-gap">
                <div>
                    <h3 class="card-title mb-1">Workspace administrativo</h3>
                    <p class="text-muted mb-0">O conteudo operacional legado foi incorporado dentro do shell AdminLTE para uma navegacao mais limpa.</p>
                </div>
                <span class="badge badge-light text-uppercase">{{ $module['group'] }}</span>
            </div>
        </div>
        <div class="card-body">
            <div class="admin-iframe-shell">
                <iframe
                    src="{{ $iframeUrl }}"
                    title="{{ $module['label'] }}"
                    class="admin-filament-frame"
                    data-admin-filament-frame
                ></iframe>
            </div>
        </div>
    </div>
@stop
