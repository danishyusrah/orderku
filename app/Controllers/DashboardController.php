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

    // ... (index, newProduct, createProduct, editProduct, updateProduct - SAMA) ...
    public function index()
    {
        $search = $this->request->getGet('search'); // Ambil query pencarian
        $perPage = 10; // Jumlah item per halaman

        $productQuery = $this->productModel
            ->where('user_id', $this->currentUserId);

        if (!empty($search)) {
            $productQuery->groupStart()
                ->like('product_name', $search)
                ->orLike('description', $search)
            ->groupEnd();
        }

        $products = $productQuery->orderBy('created_at', 'DESC')
                                 ->paginate($perPage, 'products'); // 'products' adalah nama grup paginasi

        $pager = $this->productModel->pager;
        $pager->setPath(route_to('dashboard'), 'products'); // Set base path dan grup
        if (!empty($search)) {
            $pager->setPath(route_to('dashboard') . '?search=' . urlencode($search), 'products');
        }

        $totalAvailableStock = 0; // Inisialisasi total stok
        foreach ($products as $product) {
            if ($product->order_type === 'auto') {
                 if ($product->has_variants) {
                    $totalStockObject = $this->productVariantModel
                        ->selectSum('stock', 'total_stock')
                        ->where('product_id', $product->id)
                        ->where('is_active', true)
                        ->first();
                    $stockCount = $totalStockObject->total_stock ?? 0;
                } else {
                    $stockCount = $this->productStockModel->getAvailableStockCountForNonVariant($product->id);
                }
                $product->available_stock = $stockCount;
                $totalAvailableStock += $stockCount;
            } else {
                $product->available_stock = null;
            }
            $product->icon_url = $product->icon_filename
                ? base_url('uploads/product_icons/' . $product->icon_filename)
                : 'https://placehold.co/40x40/374151/9ca3af?text=' . strtoupper(substr(esc($product->product_name), 0, 1)); // Placeholder
        }

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

    public function createProduct()
    {
        $data = $this->request->getPost([
            'product_name', 'description', 'price', 'order_type', 'target_url', 'is_active', 'has_variants', 'variants'
        ]);
        $iconFile = $this->request->getFile('product_icon');

        $rules = [
            'product_name' => 'required|max_length[255]',
            'order_type'   => 'required|in_list[manual,auto]',
            'price'        => 'permit_empty|numeric|greater_than_equal_to[0]',
            'product_icon' => 'permit_empty|uploaded[product_icon]|max_size[product_icon,1024]|is_image[product_icon]|mime_in[product_icon,image/jpg,image/jpeg,image/png,image/gif,image/webp]',
        ];
        $errors = [
            'product_icon' => [
                'uploaded' => 'Anda harus memilih file ikon.',
                'max_size' => 'Ukuran file ikon maksimal 1MB.',
                'is_image' => 'File yang diupload harus berupa gambar.',
                'mime_in'  => 'Format gambar yang didukung: JPG, JPEG, PNG, GIF, WebP.'
            ]
        ];

        $user = $this->userModel->find($this->currentUserId);
        if (!$user) {
             log_message('error', 'User not found in createProduct. User ID: ' . $this->currentUserId);
             return redirect()->route('logout');
        }

        $isAuto = $data['order_type'] === 'auto';
        $hasVariants = $isAuto && ($data['has_variants'] ?? 0) == 1;

        if ($isAuto && !$user->is_premium) {
            return redirect()->back()->withInput()->with('error', 'Fitur "Otomatis" hanya untuk user Premium. Silakan Upgrade.');
        }

        if (!$isAuto) {
             if (empty(trim((string) $this->request->getPost('target_url')))) {
                 return redirect()->back()->withInput()->with('errors', ['target_url' => 'Target URL (Link Whatsapp) wajib diisi untuk produk manual.']);
             }
             if (! filter_var($this->request->getPost('target_url'), FILTER_VALIDATE_URL)) {
                 return redirect()->back()->withInput()->with('errors', ['target_url' => 'Target URL harus berupa link yang valid.']);
             }
             $data['target_url'] = $this->request->getPost('target_url');
        } else {
             $data['target_url'] = null;
             if (!$hasVariants) {
                 if (empty($data['price']) || !is_numeric($data['price']) || $data['price'] <= 0) {
                      return redirect()->back()->withInput()->with('error', 'Produk "Otomatis" (Non-Varian) harus memiliki Harga lebih dari 0.');
                 }
             } else {
                 $validVariantExists = false;
                 if (!empty($data['variants']) && is_array($data['variants'])) {
                     foreach ($data['variants'] as $variant) {
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

        if (! $this->validate($rules, $errors)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $iconFilename = null;
        $uploadPath = FCPATH . 'uploads/product_icons/';
        if ($iconFile instanceof \CodeIgniter\HTTP\Files\UploadedFile && $iconFile->isValid() && !$iconFile->hasMoved()) {
            $iconFilename = $iconFile->getRandomName();
            if (!is_dir($uploadPath)) {
                @mkdir($uploadPath, 0777, true);
            }
            try {
                $iconFile->move($uploadPath, $iconFilename);
            } catch (\Exception $e) {
                 log_message('error', 'Icon move failed: ' . $e->getMessage());
                 return redirect()->back()->withInput()->with('error', 'Gagal mengupload ikon: ' . $e->getMessage());
            }
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        $saveData = [
            'user_id'       => $this->currentUserId,
            'product_name'  => $data['product_name'],
            'description'   => $data['description'],
            'order_type'    => $data['order_type'],
            'target_url'    => $data['target_url'],
            'is_active'     => isset($data['is_active']) ? 1 : 0,
            'icon_filename' => $iconFilename,
            'price'         => !$hasVariants ? ((!empty($data['price']) && is_numeric($data['price'])) ? $data['price'] : 0) : 0,
            'has_variants'  => $hasVariants ? 1 : 0,
        ];

        if (! $this->productModel->save($saveData)) {
            $db->transRollback();
            if ($iconFilename && file_exists($uploadPath . $iconFilename)) { @unlink($uploadPath . $iconFilename); }
            return redirect()->back()->withInput()->with('errors', $this->productModel->errors() ?: ['general' => 'Gagal menyimpan produk.']);
        }

        $productId = $this->productModel->getInsertID();

        if ($hasVariants && !empty($data['variants']) && is_array($data['variants'])) {
            $variantsToInsert = [];
            foreach ($data['variants'] as $variant) {
                 if (!empty($variant['name']) && !empty($variant['price']) && is_numeric($variant['price']) && $variant['price'] > 0) {
                     $variantsToInsert[] = [
                        'product_id' => $productId,
                        'name'       => $variant['name'],
                        'price'      => $variant['price'],
                        'stock'      => 0,
                        'is_active'  => 1,
                     ];
                 }
            }

            if (!empty($variantsToInsert)) {
                if (!$this->productVariantModel->insertBatch($variantsToInsert)) {
                    $db->transRollback();
                    if ($iconFilename && file_exists($uploadPath . $iconFilename)) { @unlink($uploadPath . $iconFilename); }
                    return redirect()->back()->withInput()->with('errors', $this->productVariantModel->errors() ?: ['general' => 'Gagal menyimpan varian produk.']);
                }
            }
        }

        if ($db->transStatus() === false) {
             $db->transRollback();
             if ($iconFilename && file_exists($uploadPath . $iconFilename)) { @unlink($uploadPath . $iconFilename); }
             return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan database saat membuat produk.');
        } else {
             $db->transCommit();
             return redirect()->route('dashboard')->with('success', 'Produk berhasil ditambahkan.');
        }
    }

     public function editProduct($id)
    {
        $product = $this->productModel->find($id);

        if (! $product || $product->user_id != $this->currentUserId) {
            return redirect()->route('dashboard')->with('error', 'Produk tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $variants = [];
        if ($product->has_variants) {
             $variants = $this->productVariantModel->where('product_id', $id)->orderBy('name', 'ASC')->findAll();
        }

        $product->icon_url = $product->icon_filename
            ? base_url('uploads/product_icons/' . $product->icon_filename)
            : null;

        $data = [
            'title'   => 'Edit Produk: ' . esc($product->product_name),
            'user'    => $this->userModel->find($this->currentUserId),
            'product' => $product,
            'variants' => $variants, // Kirim data varian ke view
            'action'  => route_to('product.update', $id), // URL action form update
        ];
        return view('dashboard/product_form', $data);
    }

    public function updateProduct($id)
    {
        $product = $this->productModel->find($id);

        if (! $product || $product->user_id != $this->currentUserId) {
            return redirect()->route('dashboard')->with('error', 'Aksi tidak diizinkan.');
        }

        $data = $this->request->getPost([
            'product_name', 'description', 'price', 'order_type', 'target_url', 'is_active', 'has_variants', 'variants', 'deleted_variants'
        ]);
        $iconFile = $this->request->getFile('product_icon');

         $rules = [
            'product_name' => 'required|max_length[255]',
            'order_type'   => 'required|in_list[manual,auto]',
            'price'        => 'permit_empty|numeric|greater_than_equal_to[0]',
            'product_icon' => 'permit_empty|is_image[product_icon]|mime_in[product_icon,image/jpg,image/jpeg,image/png,image/gif,image/webp]|max_size[product_icon,1024]',
        ];
         $errors = [
            'product_icon' => [
                'max_size' => 'Ukuran file ikon maksimal 1MB.',
                'is_image' => 'File yang diupload harus berupa gambar.',
                'mime_in'  => 'Format gambar yang didukung: JPG, JPEG, PNG, GIF, WebP.'
            ]
        ];

        $user = $this->userModel->find($this->currentUserId);
         if (!$user) {
             return redirect()->route('logout');
        }

        $isAuto = $data['order_type'] === 'auto';
        $hasVariants = $isAuto && ($data['has_variants'] ?? 0) == 1;

         if ($isAuto && !$user->is_premium) {
            return redirect()->back()->withInput()->with('error', 'Fitur "Otomatis" hanya untuk user Premium. Silakan Upgrade.');
        }

        if (!$isAuto) {
             if (empty(trim((string) $this->request->getPost('target_url')))) {
                 return redirect()->back()->withInput()->with('errors', ['target_url' => 'Target URL (Link Whatsapp) wajib diisi untuk produk manual.']);
             }
             if (! filter_var($this->request->getPost('target_url'), FILTER_VALIDATE_URL)) {
                 return redirect()->back()->withInput()->with('errors', ['target_url' => 'Target URL harus berupa link yang valid.']);
             }
             $data['target_url'] = $this->request->getPost('target_url');
        } else {
             $data['target_url'] = null;
             if (!$hasVariants) {
                 if (empty($data['price']) || !is_numeric($data['price']) || $data['price'] <= 0) {
                      return redirect()->back()->withInput()->with('error', 'Produk "Otomatis" (Non-Varian) harus memiliki Harga lebih dari 0.');
                 }
             } else {
                 $validVariantExists = false;
                 $deletedIds = isset($data['deleted_variants']) ? (is_array($data['deleted_variants']) ? $data['deleted_variants'] : [$data['deleted_variants']]) : [];
                 if (!empty($data['variants']) && is_array($data['variants'])) {
                     foreach ($data['variants'] as $variant) {
                         $variantIdToCheck = $variant['id'] ?? null;
                         if ($variantIdToCheck && in_array($variantIdToCheck, $deletedIds)) {
                             continue;
                         }
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

        if (! $this->validate($rules, $errors)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        $uploadPath = FCPATH . 'uploads/product_icons/';
        $iconFilename = $product->icon_filename;
        $newFileUploaded = false;

        if ($iconFile instanceof \CodeIgniter\HTTP\Files\UploadedFile && $iconFile->isValid() && !$iconFile->hasMoved()) {
            $newFileUploaded = true;
            $oldIconPath = $iconFilename ? $uploadPath . $iconFilename : null;

            $iconFilename = $iconFile->getRandomName();
            if (!is_dir($uploadPath)) { @mkdir($uploadPath, 0777, true); }
             try {
                $iconFile->move($uploadPath, $iconFilename);
                if ($oldIconPath && file_exists($oldIconPath)) {
                    @unlink($oldIconPath);
                }
             } catch (\Exception $e) {
                 log_message('error', 'Icon move failed during update: ' . $e->getMessage());
                 $db->transRollback();
                 return redirect()->back()->withInput()->with('error', 'Gagal mengupload ikon baru: ' . $e->getMessage());
            }
        }

        $updateData = [
            'product_name'  => $data['product_name'],
            'description'   => $data['description'],
            'order_type'    => $data['order_type'],
            'target_url'    => $data['target_url'],
            'is_active'     => isset($data['is_active']) ? 1 : 0,
            'icon_filename' => $iconFilename,
            'has_variants'  => $hasVariants ? 1 : 0,
            'price'         => !$hasVariants ? ((!empty($data['price']) && is_numeric($data['price'])) ? $data['price'] : 0) : 0,
        ];

        if (! $this->productModel->update($id, $updateData)) {
            $db->transRollback();
             if ($newFileUploaded && $iconFilename && file_exists($uploadPath . $iconFilename)) { @unlink($uploadPath . $iconFilename); }
            return redirect()->back()->withInput()->with('errors', $this->productModel->errors() ?: ['general' => 'Gagal memperbarui produk.']);
        }

        $variantsToInsert = [];
        $variantsToUpdate = [];
        $processedVariantIds = [];

        if ($hasVariants && !empty($data['variants']) && is_array($data['variants'])) {
             foreach ($data['variants'] as $variantInput) {
                 if (empty($variantInput['name']) || empty($variantInput['price']) || !is_numeric($variantInput['price']) || $variantInput['price'] <= 0) continue;

                 $variantData = [
                     'product_id' => $id,
                     'name'       => $variantInput['name'],
                     'price'      => $variantInput['price'],
                     'is_active'  => $variantInput['is_active'] ?? 1,
                 ];

                 if (isset($variantInput['id']) && !empty($variantInput['id'])) {
                     $variantData['id'] = $variantInput['id'];
                     $variantsToUpdate[] = $variantData;
                     $processedVariantIds[] = $variantInput['id'];
                 } else {
                     $variantData['stock'] = 0;
                     $variantsToInsert[] = $variantData;
                 }
             }

             if (!empty($variantsToUpdate)) {
                 if (!$this->productVariantModel->updateBatch($variantsToUpdate, 'id')) {
                     $db->transRollback();
                     return redirect()->back()->withInput()->with('error', 'Gagal memperbarui varian yang ada.');
                 }
             }
             if (!empty($variantsToInsert)) {
                  if (!$this->productVariantModel->insertBatch($variantsToInsert)) {
                     $db->transRollback();
                     return redirect()->back()->withInput()->with('error', 'Gagal menyimpan varian baru.');
                 }
             }

             if (!empty($data['deleted_variants'])) {
                 $deletedIds = is_array($data['deleted_variants']) ? $data['deleted_variants'] : [$data['deleted_variants']];
                 $idsToDelete = array_diff($deletedIds, $processedVariantIds);

                 if (!empty($idsToDelete)) {
                     if (!$this->productVariantModel->where('product_id', $id)->whereIn('id', $idsToDelete)->delete()) {
                         $db->transRollback();
                         return redirect()->back()->withInput()->with('error', 'Gagal menghapus varian lama.');
                     }
                 }
             }

        } elseif ($product->has_variants && !$hasVariants) {
            if (!$this->productVariantModel->where('product_id', $id)->delete()) {
                 $db->transRollback();
                 return redirect()->back()->withInput()->with('error', 'Gagal menghapus varian saat mengubah tipe produk.');
            }
        }

        if ($db->transStatus() === false) {
             $db->transRollback();
             if ($newFileUploaded && $iconFilename && file_exists($uploadPath . $iconFilename)) { @unlink($uploadPath . $iconFilename); }
             return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan database saat memperbarui produk.');
        } else {
             $db->transCommit();
             return redirect()->route('dashboard')->with('success', 'Produk berhasil diperbarui.');
        }
    }

    /**
     * Menghapus produk
     */
    public function deleteProduct($id)
    {
        $product = $this->productModel->find($id);

        // Cek kepemilikan
        if (! $product || $product->user_id != $this->currentUserId) {
            return redirect()->route('dashboard')->with('error', 'Aksi tidak diizinkan.');
        }

        $db = \Config\Database::connect();
        $db->transBegin(); // Mulai transaksi

        $deleteSuccess = $this->productModel->delete($id);

        if ($deleteSuccess) {
            // Hapus file ikon produk jika produk berhasil dihapus dari DB
            $uploadPath = FCPATH . 'uploads/product_icons/';
            if ($product->icon_filename && file_exists($uploadPath . $product->icon_filename)) {
                @unlink($uploadPath . $product->icon_filename);
            }
            // Bagian yang error (menghapus logo user) sudah dihapus
            $db->transCommit(); // Konfirmasi transaksi
            return redirect()->route('dashboard')->with('success', 'Produk berhasil dihapus.');
        } else {
             $db->transRollback(); // Batalkan jika gagal hapus produk
             return redirect()->route('dashboard')->with('error', 'Gagal menghapus produk.');
        }
    }


    public function upgradePage()
    {
        $data = [
            'title' => 'Upgrade ke Premium',
            'user'  => $this->userModel->find($this->currentUserId)
        ];
        return view('dashboard/upgrade_page', $data);
    }

    public function manageStock($productId)
    {
        $product = $this->productModel->find($productId);

        if (! $product || $product->user_id != $this->currentUserId || $product->order_type !== 'auto') {
            return redirect()->route('dashboard')->with('error', 'Aksi tidak diizinkan atau produk bukan tipe otomatis.');
        }

        if ($product->has_variants) {
            $variants = $this->productVariantModel->where('product_id', $productId)->orderBy('name', 'ASC')->findAll();
            $data = [
                 'title'   => 'Kelola Stok: ' . esc($product->product_name),
                 'product' => $product,
                 'variants' => $variants,
            ];
            return view('dashboard/manage_variant_stock', $data);
        } else {
            $perPageStock = 20;
            $pagerService = Services::pager();
            $currentPageStock = $this->request->getVar('page_stock') ? (int) $this->request->getVar('page_stock') : 1;
            $data = [
                'title'   => 'Kelola Stok: ' . esc($product->product_name),
                'user'    => $this->userModel->find($this->currentUserId),
                'product' => $product,
                'stocks'  => $this->productStockModel
                                ->where('product_id', $productId)
                                ->where('variant_id', null)
                                ->orderBy('created_at', 'DESC')
                                ->paginate($perPageStock, 'stock'),
                'pager'   => $this->productStockModel->pager,
                'currentPage' => $currentPageStock,
                'perPage'     => $perPageStock,
            ];
            return view('dashboard/manage_stock', $data);
        }
    }

    public function manageVariantStockItems($productId, $variantId)
    {
        $product = $this->productModel->find($productId);
        $variant = $this->productVariantModel->find($variantId);

        if (! $product || $product->user_id != $this->currentUserId || !$variant || $variant->product_id != $productId) {
            return redirect()->route('dashboard')->with('error', 'Varian atau produk tidak ditemukan.');
        }

        $perPageStock = 20;
        $currentPageStock = $this->request->getVar('page_stock') ? (int) $this->request->getVar('page_stock') : 1;

        $data = [
            'title'      => 'Kelola Item Stok: ' . esc($variant->name),
            'product'    => $product,
            'variant'    => $variant,
            'stocks'     => $this->productStockModel
                                ->where('variant_id', $variantId)
                                ->orderBy('created_at', 'DESC')
                                ->paginate($perPageStock, 'stock'),
            'pager'      => $this->productStockModel->pager,
            'currentPage' => $currentPageStock,
            'perPage'     => $perPageStock,
        ];

        return view('dashboard/manage_variant_stock_items', $data);
    }

    /**
     * Tambah Stok (Produk Non-Varian) - UPDATED FOR JSON
     */
    public function addStock($productId)
    {
        $product = $this->productModel->find($productId);

        if (! $product || $product->user_id != $this->currentUserId || $product->order_type !== 'auto' || $product->has_variants) {
            return redirect()->route('dashboard')->with('error', 'Aksi tidak diizinkan atau produk bervarian.');
        }

        $stockInput = $this->request->getPost('stock_data');
        if (empty(trim($stockInput))) {
            return redirect()->back()->with('error', 'Data stok tidak boleh kosong.');
        }

        $stockLines = array_filter(array_map('trim', explode("\n", $stockInput)));
        if (empty($stockLines)) {
            return redirect()->back()->with('error', 'Tidak ada data stok valid untuk ditambahkan.');
        }

        $batchData = [];
        $now = date('Y-m-d H:i:s');
        $lineErrors = []; // Untuk menyimpan error per baris

        foreach ($stockLines as $index => $line) {
            if (empty($line)) continue;

            $jsonData = json_decode($line);
            $lineNumber = $index + 1;

            if (json_last_error() !== JSON_ERROR_NONE) {
                $lineErrors[] = "Baris {$lineNumber}: Format JSON tidak valid (" . json_last_error_msg() . "). Input: " . substr($line, 0, 50) . "...";
                continue;
            }
            if (!is_object($jsonData)) {
                 $lineErrors[] = "Baris {$lineNumber}: Data harus berupa objek JSON. Input: " . substr($line, 0, 50) . "...";
                 continue;
            }
            if (!isset($jsonData->email) || empty(trim($jsonData->email))) {
                 $lineErrors[] = "Baris {$lineNumber}: Field 'email' wajib diisi.";
                 continue;
            }
             if (!isset($jsonData->password) || empty(trim($jsonData->password))) {
                 $lineErrors[] = "Baris {$lineNumber}: Field 'password' wajib diisi.";
                 continue;
            }

            $batchData[] = [
                'product_id' => $productId,
                'variant_id' => null,
                'stock_data' => $line,
                'is_used'    => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($lineErrors)) {
            return redirect()->back()->withInput()->with('error', 'Gagal menambahkan stok:<ul class="list-disc pl-5 text-sm">' . implode('', array_map(fn($err) => "<li>{$err}</li>", $lineErrors)) . '</ul>');
        }
        if (empty($batchData)) {
             return redirect()->back()->with('error', 'Tidak ada data stok valid untuk ditambahkan setelah validasi.');
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        $inserted = $this->productStockModel->insertBatch($batchData);

        if ($inserted === false || $db->transStatus() === false) {
             $db->transRollback();
            log_message('error', 'Gagal insert batch stok (non-varian) untuk produk ID: ' . $productId . '. Error DB: ' . print_r($db->error(), true));
            return redirect()->back()->withInput()->with('error', 'Gagal menambahkan stok karena kesalahan database.');
        } else {
             $db->transCommit();
            return redirect()->route('product.stock.manage', [$productId])->with('success', count($batchData) . ' item stok berhasil ditambahkan.');
        }
    }

    /**
     * Tambah Item Stok Unik untuk Varian Tertentu (NEW) - UPDATED FOR JSON
     */
    public function addVariantStockItem($productId, $variantId)
    {
        $product = $this->productModel->find($productId);
        $variant = $this->productVariantModel->find($variantId);

        if (! $product || $product->user_id != $this->currentUserId || !$variant || $variant->product_id != $productId) {
            return redirect()->route('dashboard')->with('error', 'Varian atau produk tidak ditemukan.');
        }

        $stockInput = $this->request->getPost('stock_data');
        if (empty(trim($stockInput))) {
            return redirect()->back()->with('error', 'Data stok tidak boleh kosong.');
        }

        $stockLines = array_filter(array_map('trim', explode("\n", $stockInput)));
        if (empty($stockLines)) {
            return redirect()->back()->with('error', 'Tidak ada data stok valid untuk ditambahkan.');
        }

        $batchData = [];
        $now = date('Y-m-d H:i:s');
        $lineErrors = [];

        foreach ($stockLines as $index => $line) {
             if (empty($line)) continue;

            $jsonData = json_decode($line);
            $lineNumber = $index + 1;

            if (json_last_error() !== JSON_ERROR_NONE) {
                $lineErrors[] = "Baris {$lineNumber}: Format JSON tidak valid (" . json_last_error_msg() . "). Input: " . substr($line, 0, 50) . "...";
                continue;
            }
            if (!is_object($jsonData)) {
                 $lineErrors[] = "Baris {$lineNumber}: Data harus berupa objek JSON. Input: " . substr($line, 0, 50) . "...";
                 continue;
            }
            if (!isset($jsonData->email) || empty(trim($jsonData->email))) {
                 $lineErrors[] = "Baris {$lineNumber}: Field 'email' wajib diisi.";
                 continue;
            }
             if (!isset($jsonData->password) || empty(trim($jsonData->password))) {
                 $lineErrors[] = "Baris {$lineNumber}: Field 'password' wajib diisi.";
                 continue;
            }

            $batchData[] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'stock_data' => $line,
                'is_used'    => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($lineErrors)) {
            return redirect()->back()->withInput()->with('error', 'Gagal menambahkan stok:<ul class="list-disc pl-5 text-sm">' . implode('', array_map(fn($err) => "<li>{$err}</li>", $lineErrors)) . '</ul>');
        }
        if (empty($batchData)) {
            return redirect()->back()->with('error', 'Tidak ada data stok valid untuk ditambahkan setelah validasi.');
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        $inserted = $this->productStockModel->insertBatch($batchData);

        if ($inserted === false || $db->transStatus() === false) {
             $db->transRollback();
             log_message('error', 'Gagal insert batch stok (varian) untuk produk ID: ' . $productId . ', Varian ID: ' . $variantId . '. Error DB: ' . print_r($db->error(), true));
            return redirect()->back()->withInput()->with('error', 'Gagal menambahkan item stok karena kesalahan database.');
        } else {
             if(!$this->productVariantModel->synchronizeStock($variantId, $this->productStockModel)){
                 $db->transRollback();
                 log_message('error', 'Gagal sinkronisasi stok setelah insert batch untuk Varian ID: ' . $variantId);
                 return redirect()->back()->withInput()->with('error', 'Gagal sinkronisasi stok varian setelah menambah item.');
             }

             $db->transCommit();
            return redirect()->route('product.variant.stock.items', [$productId, $variantId])->with('success', count($batchData) . ' item stok berhasil ditambahkan.');
        }
    }

    public function deleteStock($productId, $stockId)
    {
        $product = $this->productModel->find($productId);
        $stock = $this->productStockModel->find($stockId);

        if (! $product || $product->user_id != $this->currentUserId || !$stock || $stock->product_id != $productId || $stock->variant_id !== null) {
            return redirect()->route('dashboard')->with('error', 'Aksi tidak diizinkan.');
        }

        if ($stock->is_used) {
            return redirect()->route('product.stock.manage', [$productId])->with('error', 'Tidak dapat menghapus stok yang sudah terpakai.');
        }

        if ($this->productStockModel->delete($stockId)) {
            return redirect()->route('product.stock.manage', [$productId])->with('success', 'Item stok berhasil dihapus.');
        } else {
            return redirect()->route('product.stock.manage', [$productId])->with('error', 'Gagal menghapus item stok.');
        }
    }

     public function deleteVariantStockItem($productId, $variantId, $stockId)
    {
        $product = $this->productModel->find($productId);
        $variant = $this->productVariantModel->find($variantId);
        $stock = $this->productStockModel->find($stockId);

        if (! $product || $product->user_id != $this->currentUserId || !$variant || $variant->product_id != $productId || !$stock || $stock->variant_id != $variantId) {
            return redirect()->route('dashboard')->with('error', 'Aksi tidak diizinkan.');
        }

        if ($stock->is_used) {
            return redirect()->route('product.variant.stock.items', [$productId, $variantId])->with('error', 'Tidak dapat menghapus item stok yang sudah terpakai.');
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        if ($this->productStockModel->delete($stockId)) {
             if(!$this->productVariantModel->synchronizeStock($variantId, $this->productStockModel)){
                 $db->transRollback();
                 log_message('error', 'Gagal sinkronisasi stok setelah delete item untuk Varian ID: ' . $variantId);
                 return redirect()->route('product.variant.stock.items', [$productId, $variantId])->with('error', 'Gagal sinkronisasi stok varian setelah menghapus item.');
             }

            $db->transCommit();
            return redirect()->route('product.variant.stock.items', [$productId, $variantId])->with('success', 'Item stok berhasil dihapus.');
        } else {
            $db->transRollback();
            log_message('error', 'Gagal delete item stok ID: ' . $stockId . '. Error DB: ' . print_r($db->error(), true));
            return redirect()->route('product.variant.stock.items', [$productId, $variantId])->with('error', 'Gagal menghapus item stok.');
        }
    }


     public function profileSettings()
     {
         $user = $this->userModel->find($this->currentUserId);
         $logoUrl = $user->logo_filename
            ? base_url('uploads/logos/' . $user->logo_filename)
            : 'https://ui-avatars.com/api/?name=' . urlencode($user->store_name ?: $user->username) . '&background=4f46e5&color=ffffff&size=64&bold=true';

         $data = [
             'title' => 'Pengaturan',
             'user'  => $user,
             'logoUrl' => $logoUrl,
             'actionProfile' => route_to('dashboard.profile.update'),
             'actionBank' => route_to('dashboard.bank.update'),
             'actionMidtrans' => route_to('dashboard.midtrans.update'),
             'actionPassword' => route_to('dashboard.password.update'),
         ];
         return view('dashboard/profile_settings', $data);
     }

     public function updateProfile()
     {
         $user = $this->userModel->find($this->currentUserId);

         $rules = [
             'username'      => "required|alpha_numeric|min_length[3]|max_length[30]|is_unique[users.username,id,{$this->currentUserId}]",
             'store_name'    => 'required|string|max_length[100]',
             'profile_subtitle' => 'permit_empty|string|max_length[150]',
             'whatsapp_link' => 'permit_empty|valid_url|max_length[255]',
             'logo'          => 'permit_empty|uploaded[logo]|max_size[logo,1024]|is_image[logo]|mime_in[logo,image/jpg,image/jpeg,image/png,image/gif,image/webp]',
         ];
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

         if (!$this->validate($rules, $errors)) {
             session()->setFlashdata('errorsProfile', $this->validator->getErrors());
             return redirect()->back()->withInput();
         }

         $data = [
             'username'      => trim($this->request->getPost('username') ?? ''),
             'store_name'    => trim($this->request->getPost('store_name') ?? ''),
             'profile_subtitle' => trim($this->request->getPost('profile_subtitle') ?? '') ?: null,
             'whatsapp_link' => trim($this->request->getPost('whatsapp_link') ?? '') ?: null,
         ];

         $logoFile = $this->request->getFile('logo');
         $newLogoFilename = null;
         $uploadPath = FCPATH . 'uploads/logos/';
         $newFileUploaded = false;

         if ($logoFile instanceof \CodeIgniter\HTTP\Files\UploadedFile && $logoFile->isValid() && !$logoFile->hasMoved()) {
             $newFileUploaded = true;
             $oldLogoPath = $user->logo_filename ? $uploadPath . $user->logo_filename : null;
             $newLogoFilename = $logoFile->getRandomName();

             if (!is_dir($uploadPath)) { @mkdir($uploadPath, 0777, true); }
             try {
                 $logoFile->move($uploadPath, $newLogoFilename);
                 if ($oldLogoPath && file_exists($oldLogoPath)) {
                     @unlink($oldLogoPath);
                 }
                 $data['logo_filename'] = $newLogoFilename;
             } catch (\Exception $e) {
                 log_message('error', 'Logo move failed: ' . $e->getMessage());
                 return redirect()->back()->withInput()->with('errorProfile', 'Gagal mengupload logo baru: ' . $e->getMessage());
             }
         }

         if ($this->userModel->update($this->currentUserId, $data)) {
             if (isset($data['username']) && $data['username'] !== $user->username) {
                 session()->set('username', $data['username']);
             }
              log_message('info', 'Profile updated for User ID: ' . $this->currentUserId);
             return redirect()->route('dashboard.settings')->with('successProfile', 'Informasi profil berhasil diperbarui.');
         } else {
             log_message('error', 'Profile update failed for User ID: ' . $this->currentUserId . ' Errors: ' . print_r($this->userModel->errors(), true));
              if ($newFileUploaded && $newLogoFilename && file_exists($uploadPath . $newLogoFilename)) { @unlink($uploadPath . $newLogoFilename); }
             session()->setFlashdata('errorProfile', 'Gagal memperbarui informasi profil.');
             return redirect()->back()->withInput();
         }
     }


     public function updateBank()
     {
        $rules = [
             'bank_name' => 'permit_empty|string|max_length[100]',
             'account_number' => 'permit_empty|numeric|max_length[50]',
             'account_name' => 'permit_empty|string|max_length[150]',
        ];

         if (!$this->validate($rules)) {
             session()->setFlashdata('errorsBank', $this->validator->getErrors());
             return redirect()->back()->withInput();
         }

         $data = [
             'bank_name' => trim($this->request->getPost('bank_name') ?? '') ?: null,
             'account_number' => trim($this->request->getPost('account_number') ?? '') ?: null,
             'account_name' => trim($this->request->getPost('account_name') ?? '') ?: null,
         ];

         if ($this->userModel->update($this->currentUserId, $data)) {
             log_message('info', 'Bank details updated for User ID: ' . $this->currentUserId);
             return redirect()->route('dashboard.settings')->with('successBank', 'Informasi penarikan dana berhasil diperbarui.');
         } else {
              log_message('error', 'Bank details update failed for User ID: ' . $this->currentUserId . ' Errors: ' . print_r($this->userModel->errors(), true));
             session()->setFlashdata('errorBank', 'Gagal memperbarui informasi penarikan dana.');
             return redirect()->back()->withInput();
         }
     }

    public function updateMidtransKeys()
     {
         $rules = [
             'midtrans_server_key' => 'permit_empty|string|max_length[255]',
             'midtrans_client_key' => 'permit_empty|string|max_length[255]',
         ];

        if (!$this->validate($rules)) {
             session()->setFlashdata('errorsMidtrans', $this->validator->getErrors());
             return redirect()->back()->withInput();
         }

        $serverKey = trim($this->request->getPost('midtrans_server_key') ?? '');
         $clientKey = trim($this->request->getPost('midtrans_client_key') ?? '');

        $data = [
             'midtrans_server_key' => $serverKey ?: null,
             'midtrans_client_key' => $clientKey ?: null,
         ];

        if ($this->userModel->update($this->currentUserId, $data)) {
              log_message('info', 'Midtrans keys updated for User ID: ' . $this->currentUserId);
             return redirect()->route('dashboard.settings')->with('successMidtrans', 'API Key Midtrans berhasil diperbarui.');
         } else {
              log_message('error', 'Midtrans keys update failed for User ID: ' . $this->currentUserId . ' Errors: ' . print_r($this->userModel->errors(), true));
              session()->setFlashdata('errorMidtrans', 'Gagal memperbarui API Key Midtrans.');
             return redirect()->back()->withInput();
         }
     }

    public function updateTripayKeys()
    {
        $rules = [
            'tripay_api_key'       => 'permit_empty|string|max_length[255]',
            'tripay_private_key'   => 'permit_empty|string|max_length[255]',
            'tripay_merchant_code' => 'permit_empty|string|max_length[64]',
        ];
        if (!$this->validate($rules)) {
            session()->setFlashdata('errorsTripay', $this->validator->getErrors());
            return redirect()->back()->withInput();
        }

        $data = [
            'tripay_api_key'       => trim($this->request->getPost('tripay_api_key') ?? '') ?: null,
            'tripay_private_key'   => trim($this->request->getPost('tripay_private_key') ?? '') ?: null,
            'tripay_merchant_code' => trim($this->request->getPost('tripay_merchant_code') ?? '') ?: null,
        ];

        if ($this->userModel->update($this->currentUserId, $data)) {
            log_message('info', 'Tripay keys updated for User ID: '.$this->currentUserId);
            return redirect()->route('dashboard.settings')->with('successTripay', 'API Key Tripay berhasil diperbarui.');
        }

        log_message('error', 'Tripay keys update failed for User ID: '.$this->currentUserId);
        return redirect()->route('dashboard.settings')->with('errorTripay', 'Gagal menyimpan API Key Tripay.');
    }

    public function updateGatewayPreference()
    {
        $choice = $this->request->getPost('gateway_active');
        if (!in_array($choice, ['system','midtrans','tripay'], true)) {
            return redirect()->back()->with('errorGateway', 'Pilihan gateway tidak valid.');
        }

        // Enforce “hanya satu yang aktif”: cukup simpan enum
        if ($this->userModel->update($this->currentUserId, ['gateway_active' => $choice])) {
            return redirect()->route('dashboard.settings')->with('successGateway', 'Preferensi gateway diperbarui.');
        }
        return redirect()->route('dashboard.settings')->with('errorGateway', 'Gagal memperbarui preferensi gateway.');
    }


     public function updatePassword()
     {
        $rules = [
            'current_password' => ['label' => 'Password Saat Ini', 'rules' => 'required'],
            'new_password' => ['label' => 'Password Baru', 'rules' => 'required|min_length[8]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/]', 'errors' => ['regex_match' => '{field} harus mengandung setidaknya satu huruf kecil, satu huruf besar, dan satu angka.']],
            'confirm_password' => ['label' => 'Konfirmasi Password Baru', 'rules' => 'required|matches[new_password]', 'errors' => ['matches' => '{field} tidak cocok dengan Password Baru.']],
        ];

         $user = $this->userModel->find($this->currentUserId);
         if (!$user) {
             log_message('error', 'User not found for password change. User ID: ' . $this->currentUserId);
             return redirect()->route('logout');
         }

         $currentPasswordInput = $this->request->getPost('current_password');
         if (!password_verify((string)$currentPasswordInput, $user->password_hash)) {
              session()->setFlashdata('errorsPassword', ['current_password' => 'Password Saat Ini salah.']);
             return redirect()->back()->withInput();
         }

         if (!$this->validate($rules)) {
             session()->setFlashdata('errorsPassword', $this->validator->getErrors());
             return redirect()->back()->withInput();
         }

         $newPasswordHash = password_hash($this->request->getPost('new_password'), PASSWORD_DEFAULT);

         if ($this->userModel->update($this->currentUserId, ['password_hash' => $newPasswordHash])) {
              log_message('info', 'Password changed successfully for User ID: ' . $this->currentUserId);
             return redirect()->route('dashboard.settings')->with('successPassword', 'Password berhasil diubah.');
         } else {
             log_message('error', 'Password update failed for User ID: ' . $this->currentUserId . ' Errors: ' . print_r($this->userModel->errors(), true));
             session()->setFlashdata('errorPassword', 'Gagal mengubah password.');
             return redirect()->back()->withInput();
         }
     }

     public function withdrawPage()
     {
         $user = $this->userModel->find($this->currentUserId);
         $hasBankDetails = !empty($user->bank_name) && !empty($user->account_number) && !empty($user->account_name);

         $perPageWithdraw = 15;
         $pagerService = Services::pager();
         $currentPageWithdraw = $this->request->getVar('page_withdraw') ? (int) $this->request->getVar('page_withdraw') : 1;

         $withdrawals = $this->withdrawalRequestModel
                             ->select('withdrawal_requests.*, users.username as user_username')
                             ->join('users', 'users.id = withdrawal_requests.user_id', 'left')
                             ->where('withdrawal_requests.user_id', $this->currentUserId)
                             ->orderBy('withdrawal_requests.created_at', 'DESC')
                             ->paginate($perPageWithdraw, 'withdraw');

         if (!empty($withdrawals)) {
            foreach ($withdrawals as $wd) {
                if (isset($wd->bank_details)) {
                    $decoded = json_decode($wd->bank_details);
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

         $data = [
             'title'             => 'Penarikan Dana',
             'user'              => $user,
             'hasBankDetails'    => $hasBankDetails,
             'withdrawals'       => $withdrawals,
             'pager'             => $this->withdrawalRequestModel->pager,
             'currentPage'       => $currentPageWithdraw,
             'itemsPerPage'      => $perPageWithdraw,
         ];
         return view('dashboard/withdraw', $data);
     }

    public function requestWithdrawal()
    {
        $user = $this->userModel->find($this->currentUserId);

        if (empty($user->bank_name) || empty($user->account_number) || empty($user->account_name)) {
            return redirect()->route('dashboard.settings')->with('error', 'Lengkapi informasi rekening bank Anda terlebih dahulu di Pengaturan Profil.');
        }

        $currentBalance = (int) ($user->balance ?? 0);
        $amountInput = $this->request->getPost('amount');
        $amountInt = filter_var($amountInput, FILTER_VALIDATE_INT);
        if ($amountInt === false) $amountInt = 0;

        $minWithdrawal = 10000;

        log_message('debug', 'Withdrawal Request - Amount Input: "' . $amountInput . '" (Type: ' . gettype($amountInput) . ') | Int Value: ' . $amountInt);
        log_message('debug', 'Withdrawal Request - Current Balance for Rule: ' . $currentBalance . ' (Type: ' . gettype($currentBalance) . ')');

        $rules = [
            'amount' => [
                'label' => 'Jumlah Penarikan',
                'rules' => "required|is_natural_no_zero|greater_than_equal_to[{$minWithdrawal}]|less_than_equal_to[{$currentBalance}]",
                'errors' => [
                    'required' => '{field} wajib diisi.',
                    'is_natural_no_zero' => '{field} harus berupa angka bulat positif.',
                    'greater_than_equal_to' => '{field} minimal Rp ' . number_format($minWithdrawal, 0, ',', '.'),
                    'less_than_equal_to' => 'Jumlah penarikan tidak boleh melebihi saldo Anda saat ini (Rp ' . number_format($currentBalance, 0, ',', '.') . ').'
                ]
            ]
        ];

        if (!$this->validate($rules)) {
             log_message('debug', 'Withdrawal Validation Failed. Errors: ' . print_r($this->validator->getErrors(), true));
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $amount = $amountInt;

        $userBeforeUpdate = $this->userModel->find($this->currentUserId);
        $balanceBeforeUpdate = (int) ($userBeforeUpdate->balance ?? 0);

        if (!$userBeforeUpdate || $amount > $balanceBeforeUpdate ) {
            log_message('warning', 'Withdrawal failed due to insufficient balance (race condition?) for user ID: ' . $this->currentUserId . '. Requested: ' . $amount . ', Balance Before Update: ' . $balanceBeforeUpdate);
            return redirect()->back()->withInput()->with('error', 'Saldo tidak mencukupi. Silakan coba lagi.');
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        $newBalance = $balanceBeforeUpdate - $amount;
        $updateBalanceResult = $this->userModel->where('id', $this->currentUserId)
                                               ->where('balance >=', $amount)
                                               ->set(['balance' => $newBalance])
                                               ->update();
        $affectedRows = $db->affectedRows();

        $saveRequestResult = false;
        $actionsSuccess = true;
        if ($updateBalanceResult && $affectedRows > 0) {
            $bankDetails = json_encode([
                'bank_name'      => $userBeforeUpdate->bank_name,
                'account_number' => $userBeforeUpdate->account_number,
                'account_name'   => $userBeforeUpdate->account_name,
            ]);

            $requestData = [
                'user_id'        => $this->currentUserId,
                'amount'         => $amount,
                'status'         => 'pending',
                'bank_details'   => $bankDetails,
            ];
            $saveRequestResult = $this->withdrawalRequestModel->save($requestData);
            if (!$saveRequestResult) {
                log_message('error', 'Failed to save withdrawal request for User ID: ' . $this->currentUserId . '. Model Errors: ' . print_r($this->withdrawalRequestModel->errors(), true));
                $actionsSuccess = false;
            }
        } else {
            log_message('warning', 'Withdrawal balance update failed or condition not met for user ID: ' . $this->currentUserId . '. Requested: ' . $amount . '. Affected Rows: ' . $affectedRows);
            $actionsSuccess = false;
        }

         if ($actionsSuccess && $db->transStatus() !== false) {
             $db->transCommit();
             log_message('info', 'Withdrawal request successful for user: ' . $this->currentUserId . ' amount: ' . $amount);
             return redirect()->route('dashboard.withdraw')->with('success', 'Permintaan penarikan sebesar Rp ' . number_format($amount, 0, ',', '.') . ' berhasil diajukan.');
         } else {
             $db->transRollback();
             log_message('error', 'Transaction failed or rolled back during withdrawal request for user: ' . $this->currentUserId . ' amount: ' . $amount . ' DB Errors: UpdateBalance=' . ($updateBalanceResult ? 'OK' : 'Fail') . ', AffectedRows=' . $affectedRows . ', SaveRequest=' . ($saveRequestResult ? 'OK' : 'Fail') . print_r($db->error(), true));

             if ($affectedRows === 0 && $updateBalanceResult) {
                return redirect()->back()->withInput()->with('error', 'Gagal memproses permintaan: Saldo mungkin telah berubah atau tidak mencukupi. Silakan cek saldo Anda dan coba lagi.');
             }
             return redirect()->back()->withInput()->with('error', 'Gagal memproses permintaan penarikan karena masalah database. Saldo tidak dikurangi.');
         }
    }

     public function transactions()
    {
        $perPage = 15;
        $pagerService = Services::pager();
        $currentPage = $this->request->getVar('page_transactions') ? (int) $this->request->getVar('page_transactions') : 1;

        $transactions = $this->transactionModel
            ->select('transactions.*, products.product_name')
            ->join('products', 'products.id = transactions.product_id', 'left')
            ->where('transactions.user_id', $this->currentUserId)
            ->where('transactions.transaction_type', 'product')
            ->orderBy('transactions.created_at', 'DESC')
            ->paginate($perPage, 'transactions');

        $data = [
            'title'        => 'Riwayat Transaksi Penjualan',
            'user'         => $this->userModel->find($this->currentUserId),
            'transactions' => $transactions,
            'pager'        => $this->transactionModel->pager,
            'currentPage'  => $currentPage,
            'itemsPerPage' => $perPage,
        ];

        return view('dashboard/transactions', $data);
    }
}

