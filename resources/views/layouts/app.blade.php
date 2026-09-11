@php
    $flashMessages = array_merge(
        session('success') ? [['message' => session('success'), 'type' => 'success']] : [],
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
    <title>@yield('title', 'ERP') — Espace Mokolo d'Obala</title>
    <script>
        window.CURRENCY = '{{ config('app.currency_symbol', 'XOF') }}';
        window.__flashMessages = @json($flashMessages);
    </script>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @stack('styles')
</head>
<body x-data="{ sidebarOpen: false }">

{{-- ──────────── TOASTS ──────────── --}}
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
                <template x-if="t.type === 'warning'">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                </template>
                <template x-if="t.type === 'info'">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                </template>
            </div>
            <div class="toast__content" x-text="t.message"></div>
            <button class="toast__close" @click="$store.toast.remove(t.id)">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>

{{-- ──────────── MODAL DE CONFIRMATION GLOBAL ──────────── --}}
<div class="modal-overlay" x-data x-show="$store.confirmDialog.visible" x-cloak x-transition>
    <div class="modal modal--sm">
        <div class="modal__header">
            <h3 x-text="$store.confirmDialog.title"></h3>
        </div>
        <div class="modal__body">
            <p x-text="$store.confirmDialog.message" style="font-size:14px;color:#374151;line-height:1.5;"></p>
            <div class="modal-footer-std">
                <button type="button" class="btn btn--ghost" @click="$store.confirmDialog.cancel()" x-text="$store.confirmDialog.cancelLabel"></button>
                <button type="button" class="btn" :class="$store.confirmDialog.variant === 'danger' ? 'btn--danger' : 'btn--primary'" @click="$store.confirmDialog.confirm()" x-text="$store.confirmDialog.confirmLabel"></button>
            </div>
        </div>
    </div>
</div>

<div class="app-wrapper">

    {{-- ──────────── SIDEBAR ──────────── --}}
    <aside class="sidebar" :class="{ 'sidebar--open': sidebarOpen }" id="sidebar">

        {{-- Brand --}}
        <div class="sidebar__brand">
            <div class="sidebar__brand-logo">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}">
            </div>
            <div class="sidebar__brand-name">
                Espace Mokolo d'Obala
                <span>ERP v1.0</span>
            </div>
        </div>

        {{-- Utilisateur --}}
        <!-- <div class="sidebar__user">
            <div class="sidebar__user-avatar">
                @if(auth()->user()->avatar_url)
                    <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}">
                @else
                    {{ auth()->user()->initials }}
                @endif
            </div>
            <div class="sidebar__user-info">
                <p>{{ auth()->user()->name }}</p>
                <span>{{ auth()->user()->getRoleNames()->first() ?? 'Utilisateur' }}</span>
            </div>
        </div> -->

        {{-- Navigation --}}
        <nav class="sidebar__nav">
            @foreach(config('sidebar') as $item)
                @php $itemPerm = $item['permission'] ?? null; @endphp
                @if($itemPerm && !auth()->user()?->can($itemPerm)) @continue @endif

                @if($item['type'] === 'separator')
                    <div class="sidebar__section-label">{{ $item['label'] }}</div>

                @elseif($item['type'] === 'item')
                    @php
                        $routeExists = \Illuminate\Support\Facades\Route::has($item['route']);
                        $isActive    = $routeExists && request()->routeIs($item['route'] . '*');
                    @endphp
                    @if($routeExists)
                        <a href="{{ route($item['route']) }}"
                           class="sidebar__item {{ $isActive ? 'sidebar__item--active' : '' }}">
                            @include('components.icon', ['name' => $item['icon']])
                            {{ $item['label'] }}
                        </a>
                    @endif

                @elseif($item['type'] === 'group')
                    @php
                        $visibleChildren = collect($item['items'])->filter(function ($child) {
                            $perm = $child['permission'] ?? null;
                            if ($perm && !auth()->user()?->can($perm)) return false;
                            return \Illuminate\Support\Facades\Route::has($child['route']);
                        });
                        $isGroupActive = $visibleChildren->contains(fn($c) => request()->routeIs($c['route'] . '*'));
                    @endphp
                    @if($visibleChildren->isNotEmpty())
                    <div x-data="{ open: {{ $isGroupActive ? 'true' : 'false' }} }">
                        <button class="sidebar__group-trigger {{ $isGroupActive ? 'sidebar__group-trigger--active' : '' }}"
                                @click="open = !open"
                                :aria-expanded="open">
                            @include('components.icon', ['name' => $item['icon'], 'class' => 'icon'])
                            <span>{{ $item['label'] }}</span>
                            <svg class="chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div class="sidebar__group-items" x-show="open" x-collapse>
                            @foreach($visibleChildren as $child)
                                @php
                                    $isChildActive = request()->routeIs($child['route'] . '*');
                                    $hasIndent     = $child['indent'] ?? false;
                                @endphp
                                <a href="{{ route($child['route']) }}"
                                   class="sidebar__group-item {{ $isChildActive ? 'sidebar__group-item--active' : '' }} {{ $hasIndent ? 'sidebar__group-item--indent' : '' }}">
                                    {{ $child['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                @endif
            @endforeach
        </nav>

        {{-- Footer sidebar --}}
        <div class="sidebar__footer">
            <form method="POST" action="{{ route('logout') }}" @submit.prevent="window.confirmDialog('Voulez-vous vraiment vous déconnecter ?', {confirmLabel:'Déconnexion'}).then(ok => ok && $el.submit())">
                @csrf
                <button type="submit" class="sidebar__item sidebar__logout-btn w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="sidebar__logout-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                    Déconnexion
                </button>
            </form>
        </div>
    </aside>

    {{-- Overlay mobile --}}
    <div class="sidebar-overlay" :class="{ 'sidebar-overlay--visible': sidebarOpen }" @click="sidebarOpen = false"></div>

    {{-- ──────────── MAIN ──────────── --}}
    <div class="main-content">

        {{-- TOPBAR --}}
        <header class="topbar">
            <button class="topbar__toggle" @click="sidebarOpen = !sidebarOpen" aria-label="Menu">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
            </button>

            {{-- Breadcrumb --}}
            <nav class="topbar__breadcrumb">
                <a href="{{ route('dashboard') }}">Accueil</a>
                @hasSection('breadcrumb')
                    <span class="sep">/</span>
                    @yield('breadcrumb')
                @endif
            </nav>

            {{-- Recherche --}}
            <div class="topbar__search">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input type="text" placeholder="Rechercher produit, client, facture..." id="global-search">
            </div>

            <div class="topbar__right">

                {{-- Raccourci POS --}}
                @can('manage pos')
                <a href="{{ route('pos.index') }}"
                   class="topbar__pos-btn {{ request()->routeIs('pos.*') ? 'topbar__pos-btn--active' : '' }}"
                   title="Point de vente">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
                    <span>POS</span>
                </a>
                @endcan

                {{-- Notifications --}}
                <div x-data="{ open: false }" class="topbar__notif-wrapper">
                    <button class="topbar__notification" @click="open = !open">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                        @php $unreadCount = auth()->user()->unreadNotifications->count(); @endphp
                        @if($unreadCount > 0)
                            <span class="topbar__notification-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </button>
                </div>

                {{-- Profil dropdown --}}
                <div x-data="{ open: false }" class="topbar__profile">
                    <button class="topbar__profile-btn" @click="open = !open" @click.outside="open = false">
                        <div class="topbar__profile-avatar">
                            @if(auth()->user()->avatar_url)
                                <img src="{{ auth()->user()->avatar_url }}" alt="">
                            @else
                                {{ auth()->user()->initials }}
                            @endif
                        </div>
                        <span class="topbar__profile-name">{{ auth()->user()->name }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="topbar__profile-chevron"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>

                    <div class="topbar__profile-dropdown" x-show="open" x-transition>
                        <div class="topbar__profile-dropdown-header">
                            <p>{{ auth()->user()->name }}</p>
                            <span>{{ auth()->user()->email }}</span>
                        </div>
                        <a href="{{ route('users.profile') }}" class="topbar__profile-dropdown-item">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                            Mon profil
                        </a>
                        <a href="{{ route('settings.company') }}" class="topbar__profile-dropdown-item">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            Paramètres
                        </a>
                        <div class="topbar__dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}" @submit.prevent="window.confirmDialog('Voulez-vous vraiment vous déconnecter ?', {confirmLabel:'Déconnexion'}).then(ok => ok && $el.submit())">
                            @csrf
                            <button type="submit" class="topbar__profile-dropdown-item topbar__profile-dropdown-item--danger w-full">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- Contenu --}}
        <main class="page-content">

            @yield('content')
        </main>

        {{-- Footer --}}
        <footer class="page-footer">
            © {{ date('Y') }} Espace Mokolo d'Obala — ERP v1.0.0
        </footer>
    </div>

</div>

@stack('scripts')
</body>
</html>
