{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="id" class="h-full dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>PCM - Login</title>

    {{-- Poppins (sama seperti dev.blade.php) --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Tailwind CDN (sama seperti dev.blade.php) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script>
        // Tailwind config ringan agar konsisten (dark, font)
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'ui-sans-serif', 'system-ui'] },
                    boxShadow: {
                        soft: '0 20px 60px -30px rgba(0,0,0,.65)'
                    }
                }
            }
        }
    </script>
</head>

<body class="h-full font-sans bg-gradient-to-br from-gray-950 via-slate-950 to-gray-950 text-gray-100">
    {{-- Background glow --}}
    <div class="pointer-events-none fixed inset-0 -z-10">
        <div class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-sky-500/20 blur-3xl"></div>
        <div class="absolute -bottom-40 -right-40 h-96 w-96 rounded-full bg-emerald-500/15 blur-3xl"></div>
        <div class="absolute inset-0 opacity-[0.06]" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 18px 18px;"></div>
    </div>

    <main class="min-h-screen flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-6xl">
            {{-- Header --}}
            <div class="mb-6 flex items-end justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-white/10 border border-white/10 flex items-center justify-center">
                        <svg class="h-6 w-6 text-white/90" viewBox="0 0 24 24" fill="none">
                            <path d="M12 2l8.5 5v10L12 22 3.5 17V7L12 2z" stroke="currentColor" stroke-width="1.7"/>
                            <path d="M12 22V12" stroke="currentColor" stroke-width="1.7"/>
                            <path d="M20.5 7L12 12 3.5 7" stroke="currentColor" stroke-width="1.7"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-lg font-semibold tracking-tight">PCM â€¢ RAPP</div>
                        <div class="text-sm text-white/60">Project Cost Management</div>
                    </div>
                </div>

                <div class="text-xs text-white/50">
                    Â© {{ date('Y') }} PCM
                </div>
            </div>

            {{-- Layout --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                {{-- Left: Login --}}
                <section class="lg:col-span-7">
                    <div class="rounded-2xl bg-white/5 border border-white/10 shadow-soft overflow-hidden">
                        <div class="px-6 py-5 sm:px-8 sm:py-6 border-b border-white/10">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="text-xl font-semibold tracking-tight">Masuk ke sistem</div>
                                    <div class="mt-1 text-sm text-white/60">Gunakan email & password. Hak akses mengikuti role (<span class="font-mono text-white/70">staff</span>/<span class="font-mono text-white/70">ho</span>).</div>
                                </div>

                                <div class="hidden sm:flex items-center gap-2">
                                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-500/10 px-3 py-1 text-[11px] text-emerald-100">
                                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span> HO
                                    </span>
                                    <span class="inline-flex items-center gap-2 rounded-full border border-sky-400/20 bg-sky-500/10 px-3 py-1 text-[11px] text-sky-100">
                                        <span class="h-2 w-2 rounded-full bg-sky-400"></span> Staff
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-6 sm:px-8 sm:py-7">
                            {{-- Error/Status --}}
                            @if (session('status'))
                                <div class="mb-5 rounded-xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                                    {{ session('status') }}
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="mb-5 rounded-xl border border-rose-400/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                                    <div class="font-semibold">Login gagal</div>
                                    <ul class="mt-2 list-disc pl-5 space-y-1 text-rose-100/90">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                                @csrf

                                <div>
                                    <label for="email" class="block text-sm font-medium text-white/80">Email</label>
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        required
                                        autocomplete="username"
                                        value="{{ old('email') }}"
                                        placeholder="contoh: staff@demo.test"
                                        class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-white/35 outline-none focus:ring-2 focus:ring-sky-400/20 focus:border-white/20"
                                    />
                                </div>

                                <div x-data="{ show:false }">
                                    <label for="password" class="block text-sm font-medium text-white/80">Password</label>
                                    <div class="mt-2 relative">
                                        <input
                                            id="password"
                                            name="password"
                                            :type="show ? 'text' : 'password'"
                                            required
                                            autocomplete="current-password"
                                            placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢"
                                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-white/35 outline-none focus:ring-2 focus:ring-sky-400/20 focus:border-white/20"
                                        />
                                        <button type="button" @click="show = !show"
                                                class="absolute inset-y-0 right-2 my-2 rounded-lg px-3 text-xs text-white/60 hover:bg-white/10">
                                            <span x-text="show ? 'sembunyi' : 'lihat'"></span>
                                        </button>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between gap-3">
                                    <label class="inline-flex items-center gap-2 text-sm text-white/70 select-none">
                                        <input type="checkbox" name="remember"
                                               class="rounded border-white/20 bg-white/10 text-sky-400 focus:ring-sky-400/20">
                                        Remember me
                                    </label>

                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}" class="text-sm text-white/60 hover:text-white/85">
                                            Lupa password?
                                        </a>
                                    @endif
                                </div>

                                <button type="submit"
                                        class="w-full rounded-xl bg-sky-500 px-4 py-3 text-sm font-semibold text-slate-950 shadow hover:bg-sky-400 active:bg-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-400/30">
                                    Masuk
                                </button>
                            </form>

                            {{-- Quick Fill --}}
                            <div class="mt-6 rounded-xl border border-white/10 bg-white/5 p-4">
                                <div class="text-xs font-semibold text-white/80">Isi cepat (opsional)</div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button type="button" data-fill="staff"
                                            class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs text-white/80 hover:bg-white/10">
                                        Isi Staff
                                    </button>
                                    <button type="button" data-fill="ho"
                                            class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs text-white/80 hover:bg-white/10">
                                        Isi HO
                                    </button>
                                </div>
                                <div class="mt-2 text-[11px] text-white/45">Tombol ini hanya mengisi form, tidak menyimpan password.</div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Right: Credentials --}}
                <aside class="lg:col-span-5">
                    <div class="rounded-2xl bg-white/5 border border-white/10 shadow-soft overflow-hidden">
                        <div class="px-6 py-5 sm:px-8 sm:py-6 border-b border-white/10">
                            <div class="text-base font-semibold">Akun Demo</div>
                            <div class="mt-1 text-sm text-white/60">Untuk testing role & fitur.</div>
                        </div>

                        <div class="px-6 py-6 sm:px-8 sm:py-7 space-y-4">
                            {{-- HO --}}
                            <div class="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-sm font-semibold text-emerald-100">Head Office (HO)</div>
                                    <span class="text-[11px] rounded-full border border-emerald-300/20 bg-emerald-500/10 px-3 py-1 text-emerald-100">
                                        FULL AKSES
                                    </span>
                                </div>

                                <div class="mt-4 space-y-2 text-sm text-emerald-100/90">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-emerald-100/70">Username</span>
                                        <span class="font-mono">admin@pcm.local</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-emerald-100/70">Password</span>
                                        <span class="font-mono">password</span>
                                    </div>

                                    <div class="mt-3 rounded-xl border border-emerald-300/15 bg-black/10 p-3 text-[12px] text-emerald-100/85">
                                        Alternatif HO:
                                        <div class="mt-2 font-mono">adhe5381@gmail.com / membanguN5381</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Staff --}}
                            <div class="rounded-2xl border border-sky-400/20 bg-sky-500/10 p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-sm font-semibold text-sky-100">Staff</div>
                                    <span class="text-[11px] rounded-full border border-sky-300/20 bg-sky-500/10 px-3 py-1 text-sky-100">
                                        TERBATAS
                                    </span>
                                </div>

                                <div class="mt-4 space-y-2 text-sm text-sky-100/90">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sky-100/70">Username</span>
                                        <span class="font-mono">staff@demo.test</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sky-100/70">Password</span>
                                        <span class="font-mono">password</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Notes --}}
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                                <div class="text-sm font-semibold text-white/85">Catatan Hak Akses</div>
                                <ul class="mt-3 space-y-2 text-sm text-white/60">
                                    <li class="flex gap-2">
                                        <span class="mt-[7px] h-1.5 w-1.5 rounded-full bg-white/40"></span>
                                        <span>HO bisa approve/reject & edit kapan pun pada transaksi.</span>
                                    </li>
                                    <li class="flex gap-2">
                                        <span class="mt-[7px] h-1.5 w-1.5 rounded-full bg-white/40"></span>
                                        <span>Staff edit transaksi hanya saat <span class="font-mono text-white/70">draft/rejected</span>.</span>
                                    </li>
                                    <li class="flex gap-2">
                                        <span class="mt-[7px] h-1.5 w-1.5 rounded-full bg-white/40"></span>
                                        <span>Delete Project/Client/Vendor/Province: <span class="text-white/80 font-medium">HO saja</span>.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </aside>

            </div>
        </div>
    </main>

    <script>
        (function () {
            const email = document.getElementById('email');
            const pass = document.getElementById('password');

            document.querySelectorAll('[data-fill]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const type = btn.getAttribute('data-fill');
                    if (type === 'staff') {
                        email.value = 'staff@demo.test';
                        pass.value = 'password';
                    } else if (type === 'ho') {
                        email.value = 'admin@pcm.local';
                        pass.value = 'password';
                    }
                    email.focus();
                });
            });
        })();
    </script>
</body>
</html>


