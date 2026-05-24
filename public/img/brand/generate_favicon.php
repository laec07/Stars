<?php
/**
 * Generador de favicon.ico multi-resolución desde favicon-source.png
 *
 * Uso:
 *   cd public/img/brand && php generate_favicon.php
 *
 * Produce:
 *   - favicon.ico (en public/, multi-resolución 16/32/48 con PNG embebido)
 *   - favicon-16.png, favicon-32.png, favicon-180.png en public/img/brand/
 *
 * Se ejecuta UNA sola vez (o cada vez que cambie el logo). No es runtime.
 *
 * Implementación: ICO con frames PNG-embedded (soportado por todos los
 * navegadores modernos: Chrome, Firefox, Safari, Edge, Opera).
 */

if (!extension_loaded('gd')) {
    fwrite(STDERR, "ERROR: extensión GD no disponible.\n");
    exit(1);
}

$sourcePath = __DIR__ . '/favicon-source.png';
if (!file_exists($sourcePath)) {
    fwrite(STDERR, "ERROR: no se encuentra $sourcePath\n");
    exit(1);
}

$publicDir = dirname(dirname(__DIR__));   // .../public
$brandDir  = __DIR__;                     // .../public/img/brand

// --------------------------------------------------------------
// 1. Cargar y normalizar la imagen fuente
// --------------------------------------------------------------
$src = @imagecreatefrompng($sourcePath);
if (!$src) {
    fwrite(STDERR, "ERROR: no se pudo leer la imagen PNG fuente.\n");
    exit(1);
}
imagealphablending($src, false);
imagesavealpha($src, true);

$srcW = imagesx($src);
$srcH = imagesy($src);
echo "Fuente: {$srcW}×{$srcH} px → $sourcePath\n";

// --------------------------------------------------------------
// 2. Generar variantes redimensionadas con transparencia preservada
// --------------------------------------------------------------
function resize($src, $size) {
    $srcW = imagesx($src);
    $srcH = imagesy($src);
    // Lienzo cuadrado size×size con fondo transparente
    $dst = imagecreatetruecolor($size, $size);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, $size, $size, $transparent);

    // Mantener aspect ratio; centrar
    $ratio = min($size / $srcW, $size / $srcH);
    $newW  = (int) round($srcW * $ratio);
    $newH  = (int) round($srcH * $ratio);
    $offX  = (int) (($size - $newW) / 2);
    $offY  = (int) (($size - $newH) / 2);

    imagealphablending($dst, true);
    imagecopyresampled($dst, $src, $offX, $offY, 0, 0, $newW, $newH, $srcW, $srcH);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    return $dst;
}

$sizes = [16, 32, 48, 180];   // 180 = apple-touch-icon
$images = [];
foreach ($sizes as $size) {
    $images[$size] = resize($src, $size);
    $outPng = $brandDir . "/favicon-{$size}.png";
    imagepng($images[$size], $outPng, 9);
    echo "Generado: $outPng\n";
}

// --------------------------------------------------------------
// 3. Componer favicon.ico (formato multi-frame con PNG embebido)
// --------------------------------------------------------------
// Solo incluir 16, 32 y 48 en el ICO. 180 va aparte como apple-touch-icon.
$icoSizes = [16, 32, 48];
$frames = [];
foreach ($icoSizes as $size) {
    ob_start();
    imagepng($images[$size], null, 9);
    $pngData = ob_get_clean();
    $frames[] = ['size' => $size, 'data' => $pngData];
}

// Estructura ICO:
//   ICONDIR (6 bytes):
//     0  WORD  Reserved (=0)
//     2  WORD  Type (1 = .ICO)
//     4  WORD  Count (# imágenes)
//   ICONDIRENTRY × N (16 bytes c/u):
//     0  BYTE  Width (0 = 256)
//     1  BYTE  Height (0 = 256)
//     2  BYTE  ColorCount (0 si >=8-bit)
//     3  BYTE  Reserved (=0)
//     4  WORD  Planes
//     6  WORD  BitCount
//     8  DWORD ImageSize
//    12  DWORD ImageOffset (desde inicio archivo)
//   ImageData × N

$header = pack('vvv', 0, 1, count($frames));    // ICONDIR
$entries = '';
$imageData = '';

// El offset del primer frame empieza después de header + todas las entradas
$dataOffset = 6 + (count($frames) * 16);

foreach ($frames as $frame) {
    $size = $frame['size'];
    $data = $frame['data'];
    $imgSize = strlen($data);

    // Width/Height = 0 si es 256
    $w = ($size === 256) ? 0 : $size;
    $h = ($size === 256) ? 0 : $size;

    $entries .= pack(
        'CCCCvvVV',
        $w,           // BYTE width
        $h,           // BYTE height
        0,            // BYTE color count
        0,            // BYTE reserved
        1,            // WORD planes
        32,           // WORD bit count (32 = RGBA)
        $imgSize,     // DWORD image size
        $dataOffset   // DWORD image offset
    );
    $imageData .= $data;
    $dataOffset += $imgSize;
}

$ico = $header . $entries . $imageData;
$icoPath = $publicDir . '/favicon.ico';
file_put_contents($icoPath, $ico);
echo "Generado: $icoPath (" . number_format(strlen($ico)) . " bytes, " . count($frames) . " resoluciones)\n";

// --------------------------------------------------------------
// 4. Limpieza
// --------------------------------------------------------------
foreach ($images as $img) imagedestroy($img);
imagedestroy($src);

echo "\n✓ Listo. Recuerda registrar los favicons en layouts/app.blade.php:\n";
echo "  <link rel=\"icon\" href=\"/favicon.ico\" sizes=\"any\">\n";
echo "  <link rel=\"icon\" type=\"image/png\" sizes=\"32x32\" href=\"/img/brand/favicon-32.png\">\n";
echo "  <link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"/img/brand/favicon-180.png\">\n";
