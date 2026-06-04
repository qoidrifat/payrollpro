<?php

/*
 * ─────────────────────────────────────────────────────────────────────
 * NOTE: DOMPDF_ENABLE_IMAGICK is defined in AppServiceProvider::register()
 * (not here) because define() in a config file is skipped when the
 * Laravel config cache is active. See app/Providers/AppServiceProvider.php.
 * ─────────────────────────────────────────────────────────────────────
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    |
    | Set some default values. It is possible to add all defines that can be set
    | in dompdf_config.inc.php. You can also override the entire config file.
    |
    */
    'show_warnings' => false,

    'public_path' => null,

    'convert_entities' => true,

    /*
    |--------------------------------------------------------------------------
    | Custom Defines
    |--------------------------------------------------------------------------
    |
    | Note: barryvdh/laravel-dompdf converts these to Dompdf Options (not PHP
    | constants), so DOMPDF_ENABLE_IMAGICK here is NOT effective on its own.
    | The define() call above is the actual fix.
    |
    */
    'defines' => [
        'DOMPDF_ENABLE_IMAGICK' => false,
    ],

    'options' => [
        'font_dir' => storage_path('fonts'),
        'font_cache' => storage_path('fonts'),
        'temp_dir' => sys_get_temp_dir(),
        'chroot' => realpath(base_path()),

        'allowed_protocols' => [
            'data://' => ['rules' => []],
            'file://' => ['rules' => []],
            'http://' => ['rules' => []],
            'https' => ['rules' => []],
        ],

        'artifactPathValidation' => null,
        'log_output_file' => null,
        'enable_font_subsetting' => false,
        'pdf_backend' => 'CPDF',
        'default_media_type' => 'screen',
        'default_paper_size' => 'a4',
        'default_paper_orientation' => 'portrait',
        'default_font' => 'serif',
        'dpi' => 96,
        'enable_php' => false,
        'enable_javascript' => true,
        'enable_remote' => true,
        'allowed_remote_hosts' => null,
        'font_height_ratio' => 1.1,
        'enable_html5_parser' => true,
    ],

];
