@extends('layouts.app')

@section('title', 'Désinscription — Khalil Déco')

@section('content')

<div class="mx-auto flex max-w-lg flex-col items-center px-6 py-24 text-center sm:px-10">
    <span class="flex h-14 w-14 items-center justify-center bg-primary-tint text-primary">
        <i class="fa-solid fa-envelope-circle-check text-xl"></i>
    </span>
    <h1 class="mt-6 font-display text-2xl font-normal italic text-secondary-shade sm:text-3xl">Vous êtes désinscrit</h1>
    <p class="mt-3 text-sm text-grey">
        L'adresse <strong class="text-secondary-shade">{{ $subscriber->email }}</strong> ne recevra plus la newsletter de Khalil Déco.
        Vous pouvez vous réinscrire à tout moment depuis le bas de n'importe quelle page du site.
    </p>
    <a href="{{ route('home') }}" class="mt-8 inline-flex items-center gap-2 border border-secondary-shade px-6 py-3 text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade transition hover:bg-secondary-shade hover:text-white">
        <i class="fa-solid fa-arrow-left"></i>Retour à l'accueil
    </a>
</div>

@endsection
