<?php

/**
 * Patches dompdf/dompdf's CPDF.php to respect the DOMPDF_ENABLE_IMAGICK constant.
 *
 * CPDF.php directly calls new \Imagick() when extension_loaded("imagick") is true,
 * without checking the DOMPDF_ENABLE_IMAGICK constant. On Windows, the Imagick C
 * library fails with RegistryKeyLookupFailed if ImageMagick is not properly installed.
 *
 * This script is intended to be run as a composer post-install / post-update hook.
 * Run: php bin/patch-cpdf.php
 */

$target = __DIR__ . '/../vendor/dompdf/dompdf/lib/Cpdf.php';

if (!file_exists($target)) {
    echo "[CPDF Patch] File not found: $target\n";
    exit(1);
}

$content = file_get_contents($target);

$search = '        elseif (extension_loaded("imagick")) {';
$replace = '        elseif (extension_loaded("imagick") && (!defined("DOMPDF_ENABLE_IMAGICK") || DOMPDF_ENABLE_IMAGICK)) {';

if (strpos($content, $replace) !== false) {
    echo "[CPDF Patch] Already patched — skipping.\n";
    exit(0);
}

if (strpos($content, $search) === false) {
    echo "[CPDF Patch] WARNING: Could not find the expected pattern in CPDF.php.\n";
    echo "  The file may have been updated by a newer version of dompdf.\n";
    echo "  Manual review needed at: $target\n";
    exit(1);
}

$content = str_replace($search, $replace, $content);
file_put_contents($target, $content);

echo "[CPDF Patch] Successfully patched CPDF.php to respect DOMPDF_ENABLE_IMAGICK.\n";
exit(0);
