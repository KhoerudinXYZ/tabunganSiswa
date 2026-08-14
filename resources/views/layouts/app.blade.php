<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - {{ config('app.name') }}</title>

    {{-- PWA Meta Tags --}}
    <meta name="theme-color" content="#6366f1">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TabunganSiswa">
    <meta name="application-name" content="TabunganSiswa">
    <meta name="msapplication-TileColor" content="#6366f1">
    <meta name="msapplication-TileImage" content="{{ asset('images/icons/icon-144x144.png') }}">
    <meta name="description" content="Aplikasi manajemen tabungan siswa SDN 4 Rambatan Kulon">

    {{-- PWA Manifest & Icons --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-192x192.png') }}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{ asset('images/icons/icon-152x152.png') }}">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('images/icons/icon-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/icons/icon-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('images/icons/icon-96x96.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/icons/icon-96x96.png') }}">

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* PWA Install Button */
        #pwa-install-btn {
            display: none;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 7px 14px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
            white-space: nowrap;
            animation: pulse-install 2s infinite;
        }
        #pwa-install-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(99, 102, 241, 0.5);
        }
        #pwa-install-btn.visible {
            display: flex;
        }
        @keyframes pulse-install {
            0%, 100% { box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4); }
            50% { box-shadow: 0 2px 20px rgba(99, 102, 241, 0.7); }
        }

        /* PWA Toast Notification */
        #pwa-toast {
            display: none;
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: linear-gradient(135deg, #1e293b, #0f172a);
            border: 1px solid rgba(99, 102, 241, 0.4);
            border-radius: 14px;
            padding: 14px 20px;
            z-index: 9999;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            max-width: 340px;
            width: 90%;
        }
        #pwa-toast.show {
            display: flex;
            align-items: center;
            gap: 14px;
            transform: translateX(-50%) translateY(0);
        }
        #pwa-toast img {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            flex-shrink: 0;
        }
        #pwa-toast .toast-content { flex: 1; }
        #pwa-toast .toast-title {
            font-size: 13px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 2px;
        }
        #pwa-toast .toast-desc {
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 10px;
        }
        #pwa-toast .toast-actions { display: flex; gap: 8px; }
        #pwa-toast .btn-install-toast {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            flex: 1;
        }
        #pwa-toast .btn-dismiss-toast {
            background: rgba(255,255,255,0.08);
            color: #94a3b8;
            border: none;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="layout-wrapper">

        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand" style="justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" style="width: 36px; height: 36px; border-radius: 4px; object-fit: contain; background: white; padding: 2px;">
                    <span style="font-size: 15px; font-weight: 700;">SDN 4 Rambatan Kulon</span>
                </div>
                <button class="mobile-menu-btn" style="color: white; padding: 4px;" onclick="document.querySelector('.sidebar').classList.remove('open')" aria-label="Close Menu">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <ul class="sidebar-menu">
                @if(auth()->user()->isAdmin() || auth()->user()->isGuru())
                    <li class="sidebar-item {{ Request::is('dashboard') ? 'active' : '' }}">
                        <a href="{{ route('dashboard') }}">
                            <i class="fa-solid fa-chart-pie"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    @if(auth()->user()->isAdmin())
                        <li class="sidebar-item {{ Request::is('kelas*') ? 'active' : '' }}">
                            <a href="{{ route('kelas.index') }}">
                                <i class="fa-solid fa-school"></i>
                                <span>Manajemen Kelas</span>
                            </a>
                        </li>
                        <li class="sidebar-item {{ Request::is('guru*') ? 'active' : '' }}">
                            <a href="{{ route('guru.index') }}">
                                <i class="fa-solid fa-chalkboard-user"></i>
                                <span>Manajemen Guru</span>
                            </a>
                        </li>
                    @elseif(auth()->user()->isGuru())
                        <li class="sidebar-item {{ Request::is('kelas-saya*') ? 'active' : '' }}">
                            <a href="{{ route('kelas.saya') }}">
                                <i class="fa-solid fa-school"></i>
                                <span>Kelas Saya</span>
                            </a>
                        </li>
                    @endif
                    <li class="sidebar-item {{ Request::is('siswa*') ? 'active' : '' }}">
                        <a href="{{ route('siswa.index') }}">
                            <i class="fa-solid fa-graduation-cap"></i>
                            <span>Data Siswa</span>
                        </a>
                    </li>
                    <li class="sidebar-item {{ Request::is('transaksi*') && !Request::is('transaksi/kolektif*') ? 'active' : '' }}">
                        <a href="{{ route('transaksi.index') }}">
                            <i class="fa-solid fa-money-bill-transfer"></i>
                            <span>Transaksi Tabungan</span>
                        </a>
                    </li>
                    <li class="sidebar-item {{ Request::is('transaksi/kolektif*') ? 'active' : '' }}">
                        <a href="{{ route('transaksi.kolektif.form') }}">
                            <i class="fa-solid fa-people-group"></i>
                            <span>Transaksi Kolektif</span>
                        </a>
                    </li>
                @else
                    <li class="sidebar-item {{ Request::is('siswa/dashboard') ? 'active' : '' }}">
                        <a href="{{ route('siswa.dashboard') }}">
                            <i class="fa-solid fa-chart-pie"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                @endif
            </ul>

            <div class="sidebar-footer">
                <a href="{{ route('profile.edit') }}" class="user-profile-bar" style="text-decoration: none; color: inherit; display: flex; align-items: center; padding: 10px; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'">
                    <div class="user-avatar">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="user-info" style="margin-left: 12px;">
                        <div class="user-name">{{ auth()->user()->name }}</div>
                        <div class="user-role">{{ ucfirst(auth()->user()->role) }}</div>
                    </div>
                </a>
                <form action="{{ route('logout') }}" method="POST" class="mt-4">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm btn-block">
                        <i class="fa-solid fa-right-from-bracket"></i> Keluar
                    </button>
                </form>
            </div>
        </aside>

        <!-- Overlay (Moved here so sibling selector works) -->
        <div class="sidebar-overlay" onclick="document.querySelector('.sidebar').classList.remove('open')"></div>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-navbar">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <button class="mobile-menu-btn" onclick="document.querySelector('.sidebar').classList.toggle('open')" aria-label="Toggle Menu">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="page-title">@yield('page_title')</div>
                </div>
                <div class="navbar-actions" style="display:flex; align-items:center; gap:12px;">
                    <button id="pwa-install-btn" title="Install aplikasi ke perangkat Anda">
                        <i class="fa-solid fa-download"></i>
                        <span>Install App</span>
                    </button>
                    <span style="font-size: 14px; font-weight: 500; color: var(--slate);">
                        Hari ini: <strong>{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</strong>
                    </span>
                </div>
            </header>

            <div class="content-body">
                @if (session('success'))
                    <div class="alert alert-success">
                        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-xmark"></i> {{ $errors->first() }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    @yield('scripts')

    {{-- PWA Toast Notification --}}
    <div id="pwa-toast">
        <img src="{{ asset('images/icons/icon-96x96.png') }}" alt="App Icon">
        <div class="toast-content">
            <div class="toast-title">📲 Install TabunganSiswa</div>
            <div class="toast-desc">Tambahkan ke layar utama untuk akses cepat tanpa buka browser!</div>
            <div class="toast-actions">
                <button class="btn-install-toast" id="toast-install-btn">Install Sekarang</button>
                <button class="btn-dismiss-toast" id="toast-dismiss-btn">Nanti</button>
            </div>
        </div>
    </div>

    <script>
    // ===== PWA Service Worker =====
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(reg => console.log('[PWA] Service Worker terdaftar:', reg.scope))
                .catch(err => console.log('[PWA] Service Worker gagal:', err));
        });
    }

    // ===== PWA Install Prompt =====
    let deferredPrompt = null;
    const installBtn  = document.getElementById('pwa-install-btn');
    const pwaToast    = document.getElementById('pwa-toast');
    const toastInstall = document.getElementById('toast-install-btn');
    const toastDismiss = document.getElementById('toast-dismiss-btn');

    // Cek apakah sudah pernah dismiss toast
    const toastDismissed = sessionStorage.getItem('pwa-toast-dismissed');

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;

        // Tampilkan tombol install di navbar
        installBtn.classList.add('visible');

        // Tampilkan toast setelah 3 detik (hanya sekali per sesi)
        if (!toastDismissed) {
            setTimeout(() => {
                pwaToast.classList.add('show');
            }, 3000);
        }
    });

    // Fungsi install
    async function triggerInstall() {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        console.log('[PWA] User choice:', outcome);
        deferredPrompt = null;
        installBtn.classList.remove('visible');
        pwaToast.classList.remove('show');
    }

    // Tombol di navbar
    installBtn.addEventListener('click', triggerInstall);

    // Tombol di toast
    toastInstall.addEventListener('click', triggerInstall);
    toastDismiss.addEventListener('click', () => {
        pwaToast.classList.remove('show');
        sessionStorage.setItem('pwa-toast-dismissed', '1');
    });

    // Sembunyikan tombol jika sudah di-install
    window.addEventListener('appinstalled', () => {
        installBtn.classList.remove('visible');
        pwaToast.classList.remove('show');
        deferredPrompt = null;
        console.log('[PWA] Aplikasi berhasil diinstall!');
    });
    </script>
</body>
</html>
