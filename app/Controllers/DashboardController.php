<?php

namespace App\Controllers;

// Import class yang diperlukan
use App\Controllers\BaseController; // Pastikan BaseController ada atau sesuaikan namespace jika perlu
use App\Models\ProductModel;
use App\Models\UserModel;
use App\Models\ProductStockModel;
use App\Models\ProductVariantModel;
use App\Models\TransactionModel;
use App\Models\WithdrawalRequestModel;
use Config\Services;
use CodeIgniter\I18n\Time;
use CodeIgniter\API\ResponseTrait; // Diperlukan untuk response AJAX

class DashboardController extends BaseController
{
    use ResponseTrait; // Gunakan trait untuk response API/AJAX

    // Deklarasi properti model
    protected ProductModel $productModel;
    protected UserModel $userModel;
    protected ProductStockModel $productStockModel;
    protected ProductVariantModel $productVariantModel;
    protected TransactionModel $transactionModel;
    protected WithdrawalRequestModel $withdrawalRequestModel;
    protected int $currentUserId; // ID user yang sedang login
    protected $helpers = ['form', 'url', 'filesystem', 'number', 'text']; // Helper yang digunakan

    public function __construct()
    {
        // Inisialisasi model
        $this->productModel = new ProductModel();
        $this->userModel = new UserModel();
        $this->productStockModel = new ProductStockModel();
        $this->productVariantModel = new ProductVariantModel();
        $this->transactionModel = new TransactionModel();
        $this->withdrawalRequestModel = new WithdrawalRequestModel();

        // Ambil user ID dari session
        $this->currentUserId = session()->get('user_id');
        if (!$this->currentUserId) {
             log_message('error', 'User ID not found in session for DashboardController.');
        }
    }

    // Menampilkan halaman utama dashboard pengguna
    public function index()
    {
        $search = $this->request->getGet('search'); // Ambil query pencarian
        $perPage = 10; // Jumlah item per halaman

        $productQuery = $this->productModel
            ->where('user_id', $this->currentUserId);

        // Filter berdasarkan pencarian jika ada
        if (!empty($search)) {
            $productQuery->groupStart()
                ->like('product_name', $search)
                ->orLike('description', $search)
            ->groupEnd();
        }

        // Ambil data produk dengan paginasi
        $products = $productQuery->orderBy('created_at', 'DESC')
                                 ->paginate($perPage, 'products'); // 'products' adalah nama grup paginasi

        $pager = $this->productModel->pager;
        $pager->setPath(route_to('dashboard'), 'products'); // Set base path dan grup
        // Tambahkan query pencarian ke link paginasi jika ada
        if (!empty($search)) {
            $pager->setPath(route_to('dashboard') . '?search=' . urlencode($search), 'products');
        }

        $totalAvailableStock = 0; // Inisialisasi total stok
        // Proses data produk untuk view
        foreach ($products as $product) {
            // Hitung stok jika produk tipe 'auto'
            if ($product->order_type === 'auto') {
                 if ($product->has_variants) {
                    // Hitung total stok dari semua varian aktif
                    $totalStockObject = $this->productVariantModel
                        ->selectSum('stock', 'total_stock')
                        ->where('product_id', $product->id)
                        ->where('is_active', true)
                        ->first();
                    $stockCount = $totalStockObject->total_stock ?? 0;
                } else {
                    // Hitung stok untuk produk non-varian
                    $stockCount = $this->productStockModel->getAvailableStockCountForNonVariant($product->id);
                }
                $product->available_stock = $stockCount;
                $totalAvailableStock += $stockCount;
            } else {
                $product->available_stock = null; // Produk manual tidak menampilkan stok
            }
            // Siapkan URL ikon atau placeholder
            $product->icon_url = $product->icon_filename
                ? base_url('uploads/product_icons/' . $product->icon_filename)
                : 'https://placehold.co/40x40/374151/9ca3af?text=' . strtoupper(substr(esc($product->product_name), 0, 1)); // Placeholder
        }

        // Data yang dikirim ke view
        $data = [
            'title'    => 'Kontrol Panel',
            'products' => $products,
            'pager'    => $pager,
            'search'   => $search,
            'user'     => $this->userModel->find($this->currentUserId),
            'currentPage' => $pager->getCurrentPage('products'),
            'perPage'     => $perPage,
            'productCount' => $pager->getTotal('products'),
            'totalAvailableStock' => $totalAvailableStock,
        ];

        return view('dashboard/index', $data);
    }

    // Menampilkan form tambah produk baru
    public function newProduct()
    {
        $data = [
            'title'   => 'Tambah Produk Baru',
            'user'    => $this->userModel->find($this->currentUserId),
            'action'  => route_to('product.create'), // URL action form
            'product' => null, // Tidak ada data produk awal
            'variants' => [], // Array kosong untuk template form varian
        ];
        return view('dashboard/product_form', $data);
    }

    // Memproses pembuatan produk baru
    public function createProduct()
    {
        // Ambil data dari POST request
        $data = $this->request->getPost([
            'product_name', 'description', 'price', 'order_type', 'target_url', 'is_active', 'has_variants', 'variants'
        ]);
        $iconFile = $this->request->getFile('product_icon');

        // Aturan validasi
        $rules = [
            'product_name' => 'required|max_length[255]',
            'order_type'   => 'required|in_list[manual,auto]',
            'price'        => 'permit_empty|numeric|greater_than_equal_to[0]',
            'product_icon' => 'permit_empty|uploaded[product_icon]|max_size[product_icon,1024]|is_image[product_icon]|mime_in[product_icon,image/jpg,image/jpeg,image/png,image/gif,image/webp]',
        ];
        // Pesan error kustom untuk ikon
        $errors = [
            'product_icon' => [
                'uploaded' => 'Anda harus memilih file ikon.',
                'max_size' => 'Ukuran file ikon maksimal 1MB.',
                'is_image' => 'File yang diupload harus berupa gambar.',
                'mime_in'  => 'Format gambar yang didukung: JPG, JPEG, PNG, GIF, WebP.'
            ]
        ];

        // Dapatkan data user yang sedang login
        $user = $this->userModel->find($this->currentUserId);
        if (!$user) {
             log_message('error', 'User not found in createProduct. User ID: ' . $this->currentUserId);
             return redirect()->route('logout'); // Redirect ke logout jika user tidak ditemukan
        }

        $isAuto = $data['order_type'] === 'auto'; // Cek tipe order
        $hasVariants = $isAuto && ($data['has_variants'] ?? 0) == 1; // Cek apakah menggunakan varian

        // Validasi tambahan: Fitur 'auto' hanya untuk premium
        if ($isAuto && !$user->is_premium) {
            return redirect()->back()->withInput()->with('error', 'Fitur "Otomatis" hanya untuk user Premium. Silakan Upgrade.');
        }

        // Validasi tambahan: Target URL wajib untuk 'manual'
        if (!$isAuto) {
             if (empty(trim((string) $this->request->getPost('target_url')))) {
                 return redirect()->back()->withInput()->with('errors', ['target_url' => 'Target URL (Link Whatsapp) wajib diisi untuk produk manual.']);
             }
             if (! filter_var($this->request->getPost('target_url'), FILTER_VALIDATE_URL)) {
                 return redirect()->back()->withInput()->with('errors', ['target_url' => 'Target URL harus berupa link yang valid.']);
             }
             $data['target_url'] = $this->request->getPost('target_url');
        } else {
             $data['target_url'] = null; // Set null jika 'auto'
             // Validasi tambahan: Harga wajib jika 'auto' tanpa varian
             if (!$hasVariants) {
                 if (empty($data['price']) || !is_numeric($data['price']) || $data['price'] <= 0) {
                      return redirect()->back()->withInput()->with('error', 'Produk "Otomatis" (Non-Varian) harus memiliki Harga lebih dari 0.');
                 }
             } else {
                 // Validasi tambahan: Minimal 1 varian valid jika 'auto' dengan varian
                 $validVariantExists = false;
                 if (!empty($data['variants']) && is_array($data['variants'])) {
                     foreach ($data['variants'] as $variant) {
                         // Cek apakah nama dan harga varian valid
                         if (!empty($variant['name']) && !empty($variant['price']) && is_numeric($variant['price']) && $variant['price'] > 0) {
                             $validVariantExists = true;
                             break;
                         }
                     }
                 }
                 if (!$validVariantExists) {
                      return redirect()->back()->withInput()->with('error', 'Produk otomatis dengan varian harus memiliki minimal satu varian aktif dengan Nama dan Harga (> 0) yang valid.');
                 }
             }
        }

        // Jalankan validasi
        if (! $this->validate($rules, $errors)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Proses upload ikon jika ada
        $iconFilename = null;
        $uploadPath = FCPATH . 'uploads/product_icons/'; // Path penyimpanan ikon
        if ($iconFile instanceof \CodeIgniter\HTTP\Files\UploadedFile && $iconFile->isValid() && !$iconFile->hasMoved()) {
            $iconFilename = $iconFile->getRandomName(); // Generate nama file acak
            if (!is_dir($uploadPath)) {
                @mkdir($uploadPath, 0777, true); // Buat direktori jika belum ada
            }
            try {
                $iconFile->move($uploadPath, $iconFilename); // Pindahkan file
            } catch (\Exception $e) {
                 log_message('error', 'Icon move failed: ' . $e->getMessage());
                 return redirect()->back()->withInput()->with('error', 'Gagal mengupload ikon: ' . $e->getMessage());
            }
        }

        // Mulai transaksi database
        $db = \Config\Database::connect();
        $db->transBegin();

        // Siapkan data untuk disimpan ke tabel products
        $saveData = [
            'user_id'       => $this->currentUserId,
            'product_name'  => $data['product_name'],
            'description'   => $data['description'],
            'order_type'    => $data['order_type'],
            'target_url'    => $data['target_url'],
            'is_active'     => isset($data['is_active']) ? 1 : 0, // Konversi checkbox ke boolean
            'icon_filename' => $iconFilename,
            'price'         => !$hasVariants ? ((!empty($data['price']) && is_numeric($data['price'])) ? $data['price'] : 0) : 0, // Harga utama 0 jika ada varian
            'has_variants'  => $hasVariants ? 1 : 0, // Simpan status varian
        ];

        // Simpan data produk
        if (! $this->productModel->save($saveData)) {
            $db->transRollback(); // Batalkan transaksi
            // Hapus ikon yang terlanjur diupload jika gagal simpan
            if ($iconFilename && file_exists($uploadPath . $iconFilename)) { @unlink($uploadPath . $iconFilename); }
            return redirect()->back()->withInput()->with('errors', $this->productModel->errors() ?: ['general' => 'Gagal menyimpan produk.']);
        }

        // Dapatkan ID produk yang baru disimpan
        $productId = $this->productModel->getInsertID();

        // Simpan data varian jika ada
        if ($hasVariants && !empty($data['variants']) && is_array($data['variants'])) {
            $variantsToInsert = [];
            foreach ($data['variants'] as $variant) {
                 // Hanya simpan varian yang valid
                 if (!empty($variant['name']) && !empty($variant['price']) && is_numeric($variant['price']) && $variant['price'] > 0) {
                     $variantsToInsert[] = [
                        'product_id' => $productId,
                        'name'       => $variant['name'],
                        'price'      => $variant['price'],
                        'stock'      => 0, // Stok awal varian di set 0, diisi manual nanti
                        'is_active'  => 1, // Varian baru defaultnya aktif
                     ];
                 }
            }

            // Simpan batch varian
            if (!empty($variantsToInsert)) {
                if (!$this->productVariantModel->insertBatch($variantsToInsert)) {
                    $db->transRollback(); // Batalkan transaksi
                    // Hapus ikon jika gagal simpan varian
                    if ($iconFilename && file_exists($uploadPath . $iconFilename)) { @unlink($uploadPath . $iconFilename); }
                    return redirect()->back()->withInput()->with('errors', $this->productVariantModel->errors() ?: ['general' => 'Gagal menyimpan varian produk.']);
                }
            }
        }

        // Cek status transaksi database
        if ($db->transStatus() === false) {
             $db->transRollback(); // Batalkan jika ada masalah
             // Hapus ikon jika transaksi gagal
             if ($iconFilename && file_exists($uploadPath . $iconFilename)) { @unlink($uploadPath . $iconFilename); }
             return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan database saat membuat produk.');
        } else {
             $db->transCommit(); // Konfirmasi transaksi jika berhasil
             return redirect()->route('dashboard')->with('success', 'Produk berhasil ditambahkan.');
        }
    }

    // Menampilkan form edit produk
    public function editProduct($id)
    {
        // Cari produk berdasarkan ID
        $product = $this->productModel->find($id);

        // Validasi kepemilikan produk
        if (! $product || $product->user_id != $this->currentUserId) {
            return redirect()->route('dashboard')->with('error', 'Produk tidak ditemukan atau Anda tidak memiliki akses.');
        }

        // Ambil data varian jika produk memiliki varian
        $variants = [];
        if ($product->has_variants) {
             $variants = $this->productVariantModel->where('product_id', $id)->orderBy('name', 'ASC')->findAll();
        }

        // Siapkan URL ikon
        $product->icon_url = $product->icon_filename
            ? base_url('uploads/product_icons/' . $product->icon_filename)
            : null;

        // Data untuk view
        $data = [
            'title'   => 'Edit Produk: ' . esc($product->product_name),
            'user'    => $this->userModel->find($this->currentUserId),
            'product' => $product,
            'variants' => $variants, // Kirim data varian ke view
            'action'  => route_to('product.update', $id), // URL action form update
        ];
        return view('dashboard/product_form', $data);
    }

    // Memproses update produk
    public function updateProduct($id)
    {
        // Cari produk yang akan diupdate
        $product = $this->productModel->find($id);

        // Validasi kepemilikan
        if (! $product || $product->user_id != $this->currentUserId) {
            return redirect()->route('dashboard')->with('error', 'Aksi tidak diizinkan.');
        }

        // Ambil data dari POST request
        $data = $this->request->getPost([
            'product_name', 'description', 'price', 'order_type', 'target_url', 'is_active', 'has_variants', 'variants', 'deleted_variants'
        ]);
        $iconFile = $this->request->getFile('product_icon');

        // Aturan validasi (sama seperti create)
         $rules = [
            'product_name' => 'required|max_length[255]',
            'order_type'   => 'required|in_list[manual,auto]',
            'price'        => 'permit_empty|numeric|greater_than_equal_to[0]',
            'product_icon' => 'permit_empty|is_image[product_icon]|mime_in[product_icon,image/jpg,image/jpeg,image/png,image/gif,image/webp]|max_size[product_icon,1024]',
        ];
         // Pesan error kustom
         $errors = [
            'product_icon' => [
                'max_size' => 'Ukuran file ikon maksimal 1MB.',
                'is_image' => 'File yang diupload harus berupa gambar.',
                'mime_in'  => 'Format gambar yang didukung: JPG, JPEG, PNG, GIF, WebP.'
            ]
        ];

        // Dapatkan data user
        $user = $this->userModel->find($this->currentUserId);
         if (!$user) {
             return redirect()->route('logout'); // Redirect jika user tidak valid
        }

        $isAuto = $data['order_type'] === 'auto';
        $hasVariants = $isAuto && ($data['has_variants'] ?? 0) == 1;

        // Validasi tambahan: Fitur 'auto' hanya untuk premium
         if ($isAuto && !$user->is_premium) {
            return redirect()->back()->withInput()->with('error', 'Fitur "Otomatis" hanya untuk user Premium. Silakan Upgrade.');
        }

        // Validasi tambahan: Target URL wajib untuk 'manual'
        if (!$isAuto) {
             if (empty(trim((string) $this->request->getPost('target_url')))) {
                 return redirect()->back()->withInput()->with('errors', ['target_url' => 'Target URL (Link Whatsapp) wajib diisi untuk produk manual.']);
             }
             if (! filter_var($this->request->getPost('target_url'), FILTER_VALIDATE_URL)) {
                 return redirect()->back()->withInput()->with('errors', ['target_url' => 'Target URL harus berupa link yang valid.']);
             }
             $data['target_url'] = $this->request->getPost('target_url');
        } else {
             $data['target_url'] = null; // Set null jika 'auto'
             // Validasi tambahan: Harga wajib jika 'auto' tanpa varian
             if (!$hasVariants) {
                 if (empty($data['price']) || !is_numeric($data['price']) || $data['price'] <= 0) {
                      return redirect()->back()->withInput()->with('error', 'Produk "Otomatis" (Non-Varian) harus memiliki Harga lebih dari 0.');
                 }
             } else {
                 // Validasi tambahan: Minimal 1 varian valid setelah update
                 $validVariantExists = false;
                 // Ambil ID varian yang akan dihapus
                 $deletedIds = isset($data['deleted_variants']) ? (is_array($data['deleted_variants']) ? $data['deleted_variants'] : [$data['deleted_variants']]) : [];
                 if (!empty($data['variants']) && is_array($data['variants'])) {
                     foreach ($data['variants'] as $variant) {
                         $variantIdToCheck = $variant['id'] ?? null;
                         // Lewati varian yang ditandai untuk dihapus
                         if ($variantIdToCheck && in_array($variantIdToCheck, $deletedIds)) {
                             continue;
                         }
                         // Cek validitas varian yang tersisa/baru
                         if (!empty($variant['name']) && !empty($variant['price']) && is_numeric($variant['price']) && $variant['price'] > 0) {
                             $validVariantExists = true;
                         }
                     }
                 }
                 if (!$validVariantExists) {
                      return redirect()->back()->withInput()->with('error', 'Produk otomatis dengan varian harus memiliki minimal satu varian aktif dengan Nama dan Harga (> 0) yang valid setelah disimpan.');
                 }
             }
        }

        // Jalankan validasi
        if (! $this->validate($rules, $errors)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Mulai transaksi database
        $db = \Config\Database::connect();
        $db->transBegin();

        $uploadPath = FCPATH . 'uploads/product_icons/';
        $iconFilename = $product->icon_filename; // Ambil nama file ikon lama
        $newFileUploaded = false;

        // Proses upload ikon baru jika ada
        if ($iconFile instanceof \CodeIgniter\HTTP\Files\UploadedFile && $iconFile->isValid() && !$iconFile->hasMoved()) {
            $newFileUploaded = true;
            $oldIconPath = $iconFilename ? $uploadPath . $iconFilename : null; // Path ikon lama

            $iconFilename = $iconFile->getRandomName(); // Nama file baru
            if (!is_dir($uploadPath)) { @mkdir($uploadPath, 0777, true); } // Buat direktori jika belum ada
             try {
                $iconFile->move($uploadPath, $iconFilename); // Pindahkan file baru
                // Hapus file ikon lama jika upload baru berhasil
                if ($oldIconPath && file_exists($oldIconPath)) {
                    @unlink($oldIconPath);
                }
             } catch (\Exception $e) {
                 log_message('error', 'Icon move failed during update: ' . $e->getMessage());
                 $db->transRollback(); // Batalkan transaksi
                 return redirect()->back()->withInput()->with('error', 'Gagal mengupload ikon baru: ' . $e->getMessage());
            }
        }

        // Siapkan data untuk update
        $updateData = [
            'product_name'  => $data['product_name'],
            'description'   => $data['description'],
            'order_type'    => $data['order_type'],
            'target_url'    => $data['target_url'],
            'is_active'     => isset($data['is_active']) ? 1 : 0,
            'icon_filename' => $iconFilename, // Nama file baru atau lama
            'has_variants'  => $hasVariants ? 1 : 0,
            'price'         => !$hasVariants ? ((!empty($data['price']) && is_numeric($data['price'])) ? $data['price'] : 0) : 0, // Harga utama 0 jika ada varian
        ];

        // Update data produk
        if (! $this->productModel->update($id, $updateData)) {
            $db->transRollback(); // Batalkan transaksi
             // Hapus ikon baru jika update gagal
             if ($newFileUploaded && $iconFilename && file_exists($uploadPath . $iconFilename)) { @unlink($uploadPath . $iconFilename); }
            return redirect()->back()->withInput()->with('errors', $this->productModel->errors() ?: ['general' => 'Gagal memperbarui produk.']);
        }

        // --- Proses Varian ---
        $variantsToInsert = [];
        $variantsToUpdate = [];
        $processedVariantIds = []; // ID varian yang diproses (update/insert)

        // Jika produk diubah menjadi memiliki varian atau tetap memiliki varian
        if ($hasVariants && !empty($data['variants']) && is_array($data['variants'])) {
             foreach ($data['variants'] as $variantInput) {
                 // Validasi dasar input varian
                 if (empty($variantInput['name']) || empty($variantInput['price']) || !is_numeric($variantInput['price']) || $variantInput['price'] <= 0) continue;

                 // Data untuk insert/update
                 $variantData = [
                     'product_id' => $id,
                     'name'       => $variantInput['name'],
                     'price'      => $variantInput['price'],
                     'is_active'  => $variantInput['is_active'] ?? 1, // Default aktif jika tidak diset
                 ];

                 // Cek apakah ini varian yang sudah ada (punya ID)
                 if (isset($variantInput['id']) && !empty($variantInput['id'])) {
                     $variantData['id'] = $variantInput['id'];
                     $variantsToUpdate[] = $variantData; // Tambahkan ke daftar update
                     $processedVariantIds[] = $variantInput['id']; // Catat ID yang diproses
                 } else {
                     // Ini varian baru, tambahkan ke daftar insert
                     $variantData['stock'] = 0; // Stok awal 0
                     $variantsToInsert[] = $variantData;
                 }
             }

             // Lakukan update batch untuk varian yang sudah ada
             if (!empty($variantsToUpdate)) {
                 if (!$this->productVariantModel->updateBatch($variantsToUpdate, 'id')) {
                     $db->transRollback();
                     return redirect()->back()->withInput()->with('error', 'Gagal memperbarui varian yang ada.');
                 }
             }
             // Lakukan insert batch untuk varian baru
             if (!empty($variantsToInsert)) {
                  if (!$this->productVariantModel->insertBatch($variantsToInsert)) {
                     $db->transRollback();
                     return redirect()->back()->withInput()->with('error', 'Gagal menyimpan varian baru.');
                 }
             }

             // Proses penghapusan varian yang ditandai
             if (!empty($data['deleted_variants'])) {
                 $deletedIds = is_array($data['deleted_variants']) ? $data['deleted_variants'] : [$data['deleted_variants']];
                 // Filter ID yang benar-benar akan dihapus (tidak ada dalam update/insert)
                 $idsToDelete = array_diff($deletedIds, $processedVariantIds);

                 if (!empty($idsToDelete)) {
                     // Hapus varian berdasarkan ID dan product_id (keamanan)
                     if (!$this->productVariantModel->where('product_id', $id)->whereIn('id', $idsToDelete)->delete()) {
                         $db->transRollback();
                         return redirect()->back()->withInput()->with('error', 'Gagal menghapus varian lama.');
                     }
                 }
             }

        // Jika produk diubah dari memiliki varian menjadi tidak punya varian
        } elseif ($product->has_variants && !$hasVariants) {
            // Hapus semua varian yang terkait dengan produk ini
            if (!$this->productVariantModel->where('product_id', $id)->delete()) {
                 $db->transRollback();
                 return redirect()->back()->withInput()->with('error', 'Gagal menghapus varian saat mengubah tipe produk.');
            }
        }

        // Cek status akhir transaksi database
        if ($db->transStatus() === false) {
             $db->transRollback(); // Batalkan jika ada masalah
             // Hapus ikon baru jika transaksi gagal
             if ($newFileUploaded && $iconFilename && file_exists($uploadPath . $iconFilename)) { @unlink($uploadPath . $iconFilename); }
             return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan database saat memperbarui produk.');
        } else {
             $db->transCommit(); // Konfirmasi transaksi jika berhasil
             return redirect()->route('dashboard')->with('success', 'Produk berhasil diperbarui.');
        }
    }

    /**
     * Menghapus produk
     */
    public function deleteProduct($id)
    {
        // Cari produk berdasarkan ID
        $product = $this->productModel->find($id);

        // Validasi kepemilikan
        if (! $product || $product->user_id != $this->currentUserId) {
            return redirect()->route('dashboard')->with('error', 'Aksi tidak diizinkan.');
        }

        // Mulai transaksi database
        $db = \Config\Database::connect();
        $db->transBegin();

        // Hapus produk (soft delete jika diaktifkan, atau hard delete)
        $deleteSuccess = $this->productModel->delete($id);

        if ($deleteSuccess) {
            // Hapus file ikon produk jika produk berhasil dihapus dari DB
            $uploadPath = FCPATH . 'uploads/product_icons/';
            if ($product->icon_filename && file_exists($uploadPath . $product->icon_filename)) {
                @unlink($uploadPath . $product->icon_filename); // Hapus file ikon
            }
            // Transaksi commit otomatis jika tidak ada error (tergantung config DB)
            $db->transCommit(); // Konfirmasi transaksi
            return redirect()->route('dashboard')->with('success', 'Produk berhasil dihapus.');
        } else {
             $db->transRollback(); // Batalkan jika gagal hapus produk
             return redirect()->route('dashboard')->with('error', 'Gagal menghapus produk.');
        }
    }

    // Menampilkan halaman upgrade premium
    public function upgradePage()
    {
        $data = [
            'title' => 'Upgrade ke Premium',
            'user'  => $this->userModel->find($this->currentUserId)
        ];
        return view('dashboard/upgrade_page', $data);
    }

    // Menampilkan halaman kelola stok (bisa varian atau non-varian)
    public function manageStock($productId)
    {
        // Cari produk
        $product = $this->productModel->find($productId);

        // Validasi produk dan kepemilikan, serta tipe 'auto'
        if (! $product || $product->user_id != $this->currentUserId || $product->order_type !== 'auto') {
            return redirect()->route('dashboard')->with('error', 'Aksi tidak diizinkan atau produk bukan tipe otomatis.');
        }

        // Jika produk punya varian, tampilkan halaman daftar varian
        if ($product->has_variants) {
            $variants = $this->productVariantModel->where('product_id', $productId)->orderBy('name', 'ASC')->findAll();
            $data = [
                 'title'   => 'Kelola Stok: ' . esc($product->product_name),
                 'product' => $product,
                 'variants' => $variants,
            ];
            return view('dashboard/manage_variant_stock', $data);
        }
        // Jika produk tidak punya varian, tampilkan halaman kelola stok non-varian
        else {
            $perPageStock = 20; // Item stok per halaman
            $pagerService = Services::pager(); // Dapatkan service pager
            $currentPageStock = $this->request->getVar('page_stock') ? (int) $this->request->getVar('page_stock') : 1; // Halaman saat ini

            // Ambil data stok non-varian dengan paginasi
            $data = [
                'title'   => 'Kelola Stok: ' . esc($product->product_name),
                'user'    => $this->userModel->find($this->currentUserId),
                'product' => $product,
                'stocks'  => $this->productStockModel
                                ->where('product_id', $productId)
                                ->where('variant_id', null) // Hanya stok non-varian
                                ->orderBy('created_at', 'DESC') // Urutkan terbaru dulu
                                ->paginate($perPageStock, 'stock'), // Paginasi grup 'stock'
                'pager'   => $this->productStockModel->pager, // Objek pager
                'currentPage' => $currentPageStock, // Info halaman
                'perPage'     => $perPageStock, // Info item per halaman
            ];
            return view('dashboard/manage_stock', $data);
        }
    }

    // Menampilkan halaman kelola item stok untuk varian spesifik
    public function manageVariantStockItems($productId, $variantId)
    {
        // Validasi produk dan varian
        $product = $this->productModel->find($productId);
        $variant = $this->productVariantModel->find($variantId);

        if (! $product || $product->user_id != $this->currentUserId || !$variant || $variant->product_id != $productId) {
            return redirect()->route('dashboard')->with('error', 'Varian atau produk tidak ditemukan.');
        }

        $perPageStock = 20; // Item per halaman
        $currentPageStock = $this->request->getVar('page_stock') ? (int) $this->request->getVar('page_stock') : 1; // Halaman saat ini

        // Ambil item stok untuk varian ini dengan paginasi
        $data = [
            'title'      => 'Kelola Item Stok: ' . esc($variant->name),
            'product'    => $product,
            'variant'    => $variant,
            'stocks'     => $this->productStockModel
                                ->where('variant_id', $variantId) // Filter berdasarkan variant_id
                                ->orderBy('created_at', 'DESC') // Urutkan terbaru dulu
                                ->paginate($perPageStock, 'stock'), // Paginasi grup 'stock'
            'pager'      => $this->productStockModel->pager, // Objek pager
            'currentPage' => $currentPageStock, // Info halaman
            'perPage'     => $perPageStock, // Info item per halaman
        ];

        return view('dashboard/manage_variant_stock_items', $data);
    }

    /**
     * Tambah Stok (Produk Non-Varian) - Menggunakan format JSON per baris
     */
    public function addStock($productId)
    {
        // Validasi produk
        $product = $this->productModel->find($productId);
        if (! $product || $product->user_id != $this->currentUserId || $product->order_type !== 'auto' || $product->has_variants) {
            return redirect()->route('dashboard')->with('error', 'Aksi tidak diizinkan atau produk bervarian.');
        }

        // Ambil data JSON dari textarea
        $stockInput = $this->request->getPost('stock_data');
        if (empty(trim($stockInput))) {
            return redirect()->back()->with('error', 'Data stok tidak boleh kosong.');
        }

        // Pisahkan per baris dan filter baris kosong
        $stockLines = array_filter(array_map('trim', explode("\n", $stockInput)));
        if (empty($stockLines)) {
            return redirect()->back()->with('error', 'Tidak ada data stok valid untuk ditambahkan.');
        }

        $batchData = []; // Array untuk menyimpan data batch insert
        $now = date('Y-m-d H:i:s'); // Waktu saat ini
        $lineErrors = []; // Array untuk menyimpan error per baris

        // Validasi setiap baris JSON
        foreach ($stockLines as $index => $line) {
            if (empty($line)) continue; // Lewati baris kosong

            $jsonData = json_decode($line); // Decode JSON
            $lineNumber = $index + 1; // Nomor baris untuk pesan error

            // Cek error decode JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                $lineErrors[] = "Baris {$lineNumber}: Format JSON tidak valid (" . json_last_error_msg() . "). Input: " . substr($line, 0, 50) . "...";
                continue;
            }
            // Cek apakah hasil decode adalah objek
            if (!is_object($jsonData)) {
                 $lineErrors[] = "Baris {$lineNumber}: Data harus berupa objek JSON. Input: " . substr($line, 0, 50) . "...";
                 continue;
            }
            // Cek field wajib 'email'
            if (!isset($jsonData->email) || empty(trim($jsonData->email))) {
                 $lineErrors[] = "Baris {$lineNumber}: Field 'email' wajib diisi.";
                 continue;
            }
            // Cek field wajib 'password'
             if (!isset($jsonData->password) || empty(trim($jsonData->password))) {
                 $lineErrors[] = "Baris {$lineNumber}: Field 'password' wajib diisi.";
                 continue;
            }

            // Jika valid, tambahkan ke batch data
            $batchData[] = [
                'product_id' => $productId,
                'variant_id' => null, // null karena non-varian
                'stock_data' => $line, // Simpan JSON string asli
                'is_used'    => 0, // Belum terpakai
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Jika ada error di salah satu baris, kembalikan dengan pesan error
        if (!empty($lineErrors)) {
            return redirect()->back()->withInput()->with('error', 'Gagal menambahkan stok:<ul class="list-disc pl-5 text-sm">' . implode('', array_map(fn($err) => "<li>{$err}</li>", $lineErrors)) . '</ul>');
        }
        // Jika tidak ada data valid setelah validasi
        if (empty($batchData)) {
             return redirect()->back()->with('error', 'Tidak ada data stok valid untuk ditambahkan setelah validasi.');
        }

        // Mulai transaksi database
        $db = \Config\Database::connect();
        $db->transBegin();

        // Lakukan batch insert
        $inserted = $this->productStockModel->insertBatch($batchData);

        // Cek hasil insert dan status transaksi
        if ($inserted === false || $db->transStatus() === false) {
             $db->transRollback(); // Batalkan jika gagal
            log_message('error', 'Gagal insert batch stok (non-varian) untuk produk ID: ' . $productId . '. Error DB: ' . print_r($db->error(), true));
            return redirect()->back()->withInput()->with('error', 'Gagal menambahkan stok karena kesalahan database.');
        } else {
             $db->transCommit(); // Konfirmasi jika berhasil
            return redirect()->route('product.stock.manage', [$productId])->with('success', count($batchData) . ' item stok berhasil ditambahkan.');
        }
    }

    /**
     * Tambah Item Stok Unik untuk Varian Tertentu - Menggunakan format JSON per baris
     */
    public function addVariantStockItem($productId, $variantId)
    {
        // Validasi produk dan varian
        $product = $this->productModel->find($productId);
        $variant = $this->productVariantModel->find($variantId);
        if (! $product || $product->user_id != $this->currentUserId || !$variant || $variant->product_id != $productId) {
            return redirect()->route('dashboard')->with('error', 'Varian atau produk tidak ditemukan.');
        }

        // Ambil data JSON dari textarea
        $stockInput = $this->request->getPost('stock_data');
        if (empty(trim($stockInput))) {
            return redirect()->back()->with('error', 'Data stok tidak boleh kosong.');
        }

        // Pisahkan per baris dan filter baris kosong
        $stockLines = array_filter(array_map('trim', explode("\n", $stockInput)));
        if (empty($stockLines)) {
            return redirect()->back()->with('error', 'Tidak ada data stok valid untuk ditambahkan.');
        }

        $batchData = []; // Array untuk batch insert
        $now = date('Y-m-d H:i:s'); // Waktu saat ini
        $lineErrors = []; // Array untuk error per baris

        // Validasi setiap baris JSON
        foreach ($stockLines as $index => $line) {
             if (empty($line)) continue; // Lewati baris kosong

            $jsonData = json_decode($line); // Decode JSON
            $lineNumber = $index + 1; // Nomor baris

            // Validasi JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                $lineErrors[] = "Baris {$lineNumber}: Format JSON tidak valid (" . json_last_error_msg() . "). Input: " . substr($line, 0, 50) . "...";
                continue;
            }
            if (!is_object($jsonData)) {
                 $lineErrors[] = "Baris {$lineNumber}: Data harus berupa objek JSON. Input: " . substr($line, 0, 50) . "...";
                 continue;
            }
            // Validasi field wajib
            if (!isset($jsonData->email) || empty(trim($jsonData->email))) {
                 $lineErrors[] = "Baris {$lineNumber}: Field 'email' wajib diisi.";
                 continue;
            }
             if (!isset($jsonData->password) || empty(trim($jsonData->password))) {
                 $lineErrors[] = "Baris {$lineNumber}: Field 'password' wajib diisi.";
                 continue;
            }

            // Tambahkan ke batch data jika valid
            $batchData[] = [
                'product_id' => $productId,
                'variant_id' => $variantId, // ID varian terkait
                'stock_data' => $line, // Simpan JSON string asli
                'is_used'    => 0, // Belum terpakai
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Kembalikan error jika ada
        if (!empty($lineErrors)) {
            return redirect()->back()->withInput()->with('error', 'Gagal menambahkan stok:<ul class="list-disc pl-5 text-sm">' . implode('', array_map(fn($err) => "<li>{$err}</li>", $lineErrors)) . '</ul>');
        }
        // Kembalikan error jika tidak ada data valid
        if (empty($batchData)) {
            return redirect()->back()->with('error', 'Tidak ada data stok valid untuk ditambahkan setelah validasi.');
        }

        // Mulai transaksi database
        $db = \Config\Database::connect();
        $db->transBegin();

        // Lakukan batch insert
        $inserted = $this->productStockModel->insertBatch($batchData);

        // Cek hasil insert
        if ($inserted === false || $db->transStatus() === false) {
             $db->transRollback(); // Batalkan jika gagal
             log_message('error', 'Gagal insert batch stok (varian) untuk produk ID: ' . $productId . ', Varian ID: ' . $variantId . '. Error DB: ' . print_r($db->error(), true));
            return redirect()->back()->withInput()->with('error', 'Gagal menambahkan item stok karena kesalahan database.');
        } else {
             // Sinkronisasi jumlah stok di tabel product_variants setelah insert berhasil
             if(!$this->productVariantModel->synchronizeStock($variantId, $this->productStockModel)){
                 $db->transRollback(); // Batalkan jika sinkronisasi gagal
                 log_message('error', 'Gagal sinkronisasi stok setelah insert batch untuk Varian ID: ' . $variantId);
                 return redirect()->back()->withInput()->with('error', 'Gagal sinkronisasi stok varian setelah menambah item.');
             }

             $db->transCommit(); // Konfirmasi jika semua berhasil
            return redirect()->route('product.variant.stock.items', [$productId, $variantId])->with('success', count($batchData) . ' item stok berhasil ditambahkan.');
        }
    }

    // Menghapus item stok non-varian
    public function deleteStock($productId, $stockId)
    {
        // Validasi produk dan stok
        $product = $this->productModel->find($productId);
        $stock = $this->productStockModel->find($stockId);

        if (! $product || $product->user_id != $this->currentUserId || !$stock || $stock->product_id != $productId || $stock->variant_id !== null) {
            return redirect()->route('dashboard')->with('error', 'Aksi tidak diizinkan.');
        }

        // Tidak bisa hapus stok yang sudah terpakai
        if ($stock->is_used) {
            return redirect()->route('product.stock.manage', [$productId])->with('error', 'Tidak dapat menghapus stok yang sudah terpakai.');
        }

        // Hapus stok
        if ($this->productStockModel->delete($stockId)) {
            return redirect()->route('product.stock.manage', [$productId])->with('success', 'Item stok berhasil dihapus.');
        } else {
            return redirect()->route('product.stock.manage', [$productId])->with('error', 'Gagal menghapus item stok.');
        }
    }

    // Menghapus item stok varian
     public function deleteVariantStockItem($productId, $variantId, $stockId)
    {
        // Validasi produk, varian, dan stok
        $product = $this->productModel->find($productId);
        $variant = $this->productVariantModel->find($variantId);
        $stock = $this->productStockModel->find($stockId);

        if (! $product || $product->user_id != $this->currentUserId || !$variant || $variant->product_id != $productId || !$stock || $stock->variant_id != $variantId) {
            return redirect()->route('dashboard')->with('error', 'Aksi tidak diizinkan.');
        }

        // Tidak bisa hapus stok yang sudah terpakai
        if ($stock->is_used) {
            return redirect()->route('product.variant.stock.items', [$productId, $variantId])->with('error', 'Tidak dapat menghapus item stok yang sudah terpakai.');
        }

        // Mulai transaksi
        $db = \Config\Database::connect();
        $db->transBegin();

        // Hapus item stok
        if ($this->productStockModel->delete($stockId)) {
             // Sinkronisasi jumlah stok di tabel varian setelah hapus
             if(!$this->productVariantModel->synchronizeStock($variantId, $this->productStockModel)){
                 $db->transRollback(); // Batalkan jika sinkronisasi gagal
                 log_message('error', 'Gagal sinkronisasi stok setelah delete item untuk Varian ID: ' . $variantId);
                 return redirect()->route('product.variant.stock.items', [$productId, $variantId])->with('error', 'Gagal sinkronisasi stok varian setelah menghapus item.');
             }

            $db->transCommit(); // Konfirmasi jika semua berhasil
            return redirect()->route('product.variant.stock.items', [$productId, $variantId])->with('success', 'Item stok berhasil dihapus.');
        } else {
            $db->transRollback(); // Batalkan jika gagal hapus
            log_message('error', 'Gagal delete item stok ID: ' . $stockId . '. Error DB: ' . print_r($db->error(), true));
            return redirect()->route('product.variant.stock.items', [$productId, $variantId])->with('error', 'Gagal menghapus item stok.');
        }
    }


    // Menampilkan halaman pengaturan profil
     public function profileSettings()
     {
         // Ambil data user
         $user = $this->userModel->find($this->currentUserId);
         // Siapkan URL logo atau placeholder
         $logoUrl = $user->logo_filename
            ? base_url('uploads/logos/' . $user->logo_filename)
            : 'https://ui-avatars.com/api/?name=' . urlencode($user->store_name ?: $user->username) . '&background=4f46e5&color=ffffff&size=64&bold=true';

         // Data untuk view
         $data = [
             'title' => 'Pengaturan',
             'user'  => $user,
             'logoUrl' => $logoUrl,
             'actionProfile' => route_to('dashboard.profile.update'),
             'actionBank' => route_to('dashboard.bank.update'),
             'actionMidtrans' => route_to('dashboard.midtrans.update'),
             'actionTripay' => route_to('dashboard.tripay.update'), // Tambahkan action Tripay
             'actionGateway' => route_to('dashboard.gateway.update'), // Tambahkan action Gateway Preference
             'actionPassword' => route_to('dashboard.password.update'),
         ];
         return view('dashboard/profile_settings', $data);
     }

     // Memproses update info profil & kontak
     public function updateProfile()
     {
         // Ambil data user saat ini
         $user = $this->userModel->find($this->currentUserId);

         // Aturan validasi
         $rules = [
             'username'      => "required|alpha_numeric|min_length[3]|max_length[30]|is_unique[users.username,id,{$this->currentUserId}]", // Username unik kecuali untuk diri sendiri
             'store_name'    => 'required|string|max_length[100]',
             'profile_subtitle' => 'permit_empty|string|max_length[150]',
             'whatsapp_link' => 'permit_empty|valid_url|max_length[255]',
             'logo'          => 'permit_empty|uploaded[logo]|max_size[logo,1024]|is_image[logo]|mime_in[logo,image/jpg,image/jpeg,image/png,image/gif,image/webp]', // Validasi logo
         ];
         // Pesan error kustom
         $errors = [
             'username' => [
                 'alpha_numeric' => 'Username hanya boleh berisi huruf dan angka (tanpa spasi atau karakter lain).',
                 'is_unique' => 'Username ini sudah digunakan pengguna lain.'
             ],
             'store_name' => [
                 'required' => 'Nama Toko wajib diisi.'
             ],
             'logo' => [
                'max_size' => 'Ukuran file logo maksimal 1MB.',
                'is_image' => 'File yang diupload harus berupa gambar.',
                'mime_in'  => 'Format gambar yang didukung: JPG, JPEG, PNG, GIF, WebP.'
            ]
         ];

         // Jalankan validasi
         if (!$this->validate($rules, $errors)) {
             session()->setFlashdata('errorsProfile', $this->validator->getErrors()); // Simpan error ke flashdata
             return redirect()->back()->withInput();
         }

         // Siapkan data untuk update
         $data = [
             'username'      => trim($this->request->getPost('username') ?? ''),
             'store_name'    => trim($this->request->getPost('store_name') ?? ''),
             'profile_subtitle' => trim($this->request->getPost('profile_subtitle') ?? '') ?: null, // Set null jika kosong
             'whatsapp_link' => trim($this->request->getPost('whatsapp_link') ?? '') ?: null, // Set null jika kosong
         ];

         // Proses upload logo baru jika ada
         $logoFile = $this->request->getFile('logo');
         $newLogoFilename = null;
         $uploadPath = FCPATH . 'uploads/logos/'; // Path penyimpanan logo
         $newFileUploaded = false;

         if ($logoFile instanceof \CodeIgniter\HTTP\Files\UploadedFile && $logoFile->isValid() && !$logoFile->hasMoved()) {
             $newFileUploaded = true;
             $oldLogoPath = $user->logo_filename ? $uploadPath . $user->logo_filename : null; // Path logo lama
             $newLogoFilename = $logoFile->getRandomName(); // Nama file baru

             if (!is_dir($uploadPath)) { @mkdir($uploadPath, 0777, true); } // Buat direktori jika belum ada
             try {
                 $logoFile->move($uploadPath, $newLogoFilename); // Pindahkan file baru
                 // Hapus logo lama jika upload baru berhasil
                 if ($oldLogoPath && file_exists($oldLogoPath)) {
                     @unlink($oldLogoPath);
                 }
                 $data['logo_filename'] = $newLogoFilename; // Update nama file di data
             } catch (\Exception $e) {
                 log_message('error', 'Logo move failed: ' . $e->getMessage());
                 return redirect()->back()->withInput()->with('errorProfile', 'Gagal mengupload logo baru: ' . $e->getMessage());
             }
         }

         // Lakukan update data user
         if ($this->userModel->update($this->currentUserId, $data)) {
             // Update session username jika berubah
             if (isset($data['username']) && $data['username'] !== $user->username) {
                 session()->set('username', $data['username']);
             }
              log_message('info', 'Profile updated for User ID: ' . $this->currentUserId);
             return redirect()->route('dashboard.settings')->with('successProfile', 'Informasi profil berhasil diperbarui.');
         } else {
             // Jika update gagal
             log_message('error', 'Profile update failed for User ID: ' . $this->currentUserId . ' Errors: ' . print_r($this->userModel->errors(), true));
              // Hapus logo baru jika update gagal
              if ($newFileUploaded && $newLogoFilename && file_exists($uploadPath . $newLogoFilename)) { @unlink($uploadPath . $newLogoFilename); }
             session()->setFlashdata('errorProfile', 'Gagal memperbarui informasi profil.'); // Simpan error ke flashdata
             return redirect()->back()->withInput();
         }
     }

     // Memproses update info bank
     public function updateBank()
     {
        // Aturan validasi
        $rules = [
             'bank_name' => 'permit_empty|string|max_length[100]',
             'account_number' => 'permit_empty|numeric|max_length[50]',
             'account_name' => 'permit_empty|string|max_length[150]',
        ];

        // Jalankan validasi
         if (!$this->validate($rules)) {
             session()->setFlashdata('errorsBank', $this->validator->getErrors());
             return redirect()->back()->withInput();
         }

         // Siapkan data, set null jika kosong
         $data = [
             'bank_name' => trim($this->request->getPost('bank_name') ?? '') ?: null,
             'account_number' => trim($this->request->getPost('account_number') ?? '') ?: null,
             'account_name' => trim($this->request->getPost('account_name') ?? '') ?: null,
         ];

         // Lakukan update
         if ($this->userModel->update($this->currentUserId, $data)) {
             log_message('info', 'Bank details updated for User ID: ' . $this->currentUserId);
             return redirect()->route('dashboard.settings')->with('successBank', 'Informasi penarikan dana berhasil diperbarui.');
         } else {
              log_message('error', 'Bank details update failed for User ID: ' . $this->currentUserId . ' Errors: ' . print_r($this->userModel->errors(), true));
             session()->setFlashdata('errorBank', 'Gagal memperbarui informasi penarikan dana.');
             return redirect()->back()->withInput();
         }
     }

    // Memproses update API Key Midtrans
    public function updateMidtransKeys()
     {
         // Aturan validasi
         $rules = [
             'midtrans_server_key' => 'permit_empty|string|max_length[255]',
             'midtrans_client_key' => 'permit_empty|string|max_length[255]',
         ];

        // Jalankan validasi
        if (!$this->validate($rules)) {
             session()->setFlashdata('errorsMidtrans', $this->validator->getErrors());
             return redirect()->back()->withInput();
         }

        // Ambil data, trim, dan set null jika kosong
        $serverKey = trim($this->request->getPost('midtrans_server_key') ?? '');
         $clientKey = trim($this->request->getPost('midtrans_client_key') ?? '');

        $data = [
             'midtrans_server_key' => $serverKey ?: null,
             'midtrans_client_key' => $clientKey ?: null,
         ];

        // Lakukan update
        if ($this->userModel->update($this->currentUserId, $data)) {
              log_message('info', 'Midtrans keys updated for User ID: ' . $this->currentUserId);
             return redirect()->route('dashboard.settings')->with('successMidtrans', 'API Key Midtrans berhasil diperbarui.');
         } else {
              log_message('error', 'Midtrans keys update failed for User ID: ' . $this->currentUserId . ' Errors: ' . print_r($this->userModel->errors(), true));
              session()->setFlashdata('errorMidtrans', 'Gagal memperbarui API Key Midtrans.');
             return redirect()->back()->withInput();
         }
     }

    // Memproses update API Key Tripay
    public function updateTripayKeys()
    {
        // Aturan validasi
        $rules = [
            'tripay_api_key'       => 'permit_empty|string|max_length[255]',
            'tripay_private_key'   => 'permit_empty|string|max_length[255]',
            'tripay_merchant_code' => 'permit_empty|string|max_length[64]',
        ];
        // Jalankan validasi
        if (!$this->validate($rules)) {
            session()->setFlashdata('errorsTripay', $this->validator->getErrors());
            return redirect()->back()->withInput();
        }

        // Siapkan data, set null jika kosong
        $data = [
            'tripay_api_key'       => trim($this->request->getPost('tripay_api_key') ?? '') ?: null,
            'tripay_private_key'   => trim($this->request->getPost('tripay_private_key') ?? '') ?: null,
            'tripay_merchant_code' => trim($this->request->getPost('tripay_merchant_code') ?? '') ?: null,
        ];

        // Lakukan update
        if ($this->userModel->update($this->currentUserId, $data)) {
            log_message('info', 'Tripay keys updated for User ID: '.$this->currentUserId);
            return redirect()->route('dashboard.settings')->with('successTripay', 'API Key Tripay berhasil diperbarui.');
        }

        log_message('error', 'Tripay keys update failed for User ID: '.$this->currentUserId);
        return redirect()->route('dashboard.settings')->with('errorTripay', 'Gagal menyimpan API Key Tripay.');
    }

    // Memproses update preferensi gateway pembayaran
    public function updateGatewayPreference()
    {
        $choice = $this->request->getPost('gateway_active');
        // Tambahkan 'orderkuota' ke dalam array pengecekan
        if (!in_array($choice, ['system','midtrans','tripay', 'orderkuota'], true)) { // <-- Updated line
            return redirect()->back()->with('errorGateway', 'Pilihan gateway tidak valid.');
        }

        // Simpan pilihan ke database
        if ($this->userModel->update($this->currentUserId, ['gateway_active' => $choice])) {
            return redirect()->route('dashboard.settings')->with('successGateway', 'Preferensi gateway diperbarui.');
        }
        return redirect()->route('dashboard.settings')->with('errorGateway', 'Gagal memperbarui preferensi gateway.');
    }


    // Memproses update password
     public function updatePassword()
     {
        // Aturan validasi password
        $rules = [
            'current_password' => ['label' => 'Password Saat Ini', 'rules' => 'required'],
            'new_password' => ['label' => 'Password Baru', 'rules' => 'required|min_length[8]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/]', 'errors' => ['regex_match' => '{field} harus mengandung setidaknya satu huruf kecil, satu huruf besar, dan satu angka.']],
            'confirm_password' => ['label' => 'Konfirmasi Password Baru', 'rules' => 'required|matches[new_password]', 'errors' => ['matches' => '{field} tidak cocok dengan Password Baru.']],
        ];

        // Ambil data user
         $user = $this->userModel->find($this->currentUserId);
         if (!$user) {
             log_message('error', 'User not found for password change. User ID: ' . $this->currentUserId);
             return redirect()->route('logout'); // Redirect jika user tidak valid
         }

         // Verifikasi password saat ini
         $currentPasswordInput = $this->request->getPost('current_password');
         if (!password_verify((string)$currentPasswordInput, $user->password_hash)) {
              session()->setFlashdata('errorsPassword', ['current_password' => 'Password Saat Ini salah.']);
             return redirect()->back()->withInput();
         }

         // Jalankan validasi password baru
         if (!$this->validate($rules)) {
             session()->setFlashdata('errorsPassword', $this->validator->getErrors());
             return redirect()->back()->withInput();
         }

         // Hash password baru
         $newPasswordHash = password_hash($this->request->getPost('new_password'), PASSWORD_DEFAULT);

         // Update password di database
         if ($this->userModel->update($this->currentUserId, ['password_hash' => $newPasswordHash])) {
              log_message('info', 'Password changed successfully for User ID: ' . $this->currentUserId);
             return redirect()->route('dashboard.settings')->with('successPassword', 'Password berhasil diubah.');
         } else {
             log_message('error', 'Password update failed for User ID: ' . $this->currentUserId . ' Errors: ' . print_r($this->userModel->errors(), true));
             session()->setFlashdata('errorPassword', 'Gagal mengubah password.');
             return redirect()->back()->withInput();
         }
     }

     // Menampilkan halaman penarikan dana
     public function withdrawPage()
     {
         // Ambil data user
         $user = $this->userModel->find($this->currentUserId);
         // Cek apakah detail bank sudah diisi
         $hasBankDetails = !empty($user->bank_name) && !empty($user->account_number) && !empty($user->account_name);

         $perPageWithdraw = 15; // Item per halaman
         $pagerService = Services::pager(); // Dapatkan service pager
         $currentPageWithdraw = $this->request->getVar('page_withdraw') ? (int) $this->request->getVar('page_withdraw') : 1; // Halaman saat ini

         // Ambil riwayat penarikan user dengan paginasi
         $withdrawals = $this->withdrawalRequestModel
                             ->select('withdrawal_requests.*, users.username as user_username') // Ambil username juga
                             ->join('users', 'users.id = withdrawal_requests.user_id', 'left') // Join tabel users
                             ->where('withdrawal_requests.user_id', $this->currentUserId) // Filter berdasarkan user ID
                             ->orderBy('withdrawal_requests.created_at', 'DESC') // Urutkan terbaru dulu
                             ->paginate($perPageWithdraw, 'withdraw'); // Paginasi grup 'withdraw'

         // Parse JSON bank_details untuk setiap withdrawal request
         if (!empty($withdrawals)) {
            foreach ($withdrawals as $wd) {
                if (isset($wd->bank_details)) {
                    $decoded = json_decode($wd->bank_details);
                    // Tambahkan properti baru ke objek withdrawal untuk kemudahan di view
                    $wd->bank_name = $decoded->bank_name ?? null;
                    $wd->account_number = $decoded->account_number ?? null;
                    $wd->account_name = $decoded->account_name ?? null;
                } else {
                    $wd->bank_name = null;
                    $wd->account_number = null;
                    $wd->account_name = null;
                }
            }
         }

         // Data untuk view
         $data = [
             'title'             => 'Penarikan Dana',
             'user'              => $user,
             'hasBankDetails'    => $hasBankDetails,
             'withdrawals'       => $withdrawals,
             'pager'             => $this->withdrawalRequestModel->pager, // Objek pager
             'currentPage'       => $currentPageWithdraw, // Info halaman
             'itemsPerPage'      => $perPageWithdraw, // Info item per halaman
         ];
         return view('dashboard/withdraw', $data);
     }

    // Memproses permintaan penarikan dana
    public function requestWithdrawal()
    {
        // Ambil data user
        $user = $this->userModel->find($this->currentUserId);

        // Validasi: Pastikan detail bank sudah terisi
        if (empty($user->bank_name) || empty($user->account_number) || empty($user->account_name)) {
            return redirect()->route('dashboard.settings')->with('error', 'Lengkapi informasi rekening bank Anda terlebih dahulu di Pengaturan Profil.');
        }

        $currentBalance = (int) ($user->balance ?? 0); // Saldo saat ini
        $amountInput = $this->request->getPost('amount'); // Jumlah yang diminta
        // Validasi dan konversi jumlah ke integer
        $amountInt = filter_var($amountInput, FILTER_VALIDATE_INT);
        if ($amountInt === false) $amountInt = 0;

        $minWithdrawal = 10000; // Minimal penarikan

        // Logging untuk debug
        log_message('debug', 'Withdrawal Request - Amount Input: "' . $amountInput . '" (Type: ' . gettype($amountInput) . ') | Int Value: ' . $amountInt);
        log_message('debug', 'Withdrawal Request - Current Balance for Rule: ' . $currentBalance . ' (Type: ' . gettype($currentBalance) . ')');

        // Aturan validasi jumlah penarikan
        $rules = [
            'amount' => [
                'label' => 'Jumlah Penarikan',
                'rules' => "required|is_natural_no_zero|greater_than_equal_to[{$minWithdrawal}]|less_than_equal_to[{$currentBalance}]", // Harus angka > 0, >= minimal, <= saldo
                'errors' => [
                    'required' => '{field} wajib diisi.',
                    'is_natural_no_zero' => '{field} harus berupa angka bulat positif.',
                    'greater_than_equal_to' => '{field} minimal Rp ' . number_format($minWithdrawal, 0, ',', '.'),
                    'less_than_equal_to' => 'Jumlah penarikan tidak boleh melebihi saldo Anda saat ini (Rp ' . number_format($currentBalance, 0, ',', '.') . ').'
                ]
            ]
        ];

        // Jalankan validasi
        if (!$this->validate($rules)) {
             log_message('debug', 'Withdrawal Validation Failed. Errors: ' . print_r($this->validator->getErrors(), true));
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $amount = $amountInt; // Jumlah yang valid

        // --- Pencegahan Race Condition ---
        // Ambil ulang saldo TEPAT SEBELUM update untuk memastikan saldo masih cukup
        $userBeforeUpdate = $this->userModel->find($this->currentUserId);
        $balanceBeforeUpdate = (int) ($userBeforeUpdate->balance ?? 0);

        if (!$userBeforeUpdate || $amount > $balanceBeforeUpdate ) {
            log_message('warning', 'Withdrawal failed due to insufficient balance (race condition?) for user ID: ' . $this->currentUserId . '. Requested: ' . $amount . ', Balance Before Update: ' . $balanceBeforeUpdate);
            return redirect()->back()->withInput()->with('error', 'Saldo tidak mencukupi. Silakan coba lagi.');
        }

        // Mulai transaksi database
        $db = \Config\Database::connect();
        $db->transBegin();

        // Kurangi saldo user (dengan kondisi WHERE balance >= amount)
        $newBalance = $balanceBeforeUpdate - $amount;
        $updateBalanceResult = $this->userModel->where('id', $this->currentUserId)
                                               ->where('balance >=', $amount) // Kondisi penting
                                               ->set(['balance' => $newBalance])
                                               ->update();
        $affectedRows = $db->affectedRows(); // Cek berapa baris yang terupdate

        $saveRequestResult = false; // Flag hasil simpan request
        $actionsSuccess = true; // Flag status keseluruhan

        // Jika saldo berhasil dikurangi (affectedRows > 0)
        if ($updateBalanceResult && $affectedRows > 0) {
            // Simpan detail bank saat ini ke dalam JSON
            $bankDetails = json_encode([
                'bank_name'      => $userBeforeUpdate->bank_name,
                'account_number' => $userBeforeUpdate->account_number,
                'account_name'   => $userBeforeUpdate->account_name,
            ]);

            // Siapkan data untuk tabel withdrawal_requests
            $requestData = [
                'user_id'        => $this->currentUserId,
                'amount'         => $amount,
                'status'         => 'pending', // Status awal
                'bank_details'   => $bankDetails, // Simpan JSON detail bank
            ];
            // Simpan request penarikan
            $saveRequestResult = $this->withdrawalRequestModel->save($requestData);
            if (!$saveRequestResult) {
                log_message('error', 'Failed to save withdrawal request for User ID: ' . $this->currentUserId . '. Model Errors: ' . print_r($this->withdrawalRequestModel->errors(), true));
                $actionsSuccess = false; // Set flag gagal
            }
        } else {
            // Jika pengurangan saldo gagal atau kondisi tidak terpenuhi
            log_message('warning', 'Withdrawal balance update failed or condition not met for user ID: ' . $this->currentUserId . '. Requested: ' . $amount . '. Affected Rows: ' . $affectedRows);
            $actionsSuccess = false; // Set flag gagal
        }

        // Cek status akhir transaksi database
         if ($actionsSuccess && $db->transStatus() !== false) {
             $db->transCommit(); // Konfirmasi transaksi
             log_message('info', 'Withdrawal request successful for user: ' . $this->currentUserId . ' amount: ' . $amount);
             return redirect()->route('dashboard.withdraw')->with('success', 'Permintaan penarikan sebesar Rp ' . number_format($amount, 0, ',', '.') . ' berhasil diajukan.');
         } else {
             $db->transRollback(); // Batalkan transaksi
             log_message('error', 'Transaction failed or rolled back during withdrawal request for user: ' . $this->currentUserId . ' amount: ' . $amount . ' DB Errors: UpdateBalance=' . ($updateBalanceResult ? 'OK' : 'Fail') . ', AffectedRows=' . $affectedRows . ', SaveRequest=' . ($saveRequestResult ? 'OK' : 'Fail') . print_r($db->error(), true));

             // Beri pesan error spesifik jika gagal karena saldo berubah (affectedRows = 0)
             if ($affectedRows === 0 && $updateBalanceResult) {
                return redirect()->back()->withInput()->with('error', 'Gagal memproses permintaan: Saldo mungkin telah berubah atau tidak mencukupi. Silakan cek saldo Anda dan coba lagi.');
             }
             // Pesan error generik jika masalah lain
             return redirect()->back()->withInput()->with('error', 'Gagal memproses permintaan penarikan karena masalah database. Saldo tidak dikurangi.');
         }
    }

    // Menampilkan riwayat transaksi penjualan
     public function transactions()
    {
        $perPage = 15; // Item per halaman
        $pagerService = Services::pager(); // Dapatkan service pager
        $currentPage = $this->request->getVar('page_transactions') ? (int) $this->request->getVar('page_transactions') : 1; // Halaman saat ini

        // Ambil data transaksi penjualan user ini dengan paginasi
        $transactions = $this->transactionModel
            ->select('transactions.*, products.product_name') // Ambil nama produk juga
            ->join('products', 'products.id = transactions.product_id', 'left') // Join tabel products
            ->where('transactions.user_id', $this->currentUserId) // Filter user ID
            ->where('transactions.transaction_type', 'product') // Hanya tipe 'product'
            ->orderBy('transactions.created_at', 'DESC') // Urutkan terbaru dulu
            ->paginate($perPage, 'transactions'); // Paginasi grup 'transactions'

        // Data untuk view
        $data = [
            'title'        => 'Riwayat Transaksi Penjualan',
            'user'         => $this->userModel->find($this->currentUserId),
            'transactions' => $transactions,
            'pager'        => $this->transactionModel->pager, // Objek pager
            'currentPage'  => $currentPage, // Info halaman
            'itemsPerPage' => $perPage, // Info item per halaman
        ];

        return view('dashboard/transactions', $data);
    }
}

