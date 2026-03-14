<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50 dark:bg-gray-900 transition-colors duration-300">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — AlumniFinder</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <script>
        // Check for saved theme preference or use system preference
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .dark .glass-panel {
            background: rgba(17, 24, 39, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .gradient-text {
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .bg-decorative {
            background-image: radial-gradient(circle at 15% 50%, rgba(59, 130, 246, 0.15), transparent 25%),
                              radial-gradient(circle at 85% 30%, rgba(139, 92, 246, 0.15), transparent 25%);
        }
        .dark .bg-decorative {
            background-image: radial-gradient(circle at 15% 50%, rgba(59, 130, 246, 0.1), transparent 25%),
                              radial-gradient(circle at 85% 30%, rgba(139, 92, 246, 0.1), transparent 25%);
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
    </style>
</head>

<body class="h-full bg-decorative relative overflow-hidden">
    {{-- Decorative backgrounds --}}
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-blue-400/20 dark:bg-blue-600/10 blur-[100px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-purple-400/20 dark:bg-purple-600/10 blur-[100px] pointer-events-none"></div>

    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-8 relative z-10">
        <div class="w-full max-w-5xl flex rounded-3xl shadow-2xl overflow-hidden glass-panel">
            
            {{-- Left Side: Branding / Showcase (Hidden on small screens) --}}
            <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-blue-600 to-indigo-800 p-12 flex-col justify-between relative overflow-hidden">
                <div class="absolute inset-0 bg-black/10"></div>
                <!-- Abstract floating geometric shapes -->
                <div class="absolute top-20 right-20 w-32 h-32 rounded-2xl bg-white/10 rotate-12 backdrop-blur-md animate-float" style="animation-delay: 0s;"></div>
                <div class="absolute bottom-20 left-10 w-24 h-24 rounded-full bg-blue-300/20 backdrop-blur-md animate-float" style="animation-delay: 1.5s;"></div>
                <div class="absolute top-1/2 left-1/2 w-48 h-48 rounded-full bg-indigo-400/10 -translate-x-1/2 -translate-y-1/2 backdrop-blur-sm animate-float" style="animation-delay: 3s;"></div>

                <div class="relative z-10 flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 backdrop-blur-md rounded-xl flex items-center justify-center border border-white/30">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <span class="text-white font-bold text-xl tracking-tight">AlumniFinder</span>
                </div>

                <div class="relative z-10 mt-auto">
                    <h1 class="text-4xl font-extrabold text-white mb-4 leading-tight">
                        Temukan & Lacak <br/>
                        <span class="text-blue-200">Jejak Alumni.</span>
                    </h1>
                    <p class="text-blue-100/80 text-lg max-w-sm">
                        Platform cerdas untuk mengelola dan memonitor data alumni sekolah dengan efisien, cepat, dan presisi.
                    </p>
                    
                    <div class="mt-8 flex items-center gap-4">
                        <div class="flex -space-x-3">
                            <div class="w-10 h-10 rounded-full border-2 border-indigo-800 bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-500">A</div>
                            <div class="w-10 h-10 rounded-full border-2 border-indigo-800 bg-gray-300 flex items-center justify-center text-xs font-bold text-gray-600">B</div>
                            <div class="w-10 h-10 rounded-full border-2 border-indigo-800 bg-gray-400 flex items-center justify-center text-xs font-bold text-gray-700">C</div>
                        </div>
                        <span class="text-sm font-medium text-blue-100">Bergabung dengan 1,000+ alumni</span>
                    </div>
                </div>
            </div>

            {{-- Right Side: Login Form --}}
            <div class="w-full lg:w-1/2 p-8 sm:p-12 lg:p-16 bg-white dark:bg-gray-900 transition-colors duration-300 flex flex-col justify-center">
                {{-- Mobile Logo --}}
                <div class="flex items-center justify-center gap-3 lg:hidden mb-8">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-gray-900 dark:text-white">AlumniFinder</span>
                </div>

                <div class="max-w-md w-full mx-auto">
                    <div class="mb-10 text-center lg:text-left">
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">Selamat Datang 👋</h2>
                        <p class="mt-2 text-gray-500 dark:text-gray-400">Silakan masuk ke akun Anda untuk melanjutkan.</p>
                    </div>

                    <form method="POST" action="{{ route('login') }}" class="space-y-6">
                        @csrf

                        <div class="space-y-1">
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alamat Email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                    </svg>
                                </div>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                                    class="block w-full pl-10 px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400 outline-none transition-all duration-200 shadow-sm
                                    @error('email') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror"
                                    placeholder="admin@alumnifinder.test">
                            </div>
                            @error('email')
                                <p class="mt-1.5 text-xs text-red-500 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kata Sandi</label>
                                <a href="#" class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">Lupa sandi?</a>
                            </div>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input type="password" name="password" id="password" required
                                    class="block w-full pl-10 px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400 outline-none transition-all duration-200 shadow-sm"
                                    placeholder="••••••••">
                            </div>
                            @error('password')
                                <p class="mt-1.5 text-xs text-red-500 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="remember" id="remember"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-400 dark:ring-offset-gray-900 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="remember" class="ml-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 cursor-pointer select-none">Biarkan saya tetap masuk</label>
                        </div>

                        <button type="submit"
                            class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-xl shadow-sm text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-900 transform transition-all duration-200 hover:scale-[1.01] active:scale-[0.98]">
                            Masuk
                        </button>
                    </form>
                    
                    <div class="mt-8 text-center">
                        <p class="text-xs text-gray-400 dark:text-gray-500 font-medium">
                            AlumniFinder v1.0 &copy; {{ date('Y') }}
                        </p>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
    
    {{-- Inline script for very basic floating animation fallback if CSS gets stripped --}}
    <script>
        // Set dynamic minimum height based on viewport
        function setMinHeight() {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }
        window.addEventListener('resize', setMinHeight);
        setMinHeight();
    </script>
</body>

</html>
