# Roadmap Pengembangan — SI-TASY (Sistem Tabungan Siswa)

> Tujuan project: mengelola tabungan siswa di sekolah dasar, di mana **setiap kelas dikelola oleh wali kelasnya masing-masing**.
>
> Dokumen ini adalah rencana kerja yang bisa dilanjutkan lintas sesi. Update checklist (`[ ]` → `[x]`) setiap menyelesaikan item, dan tambahkan catatan di bagian **Log Progres** di bagian bawah setiap kali berhenti di tengah fase.

## Status Saat Ini (per 2026-07-14)

- Stack: Laravel 12, PHP 8.5, MySQL, Vite + Tailwind v4.
- Role: `admin`, `guru`, `orang_tua` (kolom `role` di tabel `users`, dicek lewat `RoleMiddleware`).
- Sudah ada: auth login, CRUD kelas (admin only), CRUD siswa, catat transaksi setor/tarik, dashboard admin/guru, dashboard orang tua (view saldo & riwayat anak).
- ~~**Gap utama**: role `guru` bersifat global~~ → **selesai di Fase 1**: guru sekarang dibatasi ke kelas yang diampunya lewat Policy + scoping query.
- Belum ada: audit trail, edit/koreksi transaksi, laporan/export, dukungan 1 orang tua punya banyak anak (`User::siswa()` masih `hasOne`). Test otomatis sudah mulai ada (`WaliKelasAuthorizationTest`), tapi baru cover otorisasi Fase 1.

### Asumsi desain yang perlu dikonfirmasi/disesuaikan
- 1 kelas = 1 wali kelas aktif (nullable FK `kelas.wali_kelas_id` → `users.id`).
- 1 guru bisa jadi wali kelas untuk lebih dari satu kelas (misal beda tahun ajaran), tapi tidak ada batasan constraint di level DB untuk itu.
- Jika ternyata satu guru harus bisa pegang **beberapa kelas sekaligus di tahun ajaran yang sama**, perlu pivot table (`kelas_wali_kelas`) alih-alih kolom FK langsung — pertimbangkan ini sebelum mulai Fase 1 kalau kondisi sekolah seperti itu.

---

## Fase 1 — Model Wali Kelas & Otorisasi Berbasis Kelas (PRIORITAS TERTINGGI) — ✅ SELESAI (2026-07-14)

Fondasi untuk semua fase berikutnya. Tanpa ini, fitur lain akan butuh refactor ulang.

- [x] Migration: tambah kolom `wali_kelas_id` (nullable, `foreignId` → `users.id`, `onDelete('set null')`) di tabel `kelas`.
- [x] Update `Kelas` model: relasi `waliKelas()` → `belongsTo(User::class, 'wali_kelas_id')`.
- [x] Update `User` model: relasi `kelasDiampu()` → `hasMany(Kelas::class, 'wali_kelas_id')`, plus helper `isWaliKelasOf(Kelas $kelas): bool`.
- [x] Buat Policy: `SiswaPolicy`, `TransaksiPolicy`, `KelasPolicy` (auto-discovered, tidak perlu registrasi manual di Laravel 12).
  - Admin: akses semua.
  - Guru: hanya boleh `view`/`update`/`delete` siswa & transaksi yang `kelas_id`-nya termasuk kelas yang diampunya. `create` menerima parameter kelas/siswa target untuk validasi kepemilikan.
- [x] Terapkan scoping di controller:
  - `SiswaController@index/create/store/edit/update/destroy` — filter/validasi kelas berdasarkan kepemilikan guru yang login lewat `kelasScope()` + `$this->authorize(...)`.
  - `TransaksiController@index/create/store/show` — sama, termasuk validasi saat guru pindahkan siswa ke kelas lain.
  - `DashboardController@index` — jika guru, semua angka (total siswa, saldo, setor/tarik, transaksi terbaru) dihitung hanya dari kelas yang diampu.
- [x] Admin: field pilih wali kelas ditambahkan di form create/edit `kelas` (`resources/views/kelas/index.blade.php`), plus kolom "Wali Kelas" di tabel daftar kelas.
- [x] `DatabaseSeeder` di-update: 2 guru (`guru@tabungan.com`, `guru2@tabungan.com`), masing-masing wali kelas Kelas 1-A dan 1-B, supaya skenario lintas-kelas mudah dites.
- [x] **Test wajib**: `tests/Feature/WaliKelasAuthorizationTest.php` — 11 test membuktikan guru A tidak bisa lihat/ubah/hapus/buat siswa & transaksi milik kelas guru B (403), guru tidak bisa memindah siswa sendiri ke kelas guru lain, index ter-scope dengan benar, dan admin tetap bebas akses semua. Semua lulus. Juga diverifikasi manual lewat HTTP request sungguhan (login sebagai guru, akses langsung by-ID ke siswa kelas lain → 403).

**Catatan implementasi:**
- `phpunit.xml`: `DB_CONNECTION`/`DB_DATABASE` diaktifkan ke `sqlite`/`:memory:` supaya test tidak menimpa database dev asli.
- `tests/Feature/ExampleTest.php` (contoh bawaan Laravel) diperbaiki — sebelumnya expect `/` return 200, padahal app ini meng-redirect ke `/login` untuk guest.
## Fase 2 — Penyesuaian UI sesuai Peran — ✅ SELESAI (2026-07-14)

- [x] Dashboard guru: tampilkan hanya data kelas yang diampu (jumlah siswa, total saldo, transaksi terbaru). *(sudah otomatis ter-scope sejak Fase 1 lewat `DashboardController`, diverifikasi ulang di Fase 2)*.
- [x] Form tambah siswa (guru login): dropdown kelas otomatis terkunci ke kelas miliknya. Kalau guru cuma punya 1 kelas, field diganti jadi teks read-only + hidden input (`siswa/create.blade.php`, `siswa/edit.blade.php`); kalau guru punya >1 kelas, dropdown tetap tampil tapi cuma berisi kelas miliknya. Admin tetap bebas pilih semua kelas.
- [x] Form catat transaksi (guru login): daftar siswa yang muncul hanya dari kelasnya *(sudah ter-scope sejak Fase 1 lewat `TransaksiController::kelasScope()`)*.
- [x] Halaman "Kelas Saya" (`GET /kelas-saya`, route `kelas.saya`, khusus role `guru`): menampilkan tiap kelas yang diampu — kartu ringkasan (jumlah siswa, total tabungan) + tabel siswa dengan saldo, tombol edit siswa dan catat transaksi langsung per siswa.
- [x] Sidebar (`layouts/app.blade.php`): admin melihat "Manajemen Kelas", guru melihat "Kelas Saya" — keduanya mutually exclusive sesuai role.
- [x] Bonus: form catat transaksi kini menerima `?siswa_id=` di query string supaya link dari "Kelas Saya" langsung memilih siswa yang dituju.

**Catatan implementasi:**
- Semua diverifikasi lewat HTTP request sungguhan sebagai guru & admin (bukan cuma test otomatis): halaman Kelas Saya guru A hanya menampilkan siswanya sendiri, admin mendapat 403 saat mengakses `/kelas-saya` (route memang khusus guru — admin sudah punya "Manajemen Kelas" & "Data Siswa" untuk kebutuhan yang sama).
- Test suite (`php artisan test`) tetap hijau, tidak ada regresi dari Fase 1.

## Fase 3 — Penyempurnaan Transaksi — ✅ SELESAI (2026-07-14), 1 item opsional ditunda

- [x] Mekanisme koreksi transaksi salah input — `POST /transaksi/{id}/void` (route `transaksi.void`) membuat entri pembalik (reversal) dengan alasan, **tidak** mengedit/menghapus record lama. Kolom baru `is_reversal` & `reversal_of_id` di tabel `transaksis` menautkan entri koreksi ke aslinya, sehingga jejak audit lengkap.
  - `TransaksiPolicy::void()` — hanya wali kelas siswa terkait (atau admin) yang boleh membatalkan, tidak bisa membatalkan entri koreksi itu sendiri, dan tidak bisa membatalkan transaksi yang sudah pernah dibatalkan (idempoten).
  - Kalau saldo siswa sudah "terpakai" sejak transaksi asli dicatat (misal setoran lalu ditarik sebagian besar), pembatalan setoran akan ditolak dengan pesan jelas alih-alih memaksa saldo jadi negatif.
  - View `transaksi/show.blade.php`: tombol "Batalkan/Koreksi" (muncul kalau berwenang) + banner status ("ini entri koreksi dari..." / "sudah dibatalkan, lihat..."). View `transaksi/index.blade.php`: kolom badge status (Sah/Koreksi/Dibatalkan).
- [x] Cetak kwitansi — ternyata **sudah lengkap sejak awal**, hanya salah tempat saat dicek di Fase 1/2: CSS ada di `public/css/app.css` (bukan `resources/css/app.css` yang cuma stub Tailwind kosong). Sudah termasuk `@media print` yang menyembunyikan sidebar/navbar/tombol dan merapikan `.kwitansi-box` untuk cetak.
- [x] Row locking (`lockForUpdate()`) diterapkan di `TransaksiController@store` dan `@void` — saldo siswa dikunci & divalidasi ulang di dalam DB transaction sebelum increment/decrement, menutup celah race condition check-then-act pada penarikan/pembatalan bersamaan. Exception khusus `InsufficientSaldoException` dipakai untuk rollback bersih + pesan error yang sama seperti sebelumnya.
- [ ] **Ditunda**: batas nominal/jumlah transaksi harian per siswa — masih opsional dan butuh kebijakan konkret dari sekolah (berapa rupiah/berapa kali per hari?). Belum ada angka yang diberikan user, jadi belum diimplementasikan supaya tidak mengarang aturan bisnis. Tanyakan ke user kalau mau diaktifkan.

**Catatan implementasi:**
- Test baru: `tests/Feature/TransaksiVoidTest.php` (6 skenario: reversal & saldo balik normal, tidak bisa dibatalkan 2x, tidak bisa membatalkan entri koreksi, guru lain tidak bisa membatalkan, pembatalan ditolak kalau saldo sudah terpakai, penarikan melebihi saldo ditolak). Semua lulus + diverifikasi manual via HTTP end-to-end (buat transaksi → saldo naik → batalkan → saldo balik → banner & badge tampil benar → guru B dapat 403 saat coba batalkan transaksi guru A).
- Total test suite sekarang 19 test, semua hijau.

## Fase 4 — Fitur Orang Tua

- [ ] Ubah relasi `User::siswa()` dari `hasOne` ke `hasMany` (`siswas()`), sesuaikan `DashboardController@siswaIndex` untuk multi-anak (pilih anak jika lebih dari satu, atau tampilkan gabungan).
- [ ] Migration data existing: pastikan tidak ada asumsi "1 user = 1 siswa" yang tersisa di kode (cari `->siswa` di seluruh codebase).
- [ ] Riwayat transaksi & saldo per anak untuk orang tua yang punya lebih dari 1 anak di sekolah yang sama.
- [ ] (Opsional) Notifikasi email/WA setiap ada setoran/penarikan.

## Fase 5 — Laporan

- [ ] Rekap bulanan/tahunan per kelas (untuk wali kelas) dan per sekolah (untuk admin/kepala sekolah).
- [ ] Export Excel/PDF (`maatwebsite/excel` atau `barryvdh/laravel-dompdf`).
- [ ] Grafik tren tabungan per kelas (bisa pakai Chart.js sederhana di Blade).

## Fase 6 — Kualitas & Kesiapan Produksi

- [ ] Lengkapi test otomatis (unit + feature), terutama otorisasi per-kelas dari Fase 1.
- [ ] Audit log: siapa mengubah apa dan kapan (tabel `activity_log` atau paket `spatie/laravel-activitylog`).
- [ ] Review keamanan: mass-assignment pada model, validasi upload file jika nanti ada foto siswa, rate limiting form login.
- [ ] Review performa query (N+1) terutama di dashboard dan halaman index dengan banyak `with()`.

---

## Log Progres

> Tambahkan entri baru di sini setiap sesi kerja berhenti, supaya sesi berikutnya tahu persis di mana melanjutkan.

- **2026-07-14** — Project awalnya gagal jalan karena database `tabungansiswa` belum ada. Sudah dibuat, migration & seeder dijalankan, aplikasi diverifikasi jalan (login page, root redirect, asset CSS semua 200 OK). Roadmap ini dibuat, belum ada fase yang mulai dikerjakan.
- **2026-07-14** — **Fase 1 selesai.** Model wali kelas, Policy, dan scoping otorisasi diterapkan di semua controller terkait (Siswa, Transaksi, Dashboard, Kelas). Test otomatis (11 skenario) + verifikasi manual via HTTP membuktikan guru tidak bisa lagi mengakses data kelas lain. Database dev di-refresh (`migrate:fresh --seed`) dengan 2 guru contoh masing-masing wali kelas 1 kelas. **Lanjut ke Fase 2** (penyesuaian UI: kunci dropdown kelas di form untuk guru, halaman "Kelas Saya", sembunyikan menu tidak relevan) di sesi berikutnya.
- **2026-07-14** — **Fase 2 selesai.** Dropdown kelas di form siswa terkunci untuk guru dengan 1 kelas, halaman "Kelas Saya" dibuat lengkap dengan ringkasan + daftar siswa & saldo, menu sidebar disesuaikan per role, dan form transaksi bisa preselect siswa lewat `?siswa_id=`. Semua diverifikasi manual via HTTP sebagai guru & admin, test suite tetap hijau. **Lanjut ke Fase 3** (penyempurnaan transaksi: mekanisme koreksi/reversal, cetak kwitansi, validasi race condition saldo) di sesi berikutnya.
- **2026-07-14** — **Fase 3 selesai** (kecuali 1 item opsional yang ditunda). Mekanisme koreksi/pembatalan transaksi (`transaksi.void`) dibangun lengkap dengan entri reversal, guard idempoten, dan proteksi saldo negatif. Row locking (`lockForUpdate`) diterapkan di `store()` dan `void()` untuk menutup race condition. Ternyata cetak kwitansi sudah siap sejak awal (CSS-nya ada di `public/css/app.css`, bukan `resources/css/app.css`). Batas transaksi harian **ditunda** — butuh keputusan kebijakan konkret dari user (nominal/frekuensi), tidak diimplementasikan dengan angka karangan. Test suite 19 test semua hijau + verification manual end-to-end via HTTP. **Lanjut ke Fase 4** (fitur orang tua: multi-anak per akun) di sesi berikutnya — atau putuskan dulu soal batas transaksi harian kalau mau diaktifkan.
- **2026-07-19** — **Fitur Transaksi Kolektif selesai.** Menambahkan route, view, dan logika controller untuk menginput setoran/penarikan tabungan secara massal per kelas dengan nominal variatif atau nominal default. Menggunakan database transaction & row locking untuk keamanan transaksi. Automated test suite (6 skenario) ditambahkan dan 30/30 tes berhasil lulus.
- **2026-07-19** — **Fitur Manajemen Guru selesai.** Menambahkan CRUD data guru untuk Administrator. Membuat controller, view index (split-screen), dan route yang dilindungi middleware admin. Sistem memproteksi penghapusan guru yang aktif menjabat wali kelas. Menambahkan unit/feature test dan seluruh 36 tes berhasil lulus.


