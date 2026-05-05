<x-app-layout>
    <section class="mb-48 relative pt-20">
        <div class="relative z-10 text-center max-w-5xl mx-auto mb-20">
            <h1
                class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold tracking-tighter leading-[0.85] mb-10 text-black break-words">
                Backend-as-a-Service,<br>But it's Laravel
            </h1>

            <p class="text-lg sm:text-xl md:text-2xl max-w-2xl mx-auto mb-12 tracking-tight leading-tight break-words">
                Reinventing the wheel for comfort. A modular BaaS engine for the Laravel framework you use every
                day.
            </p>

            <div class="flex justify-center">
                <a href="#explore"
                    class="brutalist-button text-2xl sm:text-3xl px-12 sm:px-16 py-4 sm:py-6 bg-blue-500 text-white border-black">Explore</a>
            </div>

            <!-- Floating Objects -->
            <!-- Laravel -->
            <div class="floating-object hidden lg:block -top-10 -left-40 rotate-12"
                style="animation: float 5s ease-in-out infinite;">
                <img src="https://cdn.simpleicons.org/laravel/FF2D20" alt="Laravel"
                    class="w-24 h-24 opacity-10 hover:opacity-40 transition-opacity">
            </div>
            <!-- MySQL -->
            <div class="floating-object hidden lg:block top-40 -right-40 -rotate-12"
                style="animation: float-slow 7s ease-in-out infinite;">
                <img src="https://cdn.simpleicons.org/mysql" alt="MySQL"
                    class="                   w-24 h-24 opacity-10 hover:opacity-40 transition-opacity">
            </div>
            <!-- SQLite -->
            <div class="floating-object hidden lg:block bottom-0 -left-48 -rotate-6"
                style="animation: float-reverse 6s ease-in-out infinite;">
                <img src="https://cdn.simpleicons.org/sqlite" alt="SQLite"
                    class="w-24 h-24 opacity-10 hover:opacity-40 transition-opacity">
            </div>
            <!-- PostgreSQL -->
            <div class="floating-object hidden lg:block -bottom-20 -right-48 rotate-6"
                style="animation: float 8s ease-in-out infinite;">
                <img src="https://cdn.simpleicons.org/postgresql" alt="PostgreSQL"
                    class="w-24 h-24 opacity-10 hover:opacity-40 transition-opacity">
            </div>
        </div>

        <div class="relative max-w-4xl mx-auto z-20 px-4 sm:px-0">
            <div
                class="brutalist-card bg-black text-white p-6 sm:p-10 brutalist-shadow-blue transform -rotate-1 sm:-rotate-2 hover:rotate-0 transition-transform duration-300">
                <div class="flex items-center justify-between mb-8 border-b border-white/20 pb-4">
                    <div class="flex items-center gap-4">
                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="mono text-xs text-white/40 ml-4">quickstart</span>
                    </div>
                    <button onclick="copyCommand(this)"
                        class="mono text-xs uppercase font-bold px-3 py-1 border border-white/20 hover:bg-white hover:text-black transition-colors">
                        <span class="copy-text">Copy</span>
                        <span class="copied-text" style="display: none;">Copied!</span>
                    </button>
                    <script>
                        function copyCommand(btn) {
                            const text = 'composer create-project veloquent/veloquent project-name && cd project-name && php artisan serve';
                            navigator.clipboard.writeText(text).then(() => {
                                const copyText = btn.querySelector('.copy-text');
                                const copiedText = btn.querySelector('.copied-text');
                                copyText.style.display = 'none';
                                copiedText.style.display = 'block';
                                setTimeout(() => {
                                    copyText.style.display = 'block';
                                    copiedText.style.display = 'none';
                                }, 2000);
                            }).catch(err => {
                                console.error('Failed to copy: ', err);
                            });
                        }
                    </script>
                </div>
                <pre class="mono text-sm sm:text-base md:text-lg leading-relaxed overflow-x-auto">
<span class="text-blue-400 select-none">$ </span>composer create-project veloquent/veloquent project-name
<span class="text-blue-400 select-none">$ </span>cd project-name
<span class="text-blue-400 select-none">$ </span>php artisan serve</pre>
                <div class="mt-8 pt-4 border-t border-white/20 mono text-xs text-white/40 uppercase tracking-widest">
                    PHP 8.3+ Required
                </div>
            </div>

            <div
                class="absolute -top-10 -right-10 w-40 h-40 bg-blue-500 brutalist-border -z-10 opacity-50 hidden sm:block">
            </div>
            <div
                class="absolute -bottom-10 -left-10 w-40 h-40 bg-white brutalist-border -z-10 opacity-50 hidden sm:block">
            </div>
        </div>

        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full ocean-texture opacity-20 -z-10"></div>
    </section>

    <section class="mb-56 relative">
        <div class="bg-white brutalist-border brutalist-shadow overflow-hidden">
            <div class="bg-black text-white px-10 py-5 flex justify-between items-center">
                <div class="flex items-center gap-6">
                    <div class="w-5 h-5 bg-blue-500"></div>
                    <span class="mono text-base font-bold tracking-widest uppercase">VeloPHP - Dashboard</span>
                </div>
            </div>
            <div class="bg-blue-500/10 p-4 relative">
                <img src="{{ asset('assets/dashboard.png') }}" alt="VeloquentDashboard" class="w-full brutalist-border">
                <div class="absolute bottom-8 right-8 bg-white p-4 brutalist-border">
                    <a href="https://demo.velophp.com" class="mono text-base font-bold tracking-widest uppercase">Try
                        Demo</a>
                </div>
            </div>
        </div>
    </section>

    <section id="explore" class="mb-32 sm:mb-56">
        <div class="flex items-center gap-4 sm:gap-8 mb-12 sm:mb-20">
            <h2 class="text-6xl sm:text-8xl md:text-9xl font-black uppercase tracking-tighter">WHY</h2>
            <div class="h-2 bg-black grow"></div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-8 sm:gap-16">
            <div
                class="sm:col-span-2 lg:col-span-6 brutalist-card p-8 sm:p-12 md:p-16 relative flex flex-col justify-between overflow-hidden">
                <div>
                    <div
                        class="w-16 h-16 sm:w-24 sm:h-24 bg-white brutalist-border mb-6 sm:mb-10 flex items-center justify-center">
                        <svg class="w-10 h-10 sm:w-14 sm:h-14 text-black" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="3"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011-1v5m-4 0h4">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-3xl sm:text-5xl md:text-6xl font-black">
                        Built In
                    </h3>
                    <h3 class="text-3xl sm:text-5xl md:text-6xl font-black mb-6 sm:mb-8">
                        Multi-Tenancy
                    </h3>
                    <p class="text-lg sm:text-2xl md:text-3xl font-bold leading-[1.1] mb-8 sm:mb-12">
                        Laravel projects are bloated enough. Run one instance, use it everywhere. Focus on shipping.
                    </p>
                </div>
            </div>

            <div
                class="sm:col-span-1 lg:col-span-6 bg-black text-white brutalist-border brutalist-shadow p-8 sm:p-12 md:p-16 relative border-blue-500">
                <div
                    class="w-16 h-16 sm:w-24 sm:h-24 bg-white brutalist-border mb-6 sm:mb-10 flex items-center justify-center">
                    <svg class="w-10 h-10 sm:w-14 sm:h-14 text-black" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="3"
                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-3xl sm:text-5xl md:text-6xl font-black mb-6 sm:mb-10 leading-none">
                    Just Like Any Other BaaS</h3>
                <p class="text-lg sm:text-xl md:text-2xl font-bold leading-tight opacity-90">The developer experience of
                    Pocketbase or Supabase with the power of Laravel. Dynamic collections and instant APIs, exactly how
                    you expect.</p>
            </div>

            <div class="sm:col-span-1 lg:col-span-5 brutalist-card p-8 sm:p-12 md:p-16 relative border-dashed">
                <div
                    class="w-12 h-12 sm:w-16 sm:h-16 bg-blue-500 brutalist-border flex items-center justify-center mb-6 sm:mb-10">
                    <svg class="w-8 h-8 sm:w-10 sm:h-10 text-white" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="3"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h3 class="text-3xl sm:text-5xl md:text-6xl font-black mb-6 sm:mb-8">
                    Ecosystem</h3>
                <p class="text-lg sm:text-xl md:text-2xl font-bold leading-tight opacity-80">Experience the massive
                    ecosystem
                    Laravel is known for.
                </p>
            </div>

            <div
                class="sm:col-span-2 lg:col-span-7 bg-blue-500 text-white brutalist-border brutalist-shadow p-8 sm:p-12 md:p-16 ocean-texture relative">
                <div
                    class="w-16 h-16 sm:w-24 sm:h-24 bg-black brutalist-border mb-6 sm:mb-10 flex items-center justify-center">
                    <svg class="w-10 h-10 sm:w-14 sm:h-14 text-white" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="3"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                        </path>
                    </svg>
                </div>
                <h3 class="text-3xl sm:text-5xl md:text-6xl font-black mb-6 sm:mb-8 leading-none">
                    No Vendor<br>Lock-In</h3>
                <p class="text-lg sm:text-xl md:text-2xl font-bold leading-[1.1]">
                    Keep full control of your infrastructure. Run your own BaaS on affordable shared hosting or a full
                    VPS—anywhere PHP runs.
                </p>
            </div>
        </div>
    </section>

    <section class="mb-56 relative">
        <div class="flex items-center gap-4 sm:gap-8 mb-12 sm:mb-20">
            <h2 class="text-6xl sm:text-8xl md:text-9xl font-black uppercase tracking-tighter">SDKS</h2>
            <div class="h-2 bg-black grow"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 sm:gap-16 mb-16 sm:mb-24">
            <!-- JS SDK -->
            <div class="brutalist-card p-8 sm:p-12 bg-white relative overflow-hidden">
                <div class="flex justify-between items-center mb-10">
                    <div class="flex gap-6 items-center">
                        <!-- React -->
                        <img src="https://cdn.simpleicons.org/react/61DAFB" alt="React" class="w-8 h-8">
                        <!-- Vue.js -->
                        <img src="https://cdn.simpleicons.org/vuedotjs/4FC08D" alt="Vue.js" class="w-8 h-8">
                        <!-- Svelte -->
                        <img src="https://cdn.simpleicons.org/svelte/FF3E00" alt="Svelte" class="w-8 h-8">
                        <!-- Next.js -->
                        <img src="https://cdn.simpleicons.org/nextdotjs/000000" alt="Next.js" class="w-8 h-8">
                    </div>
                    <div class="mono text-[10px] font-bold bg-black text-white px-2 py-1 uppercase">Adapter Pattern
                    </div>
                </div>
                <h3 class="text-4xl sm:text-5xl font-black mb-6 uppercase">JS SDK</h3>
                <p class="text-xl font-bold leading-tight opacity-80">
                    Integrate into any JS environment. Use the built in adapters or bring in your own.
                </p>
            </div>

            <!-- Flutter SDK -->
            <div class="brutalist-card p-8 sm:p-12 bg-white relative overflow-hidden">
                <div class="flex justify-between items-start mb-10">
                    <img src="https://flutter.dev/assets/lockup_flutter_horizontal.549a1b7dd82615e8e9c95c1ade8cee42.svg"
                        alt="Flutter" class="w-12 h-12 object-cover object-left">
                    <div class="mono text-[10px] font-bold bg-black text-white px-2 py-1 uppercase">Flutter Package
                    </div>
                </div>
                <h3 class="text-4xl sm:text-5xl font-black mb-6 uppercase">Flutter SDK</h3>
                <p class="text-xl font-bold leading-tight opacity-80">
                    The official <strong>Flutter Package</strong> for Veloquent.
                </p>
            </div>
        </div>

        <div class="flex justify-center">
            <a href="{{ url('/docs/quickstart#content-client-setup') }}"
                class="brutalist-button text-2xl sm:text-3xl md:text-4xl px-8 sm:px-12 md:px-16 py-4 sm:py-6 md:py-8 bg-blue-500 text-white border-black">
                SDK SETUP &rarr;
            </a>
        </div>
    </section>

    <section class="mb-32 relative">
        <div class="brutalist-card bg-white text-black p-12 sm:p-20 md:p-24 text-center relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-full ocean-texture opacity-10"></div>
            <div class="relative z-10">
                <h2 class="text-5xl sm:text-7xl md:text-8xl font-black mb-10 leading-none">
                    Give it a try
                </h2>
                <p class="text-xl sm:text-2xl md:text-3xl font-bold mb-16 max-w-4xl mx-auto opacity-80">
                    Deploy your own BaaS in minutes. Keep full control of your data and infrastructure.
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-8">
                    <a href="{{ route('docs.shortcut', 'quickstart') }}"
                        class="brutalist-button bg-black text-white text-2xl px-12 py-6">
                        GET STARTED
                    </a>
                    <a href="https://github.com/kevintherm/veloquent" target="_blank"
                        class="brutalist-button bg-blue-500 text-white border-black text-2xl px-12 py-6">
                        VIEW ON GITHUB
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>