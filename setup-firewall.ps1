# Setup Firewall SI-TASY - Jalankan sebagai Administrator
# Klik kanan file ini -> "Run with PowerShell" -> klik "Yes" pada UAC

Write-Host "Membuka port 80 untuk akses jaringan lokal..." -ForegroundColor Cyan

# Hapus rule lama jika ada
netsh advfirewall firewall delete rule name="Laragon HTTP" 2>$null

# Tambah rule baru
netsh advfirewall firewall add rule name="Laragon HTTP" dir=in action=allow protocol=TCP localport=80
netsh advfirewall firewall add rule name="Laragon HTTP Out" dir=out action=allow protocol=TCP localport=80

Write-Host ""
Write-Host "SELESAI! Firewall sudah dibuka." -ForegroundColor Green
Write-Host ""
Write-Host "Guru bisa akses website di:" -ForegroundColor Yellow
Write-Host "  http://192.168.100.61" -ForegroundColor White
Write-Host ""
Write-Host "Pastikan:" -ForegroundColor Yellow
Write-Host "  1. Laragon Apache sudah Running" -ForegroundColor White
Write-Host "  2. Semua guru di jaringan WiFi/LAN yang sama" -ForegroundColor White
Write-Host "  3. Laptop ini jangan dimatikan" -ForegroundColor White
Write-Host ""
Read-Host "Tekan Enter untuk keluar"
