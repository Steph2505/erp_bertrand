<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Connexion') — Bertrand Store</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    @yield('content')
</body>
</html>
