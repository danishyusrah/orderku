<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel; // <-- Import model varian
use App\Models\ProductStockModel; // <-- Import model stok
use CodeIgniter\Exceptions\PageNotFoundException;

class ProfileController extends BaseController
{
    protected UserModel $userModel;
    protected ProductModel $productModel;
    protected ProductVariantModel $productVariantModel; // <-- Deklarasi properti varian
    protected ProductStockModel $productStockModel; // <-- Deklarasi properti stok
    protected $helpers = ['url', 'number']; // Added url helper, added number helper for formatting

    public function __construct()
    {
        $this->userModel         = new UserModel();
        $this->productModel      = new ProductModel();
        $this->productVariantModel = new ProductVariantModel(); // <-- Inisialisasi
        $this->productStockModel = new ProductStockModel(); // <-- Inisialisasi
        helper('url'); // Load url helper here if not loaded globally
    }

    /**
     * Display the public profile page for a user based on username.
     *
     * @param string $username
     * @throws PageNotFoundException
     */
    public function index(string $username)
    {
        // 1. Find the user by username
        $user = $this->userModel->where('username', $username)->first();

        // 2. If user not found, show 404 error
        if (! $user) {
            throw PageNotFoundException::forPageNotFound('User "' . esc($username) . '" tidak ditemukan.');
        }

        // 3. Get all active products for this user
        $products = $this->productModel->getActiveProductsByUserId($user->id);

        // 4. Prepare icon URL, variants, and ACTUAL stock for each product
        foreach ($products as $product) {
            $product->icon_url = $product->icon_filename
                ? base_url('uploads/product_icons/' . $product->icon_filename)
                : 'https://placehold.co/80x80/7c3aed/FFFFFF?text=' . strtoupper(substr(esc($product->product_name), 0, 1)); // Placeholder with different color

            // Ambil varian jika produk punya varian (tipe auto)
            if ($product->order_type === 'auto' && $product->has_variants) {
                $product->variants = $this->productVariantModel->getActiveVariantsByProductId($product->id);
                // Ambil stok AKTUAL untuk setiap varian
                if (!empty($product->variants)) {
                    foreach ($product->variants as $variant) {
                        $variant->stock = $this->productStockModel->getAvailableStockCountForVariant($variant->id);
                    }
                }
                $product->available_stock = null; // Set null for base product stock if has variants
            }
            // Ambil stok AKTUAL jika produk tipe auto TAPI tidak punya varian
            elseif ($product->order_type === 'auto' && !$product->has_variants) {
                $product->variants = null;
                $product->available_stock = $this->productStockModel->getAvailableStockCountForNonVariant($product->id);
            }
            // Produk manual tidak perlu info stok di sini
            else {
                $product->variants = null;
                $product->available_stock = null;
            }
        }

        // 5. Prepare data to send to the View
        // Construct Logo URL
        $logoUrl = $user->logo_filename
            ? base_url('uploads/logos/' . $user->logo_filename)
            // Fallback to UI Avatars using store_name or username
            : 'https://ui-avatars.com/api/?name=' . urlencode($user->store_name ?: $user->username) . '&background=4f46e5&color=ffffff&size=64&bold=true';

        $data = [
            'user'     => $user,
            'logoUrl'  => $logoUrl, // Pass logo URL
            'products' => $products, // Now includes icon_url, variants with ACTUAL stock, and available_stock for non-variants
        ];

        // 6. Render the view and send the data
        return view('profile/public_page', $data);
    }
}
