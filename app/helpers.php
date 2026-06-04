<?php

if (!function_exists('mask_sensitive')) {
    /**
     * Mask sensitive data (e.g., NIK, NPWP, bank account).
     * Shows only the last 4 characters, replaces the rest with asterisks.
     */
    function mask_sensitive(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $length = strlen($value);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        $visible = substr($value, -4);
        $masked = str_repeat('*', $length - 4);

        return $masked . $visible;
    }
}
