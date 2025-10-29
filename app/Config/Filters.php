<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;
// Import filter kita
use App\Filters\AuthFilter;
use App\Filters\GuestFilter;
use App\Filters\AdminFilter; // Added

class Filters extends BaseFilters
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array<string, class-string|list<class-string>>
     *
     * [filter_name => classname]
     * or [filter_name => [classname1, classname2, ...]]
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,
        'auth'          => AuthFilter::class,   // Tambahkan ini
        'guest'         => GuestFilter::class,  // Tambahkan ini
        'admin'         => AdminFilter::class, // Added
    ];

    /**
     * List of special required filters.
     *
     * The filters listed here are special. They are applied before and after
     * other kinds of filters, and always applied even if a route does not exist.
     *
     * Filters set by default provide framework functionality. If removed,
     * those functions will no longer work.
     *
     * @see https://codeigniter.com/user_guide/incoming/filters.html#provided-filters
     *
     * @var array{before: list<string>, after: list<string>}
     */
    public array $required = [
        'before' => [
            'forcehttps', // Force Global Secure Requests
            'pagecache',  // Web Page Caching
        ],
        'after' => [
            'pagecache',   // Web Page Caching
            'performance', // Performance Metrics
            // 'toolbar',     // Debug Toolbar moved to $globals -> after
        ],
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array{
     * before: array<string, array{except: list<string>|string}>|list<string>,
     * after: array<string, array{except: list<string>|string}>|list<string>
     * }
     */
    public array $globals = [
        'before' => [
            // 'honeypot',
             // Aktifkan CSRF kecuali untuk webhook dan endpoint pembayaran AJAX
             'csrf' => [
                 'except' => [
                     'payment/notify',               // Midtrans Webhook
                     'payment/tripay/notify',        // Tripay Webhook
                     'payment/orderkuota/notify',    // Orderkuota/Zeppelin Webhook
                     'payment/pay',                  // AJAX Upgrade Premium
                     'payment/pay-product',          // AJAX Beli Produk
                     'payment/orderkuota/check_status' // AJAX Cek Status Orderkuota
                 ]
             ],
            // 'invalidchars',
        ],
        'after' => [
             'toolbar', // Pindahkan toolbar ke sini agar berjalan setelah filter lain
            // 'honeypot',
             'secureheaders', // Aktifkan Secure Headers
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'POST' => ['foo', 'bar']
     *
     * If you use this, you should disable auto-routing because auto-routing
     * permits any HTTP method to access a controller. Accessing the controller
     * with a method you don't expect could bypass the filter.
     *
     * @var array<string, list<string>>
     */
    public array $methods = [
         // Contoh: Terapkan filter tambahan hanya untuk metode POST
        // 'post' => ['throttle'], // Jika Anda mengimplementasikan filter throttle
    ];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     *
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [
        // Contoh penerapan filter 'auth' untuk grup rute tertentu ada di Routes.php
        // 'auth' => ['before' => ['dashboard/*', 'admin/*']], // Alternatif jika tidak didefinisikan di Routes.php
        // 'guest' => ['before' => ['login', 'daftar']], // Alternatif
        // 'admin' => ['before' => ['admin/*']], // Alternatif
    ];
}

