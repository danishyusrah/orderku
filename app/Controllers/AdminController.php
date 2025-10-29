<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\ProductModel; // Added ProductModel
use App\Models\ProductStockModel; // Added ProductStockModel
use App\Models\TransactionModel;
use App\Models\WithdrawalRequestModel;
use Config\Services; // Added for Pager
use CodeIgniter\Exceptions\PageNotFoundException; // Added for 404

class AdminController extends BaseController
{
    protected UserModel $userModel;
    protected ProductModel $productModel; // Added
    protected ProductStockModel $productStockModel; // Added
    protected TransactionModel $transactionModel;
    protected WithdrawalRequestModel $withdrawalRequestModel;
    protected $helpers = ['number', 'date', 'form', 'text']; // Added text helper

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->productModel = new ProductModel(); // Added
        $this->productStockModel = new ProductStockModel(); // Added
        $this->transactionModel = new TransactionModel();
        $this->withdrawalRequestModel = new WithdrawalRequestModel();
    }

    /**
     * Display the main admin dashboard
     */
    public function index()
    {
        // Add logic later to display summary data if needed
        $data = [
            'title' => 'Admin Dashboard',
            'userCount' => $this->userModel->countAllResults(),
            'pendingWithdrawals' => $this->withdrawalRequestModel->where('status', 'pending')->countAllResults(),
             'totalRevenue' => $this->transactionModel->selectSum('amount')->whereIn('status', ['success', 'settlement', 'capture'])->where('transaction_type', 'product')->first()->amount ?? 0, // Adjusted status check
             'totalPremiumRevenue' => $this->transactionModel->selectSum('amount')->whereIn('status', ['success', 'settlement', 'capture'])->where('transaction_type', 'premium')->first()->amount ?? 0, // Adjusted status check
        ];
        return view('admin/dashboard', $data);
    }

    /**
     * Display list of users with search and pagination
     */
    public function users()
    {
        $search = $this->request->getGet('search');
        $perPage = 20;

        $userQuery = $this->userModel;

        if (!empty($search)) {
            $userQuery->groupStart()
                ->like('username', $search)
                ->orLike('email', $search)
            ->groupEnd();
        }

        $users = $userQuery->orderBy('created_at', 'DESC')->paginate($perPage, 'users'); // Group 'users'
        $pager = $this->userModel->pager;

        // Append search query to pagination links
        $pager->setPath(route_to('admin.users'), 'users'); // Set base path and group
        if (!empty($search)) {
            $pager->setPath(route_to('admin.users') . '?search=' . urlencode($search), 'users');
        }


        $data = [
            'title' => 'Kelola Pengguna',
            'users' => $users,
            'pager' => $pager,
            'search' => $search, // Pass search query to view
        ];
        return view('admin/users', $data);
    }

    /**
     * Display list of transactions with search and pagination
     */
    public function transactions()
    {
        $search = $this->request->getGet('search');
        $perPage = 20;

        $transactionQuery = $this->transactionModel
                                  ->select('transactions.*, users.username as seller_username, products.product_name')
                                  ->join('users', 'users.id = transactions.user_id', 'left')
                                  ->join('products', 'products.id = transactions.product_id', 'left');

        if (!empty($search)) {
             $transactionQuery->groupStart()
                ->like('transactions.order_id', $search)
                ->orLike('transactions.buyer_name', $search)
                ->orLike('transactions.buyer_email', $search)
                ->orLike('products.product_name', $search) // Search by product name
                ->orLike('users.username', $search) // Search by seller username
             ->groupEnd();
        }

        $transactions = $transactionQuery->orderBy('transactions.created_at', 'DESC')
                                          ->paginate($perPage, 'transactions'); // Group 'transactions'

        $pager = $this->transactionModel->pager;

         // Append search query to pagination links
        $pager->setPath(route_to('admin.transactions'), 'transactions'); // Set base path and group
        if (!empty($search)) {
            $pager->setPath(route_to('admin.transactions') . '?search=' . urlencode($search), 'transactions');
        }


        $data = [
            'title'        => 'Riwayat Transaksi',
            'transactions' => $transactions,
            'pager'        => $pager,
            'search'       => $search, // Pass search query to view
        ];
        return view('admin/transactions', $data);
    }

    /**
     * Display detail of a specific transaction
     * @param int $id Transaction ID
     */
    public function transactionDetail(int $id)
    {
        $transaction = $this->transactionModel
            ->select('transactions.*, users.username as seller_username, products.product_name, products.order_type as product_order_type') // Select needed fields
            ->join('users', 'users.id = transactions.user_id', 'left')
            ->join('products', 'products.id = transactions.product_id', 'left')
            ->find($id);

        if (!$transaction) {
            throw PageNotFoundException::forPageNotFound('Transaksi tidak ditemukan.');
        }

        $stockData = null;
        // If it's a successful product transaction of type 'auto', get the delivered stock
        if (
            in_array($transaction->status, ['success', 'settlement', 'capture']) &&
            $transaction->transaction_type === 'product' &&
            $transaction->product_order_type === 'auto' // Check product type from joined data
        ) {
            $stockData = $this->productStockModel
                ->where('transaction_id', $transaction->id)
                ->where('is_used', true)
                ->first(); // Assuming only one stock item per transaction ID
        }


        $data = [
            'title'       => 'Detail Transaksi: ' . $transaction->order_id,
            'transaction' => $transaction,
            'stockData'   => $stockData, // Pass stock data to the view
        ];

        return view('admin/transaction_detail', $data);
    }


    /**
     * Display withdrawal requests with search and pagination
     */
    public function withdrawals()
    {
        $search = $this->request->getGet('search');
        $perPage = 20;

        $withdrawalQuery = $this->withdrawalRequestModel
                              ->select('withdrawal_requests.*, users.username')
                              ->join('users', 'users.id = withdrawal_requests.user_id'); // No 'left' needed if user must exist

        if (!empty($search)) {
             $withdrawalQuery->groupStart()
                ->like('users.username', $search)
                // Search within the JSON bank_details column
                ->orLike('withdrawal_requests.bank_details', '"bank_name":"%' . $search . '%"')
                ->orLike('withdrawal_requests.bank_details', '"account_number":"%' . $search . '%"')
                ->orLike('withdrawal_requests.bank_details', '"account_name":"%' . $search . '%"')
             ->groupEnd();
        }


        $withdrawals = $withdrawalQuery->orderBy('withdrawal_requests.created_at', 'DESC')
                                      ->paginate($perPage, 'withdrawals'); // Group 'withdrawals'

        $pager = $this->withdrawalRequestModel->pager;

        // Manually parse bank_details for each withdrawal after pagination
        if (!empty($withdrawals)) {
            foreach ($withdrawals as $wd) {
                if (isset($wd->bank_details)) {
                    $decoded = json_decode($wd->bank_details);
                    $wd->bank_name = $decoded->bank_name ?? null;
                    $wd->account_number = $decoded->account_number ?? null;
                    $wd->account_name = $decoded->account_name ?? null;
                } else {
                    // Set default null values if bank_details is missing or empty
                    $wd->bank_name = null;
                    $wd->account_number = null;
                    $wd->account_name = null;
                }
            }
        }


         // Append search query to pagination links
        $pager->setPath(route_to('admin.withdrawals'), 'withdrawals'); // Set base path and group
        if (!empty($search)) {
            $pager->setPath(route_to('admin.withdrawals') . '?search=' . urlencode($search), 'withdrawals');
        }

        $data = [
            'title'       => 'Permintaan Penarikan',
            'withdrawals' => $withdrawals, // Now includes parsed bank details
            'pager'       => $pager,
            'search'      => $search, // Pass search query to view
        ];
        return view('admin/withdrawals', $data);
    }


    /**
     * Update withdrawal request status
     * @param int $requestId
     * @param string $status 'approved' or 'rejected'
     */
    public function updateWithdrawalStatus(int $requestId, string $status)
    {
         // Basic CSRF Check (assuming filter is enabled)
         // if (! $this->request->is('post')) {
         //     return redirect()->back()->with('error', 'Metode tidak diizinkan.');
         // }
         // Note: CSRF filter handles the main validation

         if (!in_array($status, ['approved', 'rejected'])) {
            return redirect()->back()->with('error', 'Status tidak valid.');
        }

        $requestItem = $this->withdrawalRequestModel->find($requestId); // Renamed variable
        if (!$requestItem) {
            return redirect()->back()->with('error', 'Permintaan tidak ditemukan.');
        }

        // Prevent updating non-pending requests
        if ($requestItem->status !== 'pending') {
             return redirect()->back()->with('error', 'Hanya permintaan pending yang dapat diubah statusnya.');
        }


        // Start transaction if rejecting (to potentially return balance)
        $db = \Config\Database::connect(); // Get DB connection
        $db->transBegin(); // Start transaction


        // Update status and processed time
        $updateData = [
            'status' => $status,
            'processed_at' => date('Y-m-d H:i:s')
            // Add admin notes if needed in the future
            // 'admin_notes' => $this->request->getPost('notes') // Example
        ];
        $statusUpdateSuccess = $this->withdrawalRequestModel->update($requestId, $updateData);
        $balanceUpdateSuccess = true; // Assume success if not rejecting

        // If rejected, return the balance to the user
        if ($status === 'rejected') {
            // Ensure amount is float for calculation
            $amountToReturn = (float) $requestItem->amount;
            $balanceUpdateSuccess = $this->userModel->addBalance($requestItem->user_id, $amountToReturn);
            if (!$balanceUpdateSuccess) {
                 log_message('error', "Withdrawal request {$requestId} rejected, BUT FAILED to return amount {$amountToReturn} to user {$requestItem->user_id}. MANUAL ACTION NEEDED.");
            } else {
                 log_message('info', "Withdrawal request {$requestId} rejected. Amount {$amountToReturn} returned to user {$requestItem->user_id}.");
            }
        }

        // Check transaction status
        if ($db->transStatus() === false || !$statusUpdateSuccess || !$balanceUpdateSuccess) {
            $db->transRollback(); // Rollback on any failure
            log_message('error', 'Transaction failed during withdrawal status update for request ID: ' . $requestId . ' Target Status: ' . $status);
            return redirect()->back()->with('error', 'Gagal memperbarui status permintaan karena masalah database.');
        } else {
            $db->transCommit(); // Commit if all successful
            log_message('info', "Withdrawal request {$requestId} status updated to {$status}.");
            $statusText = ($status === 'approved') ? 'disetujui' : 'ditolak';
            return redirect()->route('admin.withdrawals')->with('success', "Permintaan penarikan berhasil {$statusText}.");
        }
    }

    /**
     * Toggle user's premium status.
     * @param int $userId
     */
    public function togglePremium(int $userId)
    {
         // Basic CSRF Check
        // if (! $this->request->is('post')) {
        //     return redirect()->back()->with('error', 'Metode tidak diizinkan.');
        // }

        $user = $this->userModel->find($userId);
        if (!$user) {
            return redirect()->back()->with('error', 'Pengguna tidak ditemukan.');
        }

        $newStatus = !$user->is_premium;
        if ($this->userModel->update($userId, ['is_premium' => $newStatus])) {
             $statusText = $newStatus ? 'diaktifkan' : 'dinonaktifkan';
            return redirect()->route('admin.users')->with('success', "Status premium untuk {$user->username} berhasil {$statusText}.");
        } else {
            return redirect()->back()->with('error', 'Gagal mengubah status premium pengguna.');
        }
    }

    /**
     * Toggle user's admin status.
     * @param int $userId
     */
    public function toggleAdmin(int $userId)
    {
         // Basic CSRF Check
        // if (! $this->request->is('post')) {
        //     return redirect()->back()->with('error', 'Metode tidak diizinkan.');
        // }

         // Prevent admin from removing their own admin status
         if ($userId === session()->get('user_id')) {
            return redirect()->back()->with('error', 'Anda tidak dapat mengubah status admin diri sendiri.');
         }

        $user = $this->userModel->find($userId);
        if (!$user) {
            return redirect()->back()->with('error', 'Pengguna tidak ditemukan.');
        }

        $newStatus = !$user->is_admin;
        if ($this->userModel->update($userId, ['is_admin' => $newStatus])) {
             $statusText = $newStatus ? 'diberikan' : 'dicabut';
            return redirect()->route('admin.users')->with('success', "Hak akses admin untuk {$user->username} berhasil {$statusText}.");
        } else {
            return redirect()->back()->with('error', 'Gagal mengubah status admin pengguna.');
        }
    }
}
