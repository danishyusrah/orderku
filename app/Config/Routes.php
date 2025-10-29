<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Redirect root to login
$routes->get('/', static fn() => redirect()->route('login'));

// Authentication Routes (Guest Only)
$routes->group('', ['filter' => 'guest'], static function ($routes) {
    $routes->get('login', 'AuthController::login', ['as' => 'login']);
    $routes->post('login', 'AuthController::processLogin');
    $routes->get('daftar', 'AuthController::register', ['as' => 'register']);
    $routes->post('daftar', 'AuthController::processRegister');
    $routes->get('lupa-password', 'AuthController::forgotPassword', ['as' => 'forgot.password']);
    $routes->post('lupa-password', 'AuthController::processForgotPassword');
    $routes->get('reset-password/(:hash)', 'AuthController::resetPassword/$1', ['as' => 'reset.password']);
    $routes->post('reset-password/(:hash)', 'AuthController::processResetPassword/$1', ['as' => 'reset.password.process']);
});
$routes->get('logout', 'AuthController::logout', ['as' => 'logout']);

// User Dashboard Routes (Logged In Users)
$routes->group('dashboard', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'DashboardController::index', ['as' => 'dashboard']);

    // --- Product CRUD ---
    $routes->get('product/new', 'DashboardController::newProduct', ['as' => 'product.new']);
    $routes->post('product/create', 'DashboardController::createProduct', ['as' => 'product.create']);
    $routes->get('product/edit/(:num)', 'DashboardController::editProduct/$1', ['as' => 'product.edit']);
    $routes->post('product/update/(:num)', 'DashboardController::updateProduct/$1', ['as' => 'product.update']);
    $routes->post('product/delete/(:num)', 'DashboardController::deleteProduct/$1', ['as' => 'product.delete']);

    // --- Stock Management ---
    // Halaman utama kelola stok (bisa menampilkan daftar varian atau form stok non-varian)
    $routes->get('product/(:num)/stock', 'DashboardController::manageStock/$1', ['as' => 'product.stock.manage']);

    // Stok Produk NON-VARIAN
    $routes->post('product/(:num)/stock/add', 'DashboardController::addStock/$1', ['as' => 'product.stock.add']);
    $routes->post('product/(:num)/stock/delete/(:num)', 'DashboardController::deleteStock/$1/$2', ['as' => 'product.stock.delete']);

    // Stok Produk VARIAN (Item Unik)
    // Rute BARU untuk menampilkan halaman kelola item stok per varian
    $routes->get('product/(:num)/variant/(:num)/items', 'DashboardController::manageVariantStockItems/$1/$2', ['as' => 'product.variant.stock.items']);
    // Rute BARU untuk MENAMBAH item stok ke varian
    $routes->post('product/(:num)/variant/(:num)/items/add', 'DashboardController::addVariantStockItem/$1/$2', ['as' => 'product.variant.stock.add']);
    // Rute BARU untuk MENGHAPUS item stok dari varian
    $routes->post('product/(:num)/variant/(:num)/items/delete/(:num)', 'DashboardController::deleteVariantStockItem/$1/$2/$3', ['as' => 'product.variant.stock.delete']);

    // Rute Update Stok Varian (via AJAX dari halaman daftar varian - jika masih dipakai)
    // $routes->post('product/(:num)/variant/stock/update', 'DashboardController::updateVariantStock/$1', ['as' => 'product.variant.stock.update']); // Komentari jika tidak dipakai


    // --- Lainnya ---
    $routes->get('upgrade', 'DashboardController::upgradePage', ['as' => 'dashboard.upgrade']);

    // --- Pengaturan ---
    $routes->get('settings', 'DashboardController::profileSettings', ['as' => 'dashboard.settings']);
    $routes->post('settings/profile', 'DashboardController::updateProfile', ['as' => 'dashboard.profile.update']);
    $routes->post('settings/bank', 'DashboardController::updateBank', ['as' => 'dashboard.bank.update']);
    $routes->post('settings/midtrans', 'DashboardController::updateMidtransKeys', ['as' => 'dashboard.midtrans.update']);
    $routes->post('settings/tripay', 'DashboardController::updateTripayKeys', ['as' => 'dashboard.tripay.update']); // Tripay Keys
    $routes->post('settings/gateway', 'DashboardController::updateGatewayPreference', ['as' => 'dashboard.gateway.update']); // Gateway Preference
    $routes->post('settings/password', 'DashboardController::updatePassword', ['as' => 'dashboard.password.update']);

    $routes->get('withdraw', 'DashboardController::withdrawPage', ['as' => 'dashboard.withdraw']);
    $routes->post('withdraw', 'DashboardController::requestWithdrawal', ['as' => 'dashboard.withdraw.request']);

    $routes->get('transactions', 'DashboardController::transactions', ['as' => 'dashboard.transactions']);
});


// Payment API Routes (Webhook & AJAX Call)
$routes->group('payment', static function ($routes) {
    // --- AJAX Calls ---
    $routes->post('pay', 'PaymentController::payForPremium', ['filter' => 'auth']); // AJAX Bayar Premium
    $routes->post('pay-product', 'PaymentController::payForProduct'); // AJAX Bayar Produk
    $routes->post('orderkuota/check_status', 'PaymentController::checkOrderkuotaStatus'); // AJAX Cek Status Orderkuota

    // --- Webhooks ---
    $routes->post('notify', 'PaymentController::notificationHandler'); // Midtrans Webhook
    $routes->post('tripay/notify', 'PaymentController::tripayNotification'); // Tripay Webhook
    $routes->post('orderkuota/notify', 'PaymentController::zeppelinNotification'); // Orderkuota/Zeppelin Webhook
});


// Admin Panel Routes (Admin Only)
$routes->group('admin', ['filter' => 'admin'], static function ($routes) {
    $routes->get('/', 'AdminController::index', ['as' => 'admin.dashboard']);
    $routes->get('users', 'AdminController::users', ['as' => 'admin.users']);
    $routes->post('users/toggle-premium/(:num)', 'AdminController::togglePremium/$1', ['as' => 'admin.users.toggle_premium']);
    $routes->post('users/toggle-admin/(:num)', 'AdminController::toggleAdmin/$1', ['as' => 'admin.users.toggle_admin']);
    $routes->get('transactions', 'AdminController::transactions', ['as' => 'admin.transactions']);
    $routes->get('transactions/detail/(:num)', 'AdminController::transactionDetail/$1', ['as' => 'admin.transactions.detail']);
    $routes->get('withdrawals', 'AdminController::withdrawals', ['as' => 'admin.withdrawals']);
    $routes->post('withdrawals/approve/(:num)', 'AdminController::updateWithdrawalStatus/$1/approved', ['as' => 'admin.withdrawals.approve']);
    $routes->post('withdrawals/reject/(:num)', 'AdminController::updateWithdrawalStatus/$1/rejected', ['as' => 'admin.withdrawals.reject']);
});


// Public Profile Route (Wildcard) - Letakkan di bagian bawah setelah route spesifik
$routes->get('(:segment)', 'ProfileController::index/$1', [
    'as' => 'profile.public',
    // Pastikan constraint ini tidak menghalangi rute lain yang sudah didefinisikan di atas
    'constraints' => [
        // Regex ini mengecualikan segmen pertama yang sama dengan nama grup atau route spesifik di atas
        'segment' => '^(?!admin|dashboard|payment|login|daftar|logout|lupa-password|reset-password|assets|uploads|writable|vendor)[^/]+$'
    ]
]);

// Pastikan rute CodeIgniter default (jika diperlukan) ada di paling bawah atau dihapus jika tidak dipakai
// $routes->get('ci-default', 'Home::index');

