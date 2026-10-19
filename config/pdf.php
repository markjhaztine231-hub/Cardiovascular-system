<?php

return [
    /*
     * The default driver to use.
     * 'dompdf'      — pure PHP, zero-dependency (recommended for XAMPP / shared hosting)
     * 'browsershot' — requires Chromium / Puppeteer
     *
     * NOTE: spatie/laravel-pdf uses Browsershot internally; this key is read by
     * the application's PdfController to decide which renderer to invoke.
     */
    'driver' => env('PDF_DRIVER', 'dompdf'),

    /*
     * The default paper format. Can be overridden per-PDF.
     */
    'format' => 'letter',

    /*
     * Dompdf options — used when driver is 'dompdf'
     */
    'dompdf' => [
        'options' => [
            'defaultFont' => 'Arial',
            'enable_remote' => false,
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'enable_css_float' => true,
            'enable_html5_parser' => true,
        ],
    ],
];
