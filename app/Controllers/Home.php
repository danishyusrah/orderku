<?php

namespace App\Controllers;

// Import RedirectResponse
use CodeIgniter\HTTP\RedirectResponse;

class Home extends BaseController
{
    /**
     * Menampilkan halaman CodeIgniter default (sebelumnya).
     * Anda bisa menghapus metode ini jika tidak lagi diperlukan.
     */
    public function index(): string
    {
        // Jika Anda masih ingin halaman welcome_message bisa diakses,
        // Anda bisa membuat rute baru untuknya atau membiarkan ini.
        // Jika tidak, hapus saja metode ini.
        return view('welcome_message');
    }

    /**
     * Metode baru untuk menampilkan landing page.
     * Pastikan file view 'landing_page.php' ada di app/Views/.
     *
     * @return string|RedirectResponse
     */
    // --- PERUBAHAN DI SINI: Ubah return type ---
    public function landing(): string|RedirectResponse
    // --- AKHIR PERUBAHAN ---
    {
        // Periksa apakah user sudah login, jika ya, arahkan ke dashboard
        if (session()->get('isLoggedIn')) {
            // Arahkan admin ke rute admin, user biasa ke dashboard
            if (session()->get('is_admin')) {
                 return redirect()->route('admin.dashboard'); // Mengembalikan RedirectResponse
            }
            return redirect()->route('dashboard'); // Mengembalikan RedirectResponse
        }

        // Jika belum login, tampilkan landing page
        // Pastikan Anda telah menyimpan kode HTML landing page sebagai 'app/Views/landing_page.php'
        return view('landing_page'); // Mengembalikan string (hasil render view)
    }
}

