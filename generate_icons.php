<?php
// Generate PWA icons dari logo.png
$sourcePath = __DIR__ . '/public/images/logo.png';
$outputDir  = __DIR__ . '/public/images/icons';

if (!file_exists($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$sizes = [72, 96, 128, 144, 152, 192, 384, 512];

$source = imagecreatefrompng($sourcePath);
if (!$source) {
    // Coba sebagai JPEG
    $source = imagecreatefromjpeg($sourcePath);
}

if (!$source) {
    echo "Error: Tidak bisa membaca file sumber\n";
    exit(1);
}

$srcWidth  = imagesx($source);
$srcHeight = imagesy($source);

echo "Logo asli: {$srcWidth}x{$srcHeight}px\n";

foreach ($sizes as $size) {
    $icon = imagecreatetruecolor($size, $size);
    
    // Background warna tema (#0f172a = dark slate)
    $bg = imagecolorallocate($icon, 15, 23, 42);
    imagefill($icon, 0, 0, $bg);
    
    // Padding 12% dari ukuran untuk memberi ruang
    $padding   = (int)($size * 0.12);
    $imgSize   = $size - ($padding * 2);
    
    // Resampling logo ke ukuran dengan padding
    imagecopyresampled(
        $icon, $source,
        $padding, $padding,         // dst x, y
        0, 0,                       // src x, y
        $imgSize, $imgSize,         // dst w, h
        $srcWidth, $srcHeight       // src w, h
    );
    
    $outputPath = "{$outputDir}/icon-{$size}x{$size}.png";
    imagepng($icon, $outputPath, 9);
    imagedestroy($icon);
    
    echo "✓ Dibuat: icon-{$size}x{$size}.png\n";
}

imagedestroy($source);
echo "\nSemua ikon PWA berhasil dibuat!\n";
