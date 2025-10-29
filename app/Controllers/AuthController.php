<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\I18n\Time; // Added for timestamp comparison
use Config\Services; // Added for email service
use Throwable; // Import Throwable for exception handling

class AuthController extends BaseController
{
    protected $userModel;
    protected $helpers = ['form', 'url', 'text']; // Added text helper

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Menampilkan halaman login
     */
    public function login()
    {
        return view('auth/login');
    }

    /**
     * Memproses data dari form login
     */
    public function processLogin()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $user     = $this->userModel->where('email', $email)->first();

        if ($user && password_verify($password, $user->password_hash)) {
            // Regenerate session ID upon login for security
            session()->regenerate();

            $sessionData = [
                'user_id'    => $user->id,
                'username'   => $user->username,
                'email'      => $user->email,
                'is_premium' => (bool) $user->is_premium,
                'is_admin'   => (bool) $user->is_admin, // Tambahkan status admin
                'isLoggedIn' => true,
            ];
            session()->set($sessionData);

            // Arahkan admin ke rute admin, user biasa ke dashboard
            if ($user->is_admin) {
                 return redirect()->route('admin.dashboard')->with('success', 'Selamat datang kembali, Admin ' . $user->username);
            }

            return redirect()->route('dashboard')->with('success', 'Selamat datang kembali, ' . $user->username);
        }

        return redirect()->back()->withInput()->with('error', 'Email atau password salah.');
    }

    /**
     * Menampilkan halaman pendaftaran
     */
    public function register()
    {
        return view('auth/register');
    }

    /**
     * Memproses data dari form pendaftaran
     */
    public function processRegister()
    {
        // Added stricter username validation (alpha_numeric only)
        $rules = [
            'username'         => 'required|alpha_numeric|min_length[3]|max_length[30]|is_unique[users.username]',
            'email'            => 'required|valid_email|is_unique[users.email]',
            'password'         => 'required|min_length[8]',
            'password_confirm' => [ // Menggunakan array untuk pesan kustom
                'label' => 'Konfirmasi Password',
                'rules' => 'required|matches[password]',
                'errors' => [
                    'matches' => 'Konfirmasi password tidak cocok dengan password.',
                ],
            ],
        ];
        $errors = [ // Custom error messages
            'username' => [
                'alpha_numeric' => 'Username hanya boleh berisi huruf dan angka (tanpa spasi atau simbol lain).',
            ]
        ];

        if (!$this->validate($rules, $errors)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'username'   => $this->request->getPost('username'),
            'email'      => $this->request->getPost('email'),
            'password'   => $this->request->getPost('password'), // Kirim password mentah
            'is_premium' => 0,
            'is_admin'   => 0, // Default user baru bukan admin
            'balance'    => 0, // Default saldo awal
        ];

        try {
            if ($this->userModel->save($data)) {
                // (Optional) Kirim email verifikasi di sini jika diimplementasikan
                return redirect()->route('login')->with('success', 'Pendaftaran berhasil! Silakan login.');
            }

            // Jika save() return false tapi tidak ada error model, mungkin ada error lain
            log_message('error', 'Gagal menyimpan user baru tanpa error model yang jelas.');
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan sistem saat mendaftar. Silakan coba lagi.');

        } catch (Throwable $e) {
            // Tangkap exception database atau lainnya
            log_message('error', 'Exception saat registrasi: [' . $e->getCode() . '] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan database saat mendaftar. Silakan coba lagi.');
        }
    }

    /**
     * Menghapus session dan logout user
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }

    /**
     * Menampilkan halaman lupa password
     */
    public function forgotPassword()
    {
        // Buat view sederhana untuk form lupa password
        return view('auth/forgot_password');
    }

    /**
     * Memproses permintaan reset password dari form lupa password
     */
    public function processForgotPassword()
    {
        $rules = [
            'email' => 'required|valid_email|is_not_unique[users.email]',
        ];
        $errors = [
            'email' => [
                'is_not_unique' => 'Email yang Anda masukkan tidak terdaftar.',
            ]
        ];

        if (!$this->validate($rules, $errors)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $emailAddress = $this->request->getPost('email');
        $user = $this->userModel->where('email', $emailAddress)->first();

        // 1. Generate token reset password
        $token = bin2hex(random_bytes(32)); // Generate a secure random token
        $tokenHash = password_hash($token, PASSWORD_DEFAULT); // Hash the token for DB storage
        $expiryMinutes = 60; // Token valid for 60 minutes
        $expiresAt = date('Y-m-d H:i:s', time() + ($expiryMinutes * 60));

        // 2. Simpan hash token dan waktu expired ke database
        if (!$this->userModel->update($user->id, [
            'reset_token_hash'       => $tokenHash,
            'reset_token_expires_at' => $expiresAt
        ])) {
            log_message('error', 'Failed to save reset token for user ID: ' . $user->id);
            return redirect()->back()->withInput()->with('error', 'Gagal memproses permintaan reset. Coba lagi nanti.');
        }

        // 3. Kirim email
        // Menggunakan site_url() untuk memastikan base URL disertakan
        $resetLink = site_url(route_to('reset.password', $token)); // <-- Modifikasi di sini

        $email = Services::email();
        $email->setTo($user->email);
         // Ambil konfigurasi email dari .env atau config
        $fromEmail = env('email.fromEmail') ?: config('Email')->fromEmail ?: 'no-reply@example.com';
        $fromName = env('email.fromName') ?: config('Email')->fromName ?: 'Repo.ID';
        $email->setFrom($fromEmail, $fromName);
        $email->setSubject('Reset Password Akun Repo.ID Anda');

        $message = view('emails/reset_email', [
            'username'      => $user->username,
            'resetLink'     => $resetLink,
            'expiryMinutes' => $expiryMinutes
        ]);
        $email->setMessage($message);

        if ($email->send()) {
            log_message('info', 'Reset password email sent to: ' . $user->email);
            return redirect()->route('forgot.password')->with('success', 'Instruksi reset password telah dikirim ke email Anda.');
        } else {
            log_message('error', 'Failed to send reset password email to: ' . $user->email . ' Debug: ' . $email->printDebugger(['headers']));
            // Rollback token changes if email fails? Optional, depends on desired behavior.
            // $this->userModel->update($user->id, ['reset_token_hash' => null, 'reset_token_expires_at' => null]);
            return redirect()->back()->withInput()->with('error', 'Gagal mengirim email reset password. Pastikan konfigurasi email benar.');
        }
    }


    /**
     * Menampilkan form reset password (jika token valid)
     * @param string $token Raw token from URL
     */
    public function resetPassword($token)
    {
        // 1. Cari user berdasarkan hash dari token URL
        // (Perlu iterasi atau query khusus jika tidak ada index di token hash)
        // Cara lebih efisien: Query semua token hash, lalu loop & verify
        // Atau cara (kurang aman jika banyak token aktif): Simpan token asli sementara, cari token asli
        // Cara paling umum: Tambah kolom `reset_selector` (bagian non-rahasia token) dan index di situ.

        // --- Pendekatan Sederhana (Mungkin lambat jika banyak user) ---
        $userFound = null;
        $usersWithTokens = $this->userModel->where('reset_token_hash IS NOT NULL')->findAll();
        foreach ($usersWithTokens as $user) {
            // Pastikan hash di DB tidak null sebelum memverifikasi
            if ($user->reset_token_hash !== null && password_verify($token, $user->reset_token_hash)) {
                $userFound = $user;
                break;
            }
        }
        // --- End Pendekatan Sederhana ---

        // 2. Validasi token (ditemukan & belum expired)
        if (!$userFound || Time::now()->isAfter(Time::parse($userFound->reset_token_expires_at))) {
            log_message('warning', 'Invalid or expired reset token attempted: ' . $token);
            return redirect()->route('forgot.password')->with('error', 'Link reset password tidak valid atau sudah kedaluwarsa.');
        }

        // 3. Tampilkan view form reset password
        return view('auth/reset_password', ['token' => $token]);
    }

    /**
     * Memproses form reset password
     * @param string $token Raw token from URL
     */
    public function processResetPassword($token)
    {
         // 1. Validasi token lagi (sama seperti di show form)
         $userFound = null;
         $usersWithTokens = $this->userModel->where('reset_token_hash IS NOT NULL')->findAll();
         foreach ($usersWithTokens as $user) {
              // Pastikan hash di DB tidak null sebelum memverifikasi
             if ($user->reset_token_hash !== null && password_verify($token, $user->reset_token_hash)) {
                 $userFound = $user;
                 break;
             }
         }

        if (!$userFound || Time::now()->isAfter(Time::parse($userFound->reset_token_expires_at))) {
             log_message('warning', 'Invalid or expired reset token submitted for processing: ' . $token);
            return redirect()->route('forgot.password')->with('error', 'Link reset password tidak valid atau sudah kedaluwarsa.');
        }

        // 2. Validasi input password baru
        $rules = [
            'password'         => 'required|min_length[8]',
            'password_confirm' => [
                'label' => 'Konfirmasi Password Baru',
                'rules' => 'required|matches[password]',
                 'errors' => [
                    'matches' => 'Konfirmasi password tidak cocok dengan password baru.',
                ],
            ],
            // Include token check in rules if sent via hidden input
            // 'token' => 'required'
        ];

        if (!$this->validate($rules)) {
             // Redirect back to the reset form with the token
            return redirect()->route('reset.password', [$token])->withInput()->with('errors', $this->validator->getErrors());
        }

         // 3. Update password hash
         $newPasswordHash = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);

         // 4. Hapus/invalidate token reset dan simpan password baru
         if ($this->userModel->update($userFound->id, [
            'password_hash'          => $newPasswordHash,
            'reset_token_hash'       => null,
            'reset_token_expires_at' => null
         ])) {
             log_message('info', 'Password reset successfully for user ID: ' . $userFound->id);
              // (Optional) Login user automatically after reset?
             // session()->regenerate();
             // $this->setUserSession($userFound); // Buat helper function jika perlu
            return redirect()->route('login')->with('success', 'Password berhasil direset. Silakan login dengan password baru Anda.');
         } else {
             log_message('error', 'Failed to update password or clear reset token for user ID: ' . $userFound->id);
             return redirect()->route('reset.password', [$token])->withInput()->with('error', 'Gagal memperbarui password. Silakan coba lagi.');
         }
    }
}
