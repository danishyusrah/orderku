<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filter for admin users.
 */
class AdminFilter implements FilterInterface
{
    /**
     * Checks if the user is logged in AND is an admin.
     * If not, redirects to the login page or dashboard.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Check if logged in first (using AuthFilter logic)
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('login')->with('error', 'Anda harus login untuk mengakses halaman ini.');
        }

        // Check if user is admin
        if (! session()->get('is_admin')) {
            // Logged in but not admin, redirect to user dashboard
             log_message('notice', 'User ' . session()->get('user_id') . ' attempted to access admin area.');
            return redirect()->to('dashboard')->with('error', 'Anda tidak memiliki hak akses admin.');
        }

        // User is logged in and is an admin, proceed.
        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed after the request
    }
}
