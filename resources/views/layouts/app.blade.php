<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @yield('title', 'Khalil Déco')
    </title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white text-secondary-shade antialiased selection:bg-primary selection:text-white">

    {{-- Header --}}
    @include('components.header')

    {{-- Message de confirmation (ex : compte supprimé...) --}}
    @include('components.flash-status')

    {{-- Modale centrée pour la confirmation de vérification d'email --}}
    @include('components.email-verified-modal')

    {{-- Rappel adresse de livraison --}}
    @include('components.address-reminder')

    {{-- Contenu --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    @include('components.footer')

    {{-- Assistant IA --}}
    @include('components.ai-assistant')

</body>
</html>