<?php
/**
 * Gerador de ícones PWA — execute UMA VEZ acessando /generate-icons.php pelo browser.
 * Gera: public/assets/icons/icon-192.png e icon-512.png
 * Após gerar, apague este arquivo do servidor de produção.
 */

if (!extension_loaded('gd')) {
    die('Extensão GD não disponível. Ative no php.ini: extension=gd');
}

$iconsDir = __DIR__ . '/assets/icons';
if (!is_dir($iconsDir)) {
    mkdir($iconsDir, 0755, true);
}

$sizes = [192, 512];
$generated = [];

foreach ($sizes as $size) {
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);

    // Fundo azul principal
    $blue = imagecolorallocate($img, 37, 99, 235);   // #2563eb
    imagefilledrectangle($img, 0, 0, $size, $size, $blue);

    // Círculo branco de fundo do ícone (80% do tamanho)
    $white  = imagecolorallocate($img, 255, 255, 255);
    $margin = (int) ($size * 0.10);
    imagefilledellipse($img, (int)($size / 2), (int)($size / 2), $size - $margin * 2, $size - $margin * 2, $white);

    // Cifão ($) centralizado usando fonte embutida
    $blue2  = imagecolorallocate($img, 37, 99, 235);
    $fontSize = (int) ($size * 0.40);
    $cx = (int) ($size * 0.50);
    $cy = (int) ($size * 0.55);

    // Linha vertical do cifão
    $lineW = (int) ($size * 0.06);
    $lineH = (int) ($size * 0.55);
    imagefilledrectangle(
        $img,
        $cx - (int)($lineW / 2),
        $cy - (int)($lineH / 2),
        $cx + (int)($lineW / 2),
        $cy + (int)($lineH / 2),
        $blue2
    );

    // Arco superior
    $arcW = (int) ($size * 0.28);
    $arcH = (int) ($size * 0.20);
    imagearc($img, $cx, (int)($size * 0.37), $arcW, $arcH, 200, 360, $blue2);
    imagesetthickness($img, (int)($size * 0.06));
    imagearc($img, $cx, (int)($size * 0.37), $arcW, $arcH, 200, 360, $blue2);

    // Arco inferior
    imagearc($img, $cx, (int)($size * 0.63), $arcW, $arcH, 20, 180, $blue2);
    imagesetthickness($img, (int)($size * 0.06));
    imagearc($img, $cx, (int)($size * 0.63), $arcW, $arcH, 20, 180, $blue2);

    $path = $iconsDir . "/icon-{$size}.png";
    imagepng($img, $path);
    imagedestroy($img);
    $generated[] = "icon-{$size}.png";
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ícones gerados</title>
    <style>
        body { font-family: sans-serif; max-width: 500px; margin: 60px auto; text-align: center; }
        img  { margin: 12px; border: 1px solid #ddd; border-radius: 8px; }
        .ok  { color: #16a34a; font-size: 1.2rem; font-weight: bold; }
    </style>
</head>
<body>
    <p class="ok">✅ Ícones gerados com sucesso!</p>
    <?php foreach ($generated as $f): ?>
        <div>
            <img src="/assets/icons/<?= $f ?>" width="96" height="96" alt="<?= $f ?>">
            <p><?= $f ?></p>
        </div>
    <?php endforeach; ?>
    <p style="color:#dc2626; margin-top:32px;">
        ⚠️ Apague este arquivo do servidor de produção após usar.
    </p>
</body>
</html>
