@php
    $flashMessages = array_merge(
        session('status')  ? [['message' => session('status'),  'type' => 'success']] : [],
        session('error')   ? [['message' => session('error'),   'type' => 'error']]   : [],
        collect($errors->all())->map(fn($e) => ['message' => $e, 'type' => 'error'])->all()
    );
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Connexion') — Espace Mokolo d'Obala</title>
    <script>window.__flashMessages = @json($flashMessages);</script>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="toast-container" x-data>
        <template x-for="t in $store.toast.items" :key="t.id">
            <div class="toast" :class="'toast--' + t.type">
                <div class="toast__icon">
                    <template x-if="t.type === 'success'">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </template>
                    <template x-if="t.type === 'error'">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                    </template>
                </div>
                <div class="toast__content" x-text="t.message"></div>
                <button class="toast__close" @click="$store.toast.remove(t.id)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </template>
    </div>
    @yield('content')
</body>
</html>
