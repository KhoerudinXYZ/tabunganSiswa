# 🌐 Panduan Setup Server Lokal Menggunakan XAMPP

Panduan ini berisi langkah-langkah memindahkan aplikasi Tabungan Siswa ke komputer sekolah yang menggunakan **XAMPP**, agar aplikasi dapat diakses oleh semua guru melalui jaringan WiFi/LAN sekolah.

> **Syarat Utama:** 
> 1. Komputer Server dan perangkat semua guru **HARUS terhubung ke jaringan WiFi atau kabel LAN yang sama**.
> 2. Pastikan **XAMPP dan PHP versi 8.2 ke atas** sudah terinstal di komputer sekolah.

---

## Langkah 1: Pindahkan Folder Aplikasi
Aplikasi Laravel harus dipindahkan ke dalam folder *root* web server XAMPP.

1. Buka folder tempat Anda menyimpan project ini.
2. *Copy* (salin) seluruh folder project tersebut.
3. *Paste* (tempel) ke dalam folder htdocs XAMPP: 
   👉 **`C:\xampp\htdocs\tabunganSiswa`**
4. Jangan lupa ekspor *database* Anda dari laptop lama (file `.sql`) dan impor (masukkan) ke phpMyAdmin di komputer sekolah.

---

## Langkah 2: Cek IP Address Komputer Sekolah
1. Hubungkan komputer sekolah ke WiFi/LAN.
2. Buka **Command Prompt (CMD)**.
3. Ketik perintah berikut lalu tekan Enter:
   ```bash
   ipconfig
   ```
4. Cari tulisan **"IPv4 Address"**.
5. Catat angka tersebut. Contoh: `192.168.1.50` (Angka ini yang akan digunakan guru-guru untuk mengakses dari HP/Laptop mereka).

---

## Langkah 3: Setup Virtual Host di XAMPP
Agar aplikasi bisa dibuka langsung melalui IP tanpa menampilkan daftar folder, kita atur Virtual Host.

1. Buka folder: `C:\xampp\apache\conf\extra\`
2. Buka file **`httpd-vhosts.conf`** menggunakan Notepad.
3. *Copy* kode berikut dan *paste* di baris paling bawah file tersebut (Ganti `192.168.1.50` dengan IP komputer Anda):

   ```apache
   <VirtualHost 192.168.1.50:80>
       DocumentRoot "C:/xampp/htdocs/tabunganSiswa/public"
       ServerName 192.168.1.50
       <Directory "C:/xampp/htdocs/tabunganSiswa/public">
           Options Indexes FollowSymLinks MultiViews
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
4. Simpan file (`Ctrl + S`).

---

## Langkah 4: Update File `.env`
1. Buka folder `C:\xampp\htdocs\tabunganSiswa\`
2. Buka file `.env` menggunakan Notepad.
3. Ubah bagian `APP_URL` menjadi alamat IP yang baru:
   ```env
   APP_URL=http://192.168.1.50
   ```
4. Ubah kredensial *database* jika *username* dan *password* XAMPP di komputer sekolah berbeda (Secara default XAMPP menggunakan: `DB_USERNAME=root`, dan `DB_PASSWORD` dibiarkan kosong).
5. Simpan file tersebut.

---

## Langkah 5: Buka Windows Firewall
Secara *default*, Windows akan memblokir perangkat lain yang mencoba mengakses server lokal kita. Kita harus membuka gerbangnya.

1. Tekan tombol `Windows + X` di keyboard.
2. Pilih **Windows PowerShell (Admin)** atau **Terminal (Admin)**.
3. Klik **Yes** saat muncul popup UAC.
4. *Copy* dan *Paste* perintah berikut lalu tekan Enter:
   ```powershell
   netsh advfirewall firewall add rule name="XAMPP HTTP" dir=in action=allow protocol=TCP localport=80
   ```
5. Muncul tulisan **"Ok."** berarti firewall sukses dibuka.

---

## Langkah 6: Jalankan XAMPP & Uji Coba
1. Buka aplikasi **XAMPP Control Panel**.
2. Klik tombol **Start** pada modul **Apache** dan **MySQL**.
3. Pastikan tidak ada pesan merah/error di konsol XAMPP dan tulisan Apache di blok warna hijau.

### Cara Akses untuk Semua Guru:
Minta semua guru untuk:
1. Menghubungkan HP atau Laptop ke WiFi sekolah yang sama dengan komputer server.
2. Buka Browser (Chrome, Safari, dll).
3. Ketik IP komputer server:
   👉 **`http://192.168.1.50`** *(Ganti dengan IP asli)*.

---

**Tips Tambahan Pembersihan Cache:**
Jika setelah dipindahkan, tampilan web error atau berantakan, buka Terminal/CMD di dalam folder `C:\xampp\htdocs\tabunganSiswa` lalu jalankan perintah:
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```
