<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50 dark:bg-gray-900 transition-colors duration-200">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — AlumniFinder</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        // Check local storage for theme
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-200">
    <div class="min-h-full">
        {{-- Sidebar --}}
        <div class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 dark:bg-gray-950 border-r border-transparent dark:border-gray-800 transform transition-transform duration-200 ease-in-out lg:translate-x-0"
            id="sidebar" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            {{-- Logo --}}
            <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-800">
                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-lg shadow-blue-500/30">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <span class="text-white font-bold text-lg tracking-tight">AlumniFinder</span>
            </div>

            {{-- Navigation --}}
            <nav class="mt-4 px-3 space-y-1">
                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('dashboard') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </a>

                <a href="{{ route('alumni.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('alumni.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Data Alumni
                </a>

                <a href="{{ route('tracking.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('tracking.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Tracking
                </a>

                <a href="{{ route('audit.index') }}"
                    class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('audit.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        Audit
                    </div>
                    @if(isset($needsAuditCount) && $needsAuditCount > 0)
                        <span class="bg-yellow-500 text-gray-900 text-[10px] font-bold px-2 py-0.5 rounded-full">
                            {{ $needsAuditCount }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('export') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('export') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export
                </a>

                <a href="{{ route('settings.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('settings.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Pengaturan
                </a>
            </nav>
        </div>

        {{-- Main Content --}}
        <div class="lg:pl-64">
            {{-- Top Bar --}}
            <header class="sticky top-0 z-40 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 transition-colors duration-200">
                <div class="flex items-center justify-between px-4 py-3 sm:px-6">
                    {{-- Mobile menu button --}}
                    <button type="button" class="lg:hidden text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                        onclick="toggleSidebar()">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white">@yield('page-title', 'Dashboard')</h1>

                    {{-- Notifications & User menu --}}
                    <div class="flex items-center gap-4 lg:gap-6">
                        {{-- Dark Mode Toggle --}}
                        <button onclick="toggleTheme()" type="button" class="text-gray-400 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-300 focus:outline-none transition-colors">
                            {{-- Sun (Light Mode) --}}
                            <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            {{-- Moon (Dark Mode) --}}
                            <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>

                        {{-- Notification Bell --}}
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="text-gray-400 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-300 relative p-1 focus:outline-none transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                @if (Auth::user()->unreadNotifications->count() > 0)
                                    <span class="absolute top-0 right-0 block h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white dark:ring-gray-900"></span>
                                @endif
                            </button>

                            {{-- Dropdown --}}
                            <div x-show="open" @click.away="open = false" 
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                class="absolute right-0 mt-2 w-72 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 py-2 z-50 overflow-hidden transform origin-top-right">
                                <div class="px-4 py-3 border-b border-gray-50 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
                                    <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Notifikasi</h3>
                                    @if (Auth::user()->unreadNotifications->count() > 0)
                                        <a href="#" class="text-[10px] font-medium text-blue-600 dark:text-blue-400 hover:underline">Tandai semua dibaca</a>
                                    @endif
                                </div>
                                <div class="max-h-80 overflow-y-auto">
                                    @forelse(Auth::user()->unreadNotifications as $notification)
                                        <div class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-50 dark:border-gray-700/50 last:border-0 relative">
                                            @if(!$notification->read_at)
                                                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-blue-500 rounded-r-full"></div>
                                            @endif
                                            <p class="text-xs font-semibold text-gray-900 dark:text-white {{ !$notification->read_at ? 'pl-2' : '' }}">{{ $notification->data['title'] ?? 'Notifikasi' }}</p>
                                            <p class="text-[11px] text-gray-600 dark:text-gray-400 mt-1 {{ !$notification->read_at ? 'pl-2' : '' }} line-clamp-2">{{ $notification->data['message'] ?? '' }}</p>
                                            <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1.5 {{ !$notification->read_at ? 'pl-2' : '' }}">{{ $notification->created_at->diffForHumans() }}</p>
                                        </div>
                                    @empty
                                        <div class="px-4 py-8 text-center flex flex-col items-center justify-center">
                                            <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                            </svg>
                                            <p class="text-xs text-gray-400 dark:text-gray-500">Tidak ada notifikasi baru</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="h-6 w-px bg-gray-200 dark:bg-gray-700"></div>

                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200 hidden sm:block">{{ Auth::user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="text-sm font-medium text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 transition-colors">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="mx-4 sm:mx-6 mt-6">
                    <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800/50 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl text-sm flex items-center justify-between shadow-sm"
                        id="flash-success">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-800/50 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="font-medium">{{ session('success') }}</span>
                        </div>
                        <button onclick="document.getElementById('flash-success').remove()"
                            class="text-green-500 dark:text-green-600 hover:text-green-700 dark:hover:text-green-400 focus:outline-none transition-colors ml-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="mx-4 sm:mx-6 mt-6">
                    <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800/50 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl text-sm flex items-center justify-between shadow-sm"
                        id="flash-error">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-red-100 dark:bg-red-800/50 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="font-medium">{{ session('error') }}</span>
                        </div>
                        <button onclick="document.getElementById('flash-error').remove()"
                            class="text-red-500 dark:text-red-600 hover:text-red-700 dark:hover:text-red-400 focus:outline-none transition-colors ml-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                </div>
            @endif

            {{-- Page Content --}}
            <main class="p-4 sm:p-6">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Sidebar overlay for mobile --}}
    <div class="fixed inset-0 bg-gray-900/50 z-40 lg:hidden hidden" id="sidebar-overlay" onclick="toggleSidebar()">
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
    </script>

    @stack('scripts')
</body>

</html>
