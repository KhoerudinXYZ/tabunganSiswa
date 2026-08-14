<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" style="width: 80px; height: 80px; margin-bottom: 15px; object-fit: contain;">
                <h1 class="auth-title" style="font-size: 22px; margin-bottom: 5px;">Tabungan Siswa</h1>
                <h2 style="font-size: 18px; margin-bottom: 10px; color: var(--text);">SDN 4 Rambatan Kulon</h2>
                <p class="auth-subtitle">Sistem Informasi Tabungan Siswa Sekolah Dasar</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="email" class="form-label">Alamat Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Masukkan email Anda" value="{{ old('email') }}" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Kata Sandi</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <div class="form-group d-flex justify-between align-center">
                    <label class="form-checkbox">
                        <input type="checkbox" name="remember">
                        <span>Ingat Saya</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Masuk ke Sistem</button>
            </form>
        </div>
    </div>
</body>
</html>
