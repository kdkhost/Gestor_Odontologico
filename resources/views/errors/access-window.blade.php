@extends('layouts.base')

@section('title', 'Acesso fora do horário permitido')

@section('content')
    <section class="hero">
        <span class="eyebrow">{{ $greeting }}</span>
        <h1>Acesso fora do horário permitido.</h1>
        <p>Seu perfil possui restrição de acesso por turno ou janela operacional. Se você precisa de liberação emergencial, solicite ao superadmin ou valide seu IP/dispositivo no módulo de manutenção.</p>
    </section>
@endsection
