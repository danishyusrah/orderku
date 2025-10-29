<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil <?= esc($user->store_name ?: $user->username) // Use store_name or username ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php $midtransConfig = new \Config\Midtrans(); ?>
    <script type="text/javascript"
            src="<?= $midtransConfig->isProduction ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' ?>"
            data-client-key="<?= esc($midtransConfig->clientKey) // Use esc() for security ?>"></script>
    <!-- Add CSRF Token Meta Tag -->
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .animated-gradient-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            background-size: 400% 400%;
            animation: gradientMove 30s ease infinite;
            filter: blur(80px);
            opacity: 0.6;
            transition: background-image 0.5s ease-in-out;
        }
        .dark .animated-gradient-bg {
            background-image: linear-gradient(to right top, #3b82f6, #6366f1, #a855f7, #ec4899);
        }
        .light .animated-gradient-bg {
            background-image: linear-gradient(to right top, #8cd9fb, #a7e2fc, #bfeaff, #d6f2ff);
            opacity: 0.8;
            filter: blur(100px);
        }
        .main-content-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        /* Update modal hidden states */
        #checkoutModal.hidden, #tosModal.hidden, #manualDetailModal.hidden, #variantSelectionModal.hidden, #qrisModal.hidden { display: none; }
        .modal-enter { opacity: 0; transform: scale(0.95); }
        .modal-enter-active { opacity: 1; transform: scale(1); transition: opacity 300ms, transform 300ms; }
        .modal-leave-active { opacity: 0; transform: scale(0.95); transition: opacity 300ms, transform 300ms; }
        /* Update scrollbar selectors */
        #tosModalContent::-webkit-scrollbar, #manualDetailModalContent::-webkit-scrollbar, #variantSelectionModalContent::-webkit-scrollbar, #qrisModalContent::-webkit-scrollbar { width: 6px; }
        #tosModalContent::-webkit-scrollbar-track, #manualDetailModalContent::-webkit-scrollbar-track, #variantSelectionModalContent::-webkit-scrollbar-track, #qrisModalContent::-webkit-scrollbar-track { background: #e5e7eb; border-radius: 3px;} /* Light mode track */
        .dark #tosModalContent::-webkit-scrollbar-track, .dark #manualDetailModalContent::-webkit-scrollbar-track, .dark #variantSelectionModalContent::-webkit-scrollbar-track, .dark #qrisModalContent::-webkit-scrollbar-track { background: #374151; } /* Dark mode track */
        #tosModalContent::-webkit-scrollbar-thumb, #manualDetailModalContent::-webkit-scrollbar-thumb, #variantSelectionModalContent::-webkit-scrollbar-thumb, #qrisModalContent::-webkit-scrollbar-thumb { background: #9ca3af; border-radius: 3px;} /* Light mode thumb */
        .dark #tosModalContent::-webkit-scrollbar-thumb, .dark #manualDetailModalContent::-webkit-scrollbar-thumb, .dark #variantSelectionModalContent::-webkit-scrollbar-thumb, .dark #qrisModalContent::-webkit-scrollbar-thumb { background: #6b7280; } /* Dark mode thumb */
        #tosModalContent::-webkit-scrollbar-thumb:hover, #manualDetailModalContent::-webkit-scrollbar-thumb:hover, #variantSelectionModalContent::-webkit-scrollbar-thumb:hover, #qrisModalContent::-webkit-scrollbar-thumb:hover { background: #6b7280; } /* Light mode hover */
        .dark #tosModalContent::-webkit-scrollbar-thumb:hover, .dark #manualDetailModalContent::-webkit-scrollbar-thumb:hover, .dark #variantSelectionModalContent::-webkit-scrollbar-thumb:hover, .dark #qrisModalContent::-webkit-scrollbar-thumb:hover { background: #9ca3af; } /* Dark mode hover */

        .fade-out { animation: fadeOut 0.5s forwards; animation-delay: 5s; }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; display: none; } }
        .spinner {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 2px solid #fff;
            width: 1rem;
            height: 1rem;
            animation: spin 1s linear infinite;
        }
        .light .spinner { /* Spinner for light mode */
             border: 2px solid rgba(0, 0, 0, 0.2);
             border-top: 2px solid #333;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        .variant-item-radio {
            transition: all 0.2s;
        }
        .variant-item-radio:hover {
            background-color: rgba(0, 0, 0, 0.05); /* Light hover */
        }
        .dark .variant-item-radio:hover {
            background-color: rgba(255, 255, 255, 0.05); /* Dark hover */
        }
        .variant-item-radio input:checked + div {
            border-color: #4f46e5; /* Indigo */
            background-color: rgba(79, 70, 229, 0.05); /* Light checked */
        }
        .dark .variant-item-radio input:checked + div {
            background-color: rgba(99, 102, 241, 0.1); /* Dark checked */
        }
        /* Style adjustments for text color inside checked variant */
        .variant-item-radio input:checked + div .variant-name {
            color: #1f2937; /* Darker text for light mode */
        }
        .dark .variant-item-radio input:checked + div .variant-name {
             color: #c7d2fe; /* Lighter text for dark mode */
        }
         .variant-item-radio input:checked + div .variant-stock {
            color: #4b5563; /* Medium gray for light mode */
        }
        .dark .variant-item-radio input:checked + div .variant-stock {
             color: #9ca3af; /* Light gray for dark mode */
        }
         .variant-item-radio input:checked + div .variant-price {
            color: #4f46e5; /* Indigo for light mode */
        }
        .dark .variant-item-radio input:checked + div .variant-price {
            color: #a5b4fc; /* Lighter Indigo for dark mode */
        }
        /* Quantity Input */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] {
            -moz-appearance: textfield;
            appearance: textfield;
        }
        .quantity-btn {
            cursor: pointer;
            user-select: none;
        }
        /* Disable button style */
        button:disabled, button[disabled] {
            cursor: not-allowed;
            opacity: 0.6;
            background-image: none !important; /* Remove gradient on disabled */
        }

        /* QRIS Modal specific */
        #qrisImage {
             max-width: 100%;
             height: auto;
             max-height: 300px; /* Limit QR height */
             display: block;
             margin: 1rem auto;
             border: 1px solid #e5e7eb; /* Light border */
             border-radius: 8px;
        }
        .dark #qrisImage {
             border-color: #4b5563; /* Dark border */
        }
        .countdown-timer {
            font-size: 0.8rem;
            color: #ef4444; /* Red color for timer */
            font-weight: 500;
        }

    </style>
    <script>
        // Apply Tailwind config for dark mode
        tailwind.config = {
            darkMode: 'class', // Enable class-based dark mode
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    // Define custom colors for easier light/dark mode management
                    colors: {
                        'brand': {
                            'bg': {
                                light: '#EBF0F9', // Light background
                                dark: '#111827',   // Dark background (gray-900)
                            },
                            'card': {
                                light: 'rgba(255, 255, 255, 0.8)', // Lighter card with transparency
                                dark: 'rgba(31, 41, 55, 0.8)',   // Darker card (gray-800) with transparency
                            },
                            'button': {
                                light: 'rgba(226, 232, 240, 0.8)', // Light button
                                dark: 'rgba(55, 65, 81, 0.8)',   // Dark button (gray-700)
                            },
                            'button-hover': {
                                light: 'rgba(203, 213, 225, 0.9)', // Light hover
                                dark: 'rgba(75, 85, 99, 0.9)',     // Dark hover (gray-600)
                            },
                             'button-disabled': { // Add disabled colors
                                light: 'rgba(203, 213, 225, 0.7)', // Lighter gray for light mode disabled
                                dark: 'rgba(55, 65, 81, 0.7)',     // Darker gray for dark mode disabled
                            },
                            'border': {
                                light: 'rgba(203, 213, 225, 0.5)', // Light border
                                dark: 'rgba(55, 65, 81, 0.5)',     // Dark border (gray-700)
                            },
                            'text': {
                                light: '#1f2937', // Dark text (gray-800) for light mode
                                dark: '#d1d5db',   // Light text (gray-300) for dark mode
                            },
                            'text-muted': {
                                light: '#6b7280', // Medium gray (gray-500) for light mode
                                dark: '#9ca3af',   // Light gray (gray-400) for dark mode
                            },
                             'text-heading': {
                                light: '#111827', // Darkest gray (gray-900) for light mode headings
                                dark: '#ffffff',   // White for dark mode headings
                            },
                             'text-disabled': { // Add disabled text colors
                                light: '#9ca3af', // Lighter gray for light mode disabled text
                                dark: '#6b7280',   // Darker gray for dark mode disabled text
                            },
                            'icon': {
                                light: '#4f46e5', // Indigo-600 for light mode
                                dark: '#818cf8',   // Indigo-400 for dark mode
                            },
                             'subtitle': { // Keep using yellow for subtitle regardless of theme maybe? Or define specific light/dark
                                light: '#4B5563', // Example: Gray-600 for light mode subtitle
                                dark: '#D1D5DB',   // Example: Gray-300 for dark mode subtitle
                            }
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-brand-bg-light dark:bg-brand-bg-dark text-brand-text-light dark:text-brand-text-dark transition-colors duration-300">

    <div class="animated-gradient-bg"></div>

    <div class="main-content-wrapper">
        <div class="w-full max-w-md bg-brand-card-light dark:bg-brand-card-dark backdrop-blur-md border border-brand-border-light dark:border-brand-border-dark rounded-2xl shadow-xl overflow-hidden p-6 sm:p-8 transition-colors duration-300">

             <!-- Theme Toggle Button -->
            <div class="absolute top-4 right-4 z-20">
                <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2.5 transition-colors duration-200">
                    <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                    <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm-.707 7.072l.707-.707a1 1 0 10-1.414-1.414l-.707.707a1 1 0 001.414 1.414zM15 11a1 1 0 100-2h-1a1 1 0 100 2h1zM4 11a1 1 0 100-2H3a1 1 0 100 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                </button>
            </div>

            <!-- Toast Notifications -->
             <div id="payment-success-toast" class="hidden fixed top-5 left-1/2 -translate-x-1/2 z-50 p-4 bg-green-600/90 text-white text-center rounded-lg text-sm font-medium shadow-lg">
                 <i class="fa-solid fa-check-circle mr-2"></i>Pembayaran berhasil! Cek email Anda untuk detail produk.
             </div>
             <div id="payment-attempt-toast" class="hidden fixed top-5 left-1/2 -translate-x-1/2 z-50 p-4 bg-blue-600/90 text-white text-center rounded-lg text-sm font-medium shadow-lg">
                 <i class="fa-solid fa-info-circle mr-2"></i>Status pembayaran Anda sedang diproses atau menunggu.
             </div>
              <!-- Error Toast (Generic) -->
             <div id="error-toast" class="hidden fixed top-5 left-1/2 -translate-x-1/2 z-50 p-4 bg-red-600/90 text-white text-center rounded-lg text-sm font-medium shadow-lg">
                 <i class="fa-solid fa-exclamation-triangle mr-2"></i><span id="error-toast-message">Terjadi kesalahan.</span>
             </div>


            <header class="text-center mb-6 sm:mb-8">
                <!-- Profile Image/Logo -->
                <div class="inline-block p-1 bg-white dark:bg-gray-800 rounded-full mb-3 sm:mb-4 shadow-lg">
                     <div class="w-16 h-16 bg-white dark:bg-gray-700 rounded-full flex items-center justify-center overflow-hidden">
                          <img src="<?= esc($logoUrl, 'attr') // Use logoUrl from controller ?>"
                               alt="Logo <?= esc($user->store_name ?: $user->username) ?>"
                               class="w-full h-full object-cover"
                               onerror="this.style.display='none'; this.parentElement.innerHTML = '<svg class=\'w-10 h-10 text-brand-icon-light dark:text-brand-icon-dark\' viewBox=\'0 0 24 24\' fill=\'currentColor\' xmlns=\'http://www.w3.org/2000/svg\'><path fill-rule=\'evenodd\' clip-rule=\'evenodd\' d=\'M12.5 5C10.0147 5 8 7.01472 8 9.5V14.5C8 16.9853 10.0147 19 12.5 19H14V17H12.5C11.1193 17 10 15.8807 10 14.5V9.5C10 8.11929 11.1193 7 12.5 7H14V5H12.5Z\'/></svg>';">
                     </div>
                </div>
                 <!-- Store Name -->
                <h1 class="text-xl sm:text-2xl font-bold text-brand-text-heading-light dark:text-brand-text-heading-dark">
                    <?= esc($user->store_name ?: $user->username) // Display store_name or username ?>
                    <?php if ($user->is_premium): ?>
                        <span class="ml-1 text-yellow-500 dark:text-yellow-400" title="Akun Premium"><i class="fa-solid fa-star text-sm"></i></span>
                    <?php endif; ?>
                </h1>
                <!-- Subtitle -->
                 <?php if (!empty($user->profile_subtitle)): ?>
                 <p class="text-xs sm:text-sm text-brand-subtitle-light dark:text-brand-subtitle-dark font-medium mt-1 sm:mt-2">
                    <?= esc($user->profile_subtitle) ?>
                </p>
                <?php endif; ?>
                 <!-- Contact Links -->
                 <div class="mt-3 sm:mt-4 flex flex-wrap justify-center gap-2 text-xs">
                     <a href="mailto:<?= esc($user->email) ?>" class="flex items-center px-3 py-1 bg-gray-100 dark:bg-gray-700/50 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-full text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white transition-colors">
                         <i class="fa-solid fa-envelope mr-1.5 text-xs"></i> Email
                     </a>
                     <?php if (!empty($user->whatsapp_link)): ?>
                     <a href="<?= esc($user->whatsapp_link, 'attr') ?>" target="_blank" rel="noopener noreferrer" class="flex items-center px-3 py-1 bg-green-100 dark:bg-green-600/20 hover:bg-green-200 dark:hover:bg-green-600/40 rounded-full text-green-700 dark:text-green-300 hover:text-green-800 dark:hover:text-green-200 transition-colors">
                         <i class="fa-brands fa-whatsapp mr-1.5 text-xs"></i> WhatsApp
                     </a>
                     <?php endif; ?>
                 </div>
            </header>

            <main class="space-y-2 sm:space-y-3">
                <!-- Product List -->
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <?php
                            $isOutOfStock = false;
                            if ($product->order_type == 'auto') {
                                if ($product->has_variants) {
                                    // Check if all variants are out of stock
                                    $allVariantsOutOfStock = true;
                                    if (!empty($product->variants)) {
                                        foreach ($product->variants as $variant) {
                                            if ($variant->stock > 0) {
                                                $allVariantsOutOfStock = false;
                                                break;
                                            }
                                        }
                                    }
                                    $isOutOfStock = $allVariantsOutOfStock;
                                } else {
                                    // Check base product stock
                                    $isOutOfStock = $product->available_stock <= 0;
                                }
                            }
                        ?>
                        <?php if ($product->order_type == 'manual'): // Manual Order Button
                            // Prepare WhatsApp link
                            $base_wa_url = esc($product->target_url, 'attr');
                            // Use Store Name or Username in WA message
                            $message = "Halo " . esc($user->store_name ?: $user->username) . ", saya tertarik untuk membeli produk \"" . esc($product->product_name) . "\".";
                            $encoded_message = rawurlencode($message);
                            $separator = strpos($base_wa_url, '?') === false ? '?' : '&';
                            $whatsapp_link = $base_wa_url . $separator . 'text=' . $encoded_message;
                        ?>
                            <button type="button"
                                    data-product-name="<?= esc($product->product_name, 'attr') ?>"
                                    data-product-desc="<?= esc($product->description ?? '', 'attr') ?>"
                                    data-product-price="<?= (int)$product->price ?>"
                                    data-product-icon="<?= esc($product->icon_url, 'attr') ?>"
                                    data-whatsapp-link="<?= $whatsapp_link ?>"
                                    onclick="openManualDetailModal(this)"
                                    class="flex items-center w-full p-3 bg-brand-button-light dark:bg-brand-button-dark hover:bg-brand-button-hover-light dark:hover:bg-brand-button-hover-dark border border-brand-border-light dark:border-brand-border-dark rounded-xl shadow-sm transition-all duration-200 ease-in-out transform hover:scale-[1.02] text-left">
                                <img src="<?= esc($product->icon_url, 'attr') ?>" alt="Ikon Produk" class="w-8 h-8 rounded object-cover flex-shrink-0 mr-3" loading="lazy" onerror="this.onerror=null; this.src='https://placehold.co/32x32/9ca3af/E2E8F0?text=<?= esc(strtoupper(substr($product->product_name, 0, 1)), 'attr') ?>';">
                                <div class="flex-grow min-w-0 mr-2">
                                    <span class="font-semibold text-brand-text-heading-light dark:text-brand-text-heading-dark block truncate text-sm" title="<?= esc($product->product_name, 'attr') ?>"><?= esc($product->product_name) ?></span>
                                    <?php if (!empty($product->description)): ?>
                                        <span class="text-xs text-brand-text-muted-light dark:text-brand-text-muted-dark block truncate"><?= esc($product->description) ?></span>
                                    <?php endif; ?>
                                    <?php if ($product->price > 0): ?>
                                         <span class="block text-xs text-brand-text-muted-light dark:text-brand-text-muted-dark font-medium mt-0.5">Rp <?= number_format($product->price, 0, ',', '.') ?></span>
                                    <?php endif; ?>
                                </div>
                                <i class="fa-solid fa-arrow-right text-brand-text-muted-light dark:text-brand-text-muted-dark text-sm flex-shrink-0"></i>
                            </button>
                        <?php else: // Automatic Order Button
                             $productDataArray = [ // Build array first, without escaping for JS
                                 'id' => $product->id,
                                 'name' => $product->product_name,
                                 'price' => (int)$product->price, // Base price
                                 'desc' => $product->description ?? '',
                                 'has_variants' => (bool)$product->has_variants,
                                 'variants' => $product->variants ? array_map(function($v) {
                                     // Include ACTUAL stock in variant data
                                     return ['id' => $v->id, 'name' => $v->name, 'price' => (int)$v->price, 'stock' => (int)$v->stock];
                                 }, $product->variants) : [],
                                 'stock' => !$product->has_variants ? ($product->available_stock ?? 0) : 0 // Include ACTUAL base stock if no variants
                             ];
                             // Encode the array to JSON, then escape for HTML attribute context
                             $product_data_json = esc(json_encode($productDataArray), 'attr');
                             ?>
                             <button type="button"
                                    data-product='<?= $product_data_json ?>'
                                    onclick="handleProductClick(this)"
                                    <?= $isOutOfStock ? 'disabled' : '' ?>
                                    class="flex items-center w-full p-3 bg-brand-button-light dark:bg-brand-button-dark <?= $isOutOfStock ? 'bg-brand-button-disabled-light dark:bg-brand-button-disabled-dark' : 'hover:bg-brand-button-hover-light dark:hover:bg-brand-button-hover-dark' ?> border border-brand-border-light dark:border-brand-border-dark rounded-xl shadow-sm transition-all duration-200 ease-in-out transform <?= $isOutOfStock ? '' : 'hover:scale-[1.02]' ?> text-left <?= $isOutOfStock ? 'cursor-not-allowed opacity-60' : '' ?>">
                                <img src="<?= esc($product->icon_url, 'attr') ?>" alt="Ikon Produk" class="w-8 h-8 rounded object-cover flex-shrink-0 mr-3" loading="lazy" onerror="this.onerror=null; this.src='https://placehold.co/32x32/9ca3af/E2E8F0?text=<?= esc(strtoupper(substr($product->product_name, 0, 1)), 'attr') ?>';">
                                <div class="flex-grow min-w-0 mr-2">
                                     <span class="font-semibold <?= $isOutOfStock ? 'text-brand-text-disabled-light dark:text-brand-text-disabled-dark' : 'text-brand-text-heading-light dark:text-brand-text-heading-dark' ?> block truncate text-sm" title="<?= esc($product->product_name, 'attr') ?>"><?= esc($product->product_name) ?></span>
                                     <?php if (!empty($product->description)): ?>
                                        <span class="text-xs <?= $isOutOfStock ? 'text-brand-text-disabled-light dark:text-brand-text-disabled-dark' : 'text-brand-text-muted-light dark:text-brand-text-muted-dark' ?> block truncate"><?= esc($product->description) ?></span>
                                    <?php endif; ?>
                                     <span class="block text-xs <?= $isOutOfStock ? 'text-red-500 dark:text-red-400' : 'text-brand-icon-light dark:text-brand-icon-dark' ?> font-bold mt-0.5">
                                         <?php if ($isOutOfStock): ?>
                                             Stok Habis
                                         <?php elseif ($product->has_variants && $product->variants): ?>
                                             Pilih Varian
                                         <?php else: ?>
                                             Rp <?= number_format($product->price, 0, ',', '.') ?>
                                         <?php endif; ?>
                                     </span>
                                </div>
                                <i class="fa-solid fa-arrow-right <?= $isOutOfStock ? 'text-brand-text-disabled-light dark:text-brand-text-disabled-dark' : 'text-brand-text-muted-light dark:text-brand-text-muted-dark' ?> text-sm flex-shrink-0"></i>
                            </button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center p-4 bg-gray-100 dark:bg-gray-800/50 rounded-lg">
                        <p class="text-brand-text-muted-light dark:text-brand-text-muted-dark text-sm">Pengguna ini belum memiliki produk.</p>
                    </div>
                <?php endif; ?>
            </main>

            <footer class="text-center mt-6 sm:mt-8 pt-4 sm:pt-6 border-t border-brand-border-light dark:border-brand-border-dark">
                 <button onclick="openTosModal()" class="text-xs text-brand-text-muted-light dark:text-brand-text-muted-dark hover:text-gray-700 dark:hover:text-gray-300 underline mb-2">Ketentuan Layanan</button>
                <p class="text-xs text-brand-text-muted-light dark:text-brand-text-muted-dark">
                    &copy; <?= date('Y') ?> <?= esc($user->store_name ?: $user->username) // Display store name or username in footer too ?>. All rights reserved.
                </p>
            </footer>
        </div>
    </div>

    <!-- Modal Detail Manual -->
    <div id="manualDetailModal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-40 flex items-center justify-center p-4 modal-enter" role="dialog" aria-modal="true" aria-labelledby="manualDetailModalTitle">
        <div class="w-full max-w-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl z-50">
            <div class="flex justify-between items-center p-4 sm:p-5 border-b border-gray-200 dark:border-gray-700">
                <h3 id="manualDetailModalTitle" class="text-lg font-semibold text-gray-900 dark:text-white">Detail Produk</h3>
                <button onclick="closeManualDetailModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-white" aria-label="Tutup">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>
            <div id="manualDetailModalContent" class="p-4 sm:p-6 space-y-3 max-h-[60vh] overflow-y-auto">
                 <div class="flex items-center space-x-3 sm:space-x-4">
                     <img id="modalManualIcon" src="" alt="Ikon Produk" class="w-12 h-12 sm:w-16 sm:h-16 rounded object-cover flex-shrink-0 border border-gray-200 dark:border-gray-700" onerror="this.src='https://placehold.co/64x64/cbd5e1/475569?text=?';">
                     <div>
                        <h4 id="modalManualName" class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">Nama Produk</h4>
                        <p id="modalManualPrice" class="text-sm sm:text-md font-semibold text-gray-700 dark:text-gray-400">Rp 0</p>
                    </div>
                 </div>
                 <p id="modalManualDesc" class="text-sm text-gray-600 dark:text-gray-400"></p>
            </div>
            <div class="p-3 sm:p-4 border-t border-gray-200 dark:border-gray-700 text-right">
                 <a id="modalManualWhatsappLink" href="#" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center text-white bg-green-500 hover:bg-green-600 focus:ring-4 focus:outline-none focus:ring-green-300 dark:focus:ring-green-800 font-bold rounded-lg text-sm px-4 py-2.5 sm:px-5 sm:py-3 text-center transition-all duration-200">
                     <i class="fa-brands fa-whatsapp mr-2"></i> Lanjut ke WhatsApp
                 </a>
            </div>
        </div>
    </div>

    <!-- Modal Pemilihan Varian (UPDATED) -->
    <div id="variantSelectionModal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-40 flex items-center justify-center p-4 modal-enter" role="dialog" aria-modal="true" aria-labelledby="variantSelectionModalTitle">
        <div class="w-full max-w-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl z-50">
            <div class="flex justify-between items-center p-4 sm:p-5 border-b border-gray-200 dark:border-gray-700">
                <h3 id="variantSelectionModalTitle" class="text-lg font-semibold text-gray-900 dark:text-white">Pilih Varian Produk</h3>
                <button onclick="closeVariantSelectionModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-white" aria-label="Tutup">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>
            <div id="variantSelectionModalContent" class="p-4 sm:p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                 <div id="modalVariantProductInfo" class="bg-gray-100 dark:bg-gray-700/50 p-3 rounded-lg">
                    <h4 id="modalVariantProductName" class="text-sm font-bold text-gray-900 dark:text-white">Nama Produk</h4>
                    <p id="modalVariantProductDesc" class="text-xs text-gray-500 dark:text-gray-400 mt-1"></p>
                </div>
                <div id="variant-list" class="space-y-3">
                    <!-- Varian akan di-inject di sini via JS -->
                </div>
                 <!-- Quantity Selector for Variant -->
                 <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <label for="variantQuantity" class="block mb-1.5 text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300">Jumlah</label>
                    <div class="flex items-center">
                        <button type="button" onclick="changeQuantity('variantQuantity', -1)" class="quantity-btn p-2 rounded-l-lg bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-minus"></i>
                        </button>
                        <input type="number" id="variantQuantity" name="quantity" value="1" min="1" max="1" class="w-16 text-center bg-gray-50 dark:bg-gray-700 border-t border-b border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm focus:ring-0 focus:border-gray-300 dark:focus:border-gray-600" readonly>
                        <button type="button" onclick="changeQuantity('variantQuantity', 1)" class="quantity-btn p-2 rounded-r-lg bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                     <p id="variantQuantityError" class="text-xs text-red-500 dark:text-red-400 mt-1 hidden"></p>
                </div>
            </div>
            <div class="p-3 sm:p-4 border-t border-gray-200 dark:border-gray-700 text-right">
                <button id="selectVariantAndCheckoutBtn" disabled class="w-full text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:outline-none focus:ring-indigo-300 dark:focus:ring-indigo-800 font-bold rounded-lg text-sm px-5 py-2.5 sm:py-3 text-center transition-all duration-200 flex items-center justify-center disabled:bg-brand-button-disabled-light dark:disabled:bg-brand-button-disabled-dark disabled:cursor-not-allowed">
                    <span id="selectVariantText" class="flex items-center"><i class="fa-solid fa-cart-shopping mr-2"></i> Pilih dan Lanjut Bayar</span>
                </button>
                <input type="hidden" id="selectedVariantId" value="">
                <input type="hidden" id="selectedVariantPrice" value="">
                <input type="hidden" id="selectedVariantName" value="">
                <input type="hidden" id="selectedVariantStock" value="0"> <!-- Store selected variant stock -->
                <input type="hidden" id="baseProductId" value="">
                 <input type="hidden" id="baseProductName" value=""> <!-- Store base product name -->
                 <input type="hidden" id="baseProductDesc" value=""> <!-- Store base product description -->
            </div>
        </div>
    </div>

    <!-- Modal Checkout (UPDATED with Quantity) -->
    <div id="checkoutModal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-40 flex items-center justify-center p-4 modal-enter" role="dialog" aria-modal="true" aria-labelledby="checkoutModalTitle">
        <div class="w-full max-w-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl z-50">
            <div class="flex justify-between items-center p-4 sm:p-5 border-b border-gray-200 dark:border-gray-700">
                <h3 id="checkoutModalTitle" class="text-lg font-semibold text-gray-900 dark:text-white">Konfirmasi Pembelian</h3>
                <button onclick="closeCheckoutModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-white" aria-label="Tutup">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>
            <form id="checkoutForm" class="p-4 sm:p-6 space-y-3 sm:space-y-4">
                <div class="bg-gray-100 dark:bg-gray-700/50 p-3 sm:p-4 rounded-lg">
                    <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">Anda akan membeli:</p>
                    <h4 id="modalProductName" class="text-sm sm:text-base font-bold text-gray-900 dark:text-white">Nama Produk</h4>
                    <p id="modalProductDesc" class="text-xs text-gray-500 dark:text-gray-400 mt-1"></p>
                    <!-- Add Quantity Display -->
                    <div class="flex items-center justify-between mt-2">
                         <div>
                            <label for="checkoutQuantity" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Jumlah</label>
                            <div class="flex items-center">
                                <button type="button" onclick="changeQuantity('checkoutQuantity', -1)" class="quantity-btn p-1.5 rounded-l-md bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200">
                                    <i class="fa-solid fa-minus text-xs"></i>
                                </button>
                                <input type="number" id="checkoutQuantity" name="quantity" value="1" min="1" max="1" class="w-12 text-center bg-gray-50 dark:bg-gray-700 border-t border-b border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm focus:ring-0 focus:border-gray-300 dark:focus:border-gray-600" readonly>
                                <button type="button" onclick="changeQuantity('checkoutQuantity', 1)" class="quantity-btn p-1.5 rounded-r-md bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200">
                                    <i class="fa-solid fa-plus text-xs"></i>
                                </button>
                            </div>
                            <p id="checkoutQuantityError" class="text-xs text-red-500 dark:text-red-400 mt-1 hidden"></p>
                         </div>
                         <div class="text-right">
                             <p class="text-xs text-gray-500 dark:text-gray-400">Total Harga:</p>
                             <p id="modalProductPrice" class="text-base sm:text-lg font-semibold text-brand-icon-light dark:text-brand-icon-dark">Rp 0</p>
                         </div>
                    </div>
                </div>
                <input type="hidden" id="modalProductId" value="">
                <input type="hidden" id="modalVariantId" value="">
                <input type="hidden" id="modalBasePrice" value="0"> <!-- Store base price per item -->
                <input type="hidden" id="modalMaxStock" value="0"> <!-- Store max available stock -->

                <div id="checkoutError" class="hidden p-3 bg-red-100 dark:bg-red-500/20 border border-red-300 dark:border-red-500/50 text-red-700 dark:text-red-300 rounded-lg text-sm" role="alert"></div>
                <div>
                    <label for="buyerName" class="block mb-1.5 text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300">Nama Lengkap Anda</label>
                    <input type="text" id="buyerName" class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-purple-500 focus:border-purple-500 block w-full p-2 sm:p-2.5" placeholder="Nama Anda" required>
                    <p id="buyerNameError" class="text-xs text-red-500 dark:text-red-400 mt-1 hidden">Nama tidak boleh kosong.</p>
                </div>
                <div>
                    <label for="buyerEmail" class="block mb-1.5 text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300">Email Anda</label>
                    <input type="email" id="buyerEmail" class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-purple-500 focus:border-purple-500 block w-full p-2 sm:p-2.5" placeholder="email@anda.com" required>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Produk digital akan dikirim ke email ini.</p>
                    <p id="buyerEmailError" class="text-xs text-red-500 dark:text-red-400 mt-1 hidden">Masukkan alamat email yang valid.</p>
                </div>
                <button type="submit" id="payButton" class="w-full text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:outline-none focus:ring-indigo-300 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800 font-bold rounded-lg text-sm px-5 py-2.5 sm:py-3 text-center transition-all duration-200 flex items-center justify-center min-h-[44px] disabled:bg-brand-button-disabled-light dark:disabled:bg-brand-button-disabled-dark">
                    <span id="payButtonText" class="flex items-center"><i class="fa-solid fa-shield-halved mr-2"></i> Bayar Sekarang</span>
                    <span id="payButtonSpinner" class="hidden spinner"></span>
                </button>
            </form>
        </div>
    </div>

    <!-- NEW: Modal QRIS Orderkuota -->
    <div id="qrisModal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-40 flex items-center justify-center p-4 modal-enter" role="dialog" aria-modal="true" aria-labelledby="qrisModalTitle">
        <div class="w-full max-w-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl z-50">
            <div class="flex justify-between items-center p-4 sm:p-5 border-b border-gray-200 dark:border-gray-700">
                <h3 id="qrisModalTitle" class="text-lg font-semibold text-gray-900 dark:text-white">Scan QRIS untuk Pembayaran</h3>
                <button onclick="closeQrisModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-white" aria-label="Tutup">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>
            <div id="qrisModalContent" class="p-4 sm:p-6 text-center max-h-[70vh] overflow-y-auto">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Silakan scan kode QRIS di bawah ini menggunakan aplikasi e-wallet atau mobile banking Anda.</p>
                <!---->
                <img id="qrisImage" src="" alt="Kode QRIS Pembayaran" class="mb-3">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Total Pembayaran:</p>
                <p id="qrisAmount" class="text-xl font-bold text-indigo-600 dark:text-indigo-400 mb-3">Rp 0</p>
                <p class="text-xs text-gray-500 dark:text-gray-500">Pastikan jumlah pembayaran sesuai.</p>
                <div class="mt-4">
                    <p class="text-xs text-gray-500 dark:text-gray-500">Batas Waktu Pembayaran:</p>
                    <p id="qrisExpiry" class="countdown-timer">--:--</p>
                </div>
                 <div id="qrisError" class="hidden mt-4 p-3 bg-red-100 dark:bg-red-500/20 border border-red-300 dark:border-red-500/50 text-red-700 dark:text-red-300 rounded-lg text-sm">
                    Gagal memuat QRIS. Coba lagi nanti.
                </div>
                 <div class="mt-5 text-center">
                     <button onclick="checkOrderStatusManual()" id="checkQrisStatusBtn" class="px-4 py-2 text-xs rounded-lg bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 font-medium transition-colors">
                        <i class="fa-solid fa-sync fa-spin mr-1 hidden" id="checkQrisSpinner"></i>
                        <span id="checkQrisBtnText">Cek Status Pembayaran</span>
                    </button>
                 </div>
            </div>
             <input type="hidden" id="qrisOrderId" value="">
             <input type="hidden" id="qrisReferenceId" value="">
             <input type="hidden" id="qrisExpiryTimestamp" value="">
        </div>
    </div>

    <!-- Modal TOS (Sama seperti sebelumnya) -->
    <div id="tosModal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-40 flex items-center justify-center p-4 modal-enter" role="dialog" aria-modal="true" aria-labelledby="tosModalTitle">
         <div class="w-full max-w-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl z-50 flex flex-col max-h-[80vh]">
            <div class="flex justify-between items-center p-5 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <h3 id="tosModalTitle" class="text-xl font-semibold text-gray-900 dark:text-white">Ketentuan Layanan</h3>
                <button onclick="closeTosModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-white" aria-label="Tutup">
                    <i class="fa-solid fa-times text-2xl"></i>
                </button>
            </div>
            <div id="tosModalContent" class="p-6 overflow-y-auto text-gray-600 dark:text-gray-300 text-sm">
                <h4 class="font-semibold text-gray-800 dark:text-white mb-2">1. Pendahuluan</h4>
                <p class="mb-4">Selamat datang di layanan kami. Dengan menggunakan layanan ini, Anda setuju untuk terikat oleh Ketentuan Layanan berikut. Harap baca dengan seksama.</p>
                <h4 class="font-semibold text-gray-800 dark:text-white mb-2">2. Penggunaan Layanan</h4>
                <p class="mb-4">Anda setuju untuk menggunakan layanan kami hanya untuk tujuan yang sah dan sesuai dengan semua hukum dan peraturan yang berlaku. Anda tidak boleh menggunakan layanan ini untuk mendistribusikan konten ilegal, melanggar hak cipta, atau melakukan aktivitas berbahaya lainnya.</p>
                <h4 class="font-semibold text-gray-800 dark:text-white mb-2">3. Pembelian Produk</h4>
                <p class="mb-4">Semua pembelian produk digital bersifat final dan tidak dapat dikembalikan (non-refundable) kecuali ditentukan lain. Pastikan Anda membaca deskripsi produk dengan cermat sebelum membeli. Produk digital akan dikirimkan ke alamat email yang Anda berikan saat checkout setelah pembayaran berhasil diverifikasi.</p>
                <h4 class="font-semibold text-gray-800 dark:text-white mb-2">4. Akun Pengguna</h4>
                <p class="mb-4">Jika layanan ini memerlukan pembuatan akun, Anda bertanggung jawab untuk menjaga kerahasiaan informasi akun Anda, termasuk kata sandi. Anda bertanggung jawab penuh atas semua aktivitas yang terjadi di bawah akun Anda.</p>
                <h4 class="font-semibold text-gray-800 dark:text-white mb-2">5. Batasan Tanggung Jawab</h4>
                <p class="mb-4">Layanan ini disediakan "sebagaimana adanya". Kami tidak memberikan jaminan apa pun, baik tersurat maupun tersirat, mengenai keakuratan, kelengkapan, atau keandalan layanan atau produk yang ditawarkan. Kami tidak bertanggung jawab atas kerugian atau kerusakan tidak langsung yang timbul dari penggunaan layanan ini.</p>
                 <h4 class="font-semibold text-gray-800 dark:text-white mb-2">6. Perubahan Ketentuan</h4>
                <p>Kami berhak mengubah Ketentuan Layanan ini kapan saja. Perubahan akan berlaku efektif segera setelah diposting di situs ini. Penggunaan layanan secara berkelanjutan setelah perubahan menunjukkan penerimaan Anda terhadap ketentuan yang baru.</p>
                <p class="mt-6 text-gray-500 dark:text-gray-400">Terakhir diperbarui: <?= date('d F Y') ?></p>
            </div>
             <div class="p-4 border-t border-gray-200 dark:border-gray-700 text-right flex-shrink-0">
                 <button onclick="closeTosModal()" class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 font-medium transition-colors text-sm">Tutup</button>
             </div>
        </div>
    </div>

    <script>
        // --- Existing Variables and Theme Toggle Logic ---
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
        const htmlElement = document.documentElement;

        function applyTheme(theme) {
            // ... (same applyTheme function as before) ...
            if (theme === 'dark') {
                htmlElement.classList.add('dark');
                htmlElement.classList.remove('light');
                themeToggleLightIcon.classList.remove('hidden');
                themeToggleDarkIcon.classList.add('hidden');
                localStorage.setItem('color-theme', 'dark');
            } else {
                htmlElement.classList.remove('dark');
                htmlElement.classList.add('light');
                themeToggleDarkIcon.classList.remove('hidden');
                themeToggleLightIcon.classList.add('hidden');
                localStorage.setItem('color-theme', 'light');
            }
            const gradientBg = document.querySelector('.animated-gradient-bg');
            if(gradientBg) {
                gradientBg.style.backgroundImage = theme === 'dark'
                    ? 'linear-gradient(to right top, #3b82f6, #6366f1, #a855f7, #ec4899)'
                    : 'linear-gradient(to right top, #8cd9fb, #a7e2fc, #bfeaff, #d6f2ff)';
            }
        }
        const savedTheme = localStorage.getItem('color-theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyTheme(savedTheme ? savedTheme : (prefersDark ? 'dark' : 'light'));
        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = htmlElement.classList.contains('dark') ? 'dark' : 'light';
            applyTheme(currentTheme === 'dark' ? 'light' : 'dark');
        });

        // --- Modal Elements ---
        const checkoutModalEl = document.getElementById('checkoutModal');
        const checkoutFormEl = document.getElementById('checkoutForm');
        const payButtonEl = document.getElementById('payButton');
        const payButtonTextEl = document.getElementById('payButtonText');
        const payButtonSpinnerEl = document.getElementById('payButtonSpinner');
        const checkoutErrorEl = document.getElementById('checkoutError');
        const buyerNameInput = document.getElementById('buyerName');
        const buyerEmailInput = document.getElementById('buyerEmail');
        const buyerNameError = document.getElementById('buyerNameError');
        const buyerEmailError = document.getElementById('buyerEmailError');
        const modalProductNameEl = document.getElementById('modalProductName');
        const modalProductDescEl = document.getElementById('modalProductDesc');
        const modalProductPriceEl = document.getElementById('modalProductPrice');
        const modalProductIdInput = document.getElementById('modalProductId');
        const modalVariantIdInput = document.getElementById('modalVariantId');
        const modalBasePriceInput = document.getElementById('modalBasePrice');
        const modalMaxStockInput = document.getElementById('modalMaxStock');
        const checkoutQuantityInput = document.getElementById('checkoutQuantity');
        const checkoutQuantityError = document.getElementById('checkoutQuantityError');

        const manualDetailModalEl = document.getElementById('manualDetailModal');
        const modalManualIconEl = document.getElementById('modalManualIcon');
        const modalManualNameEl = document.getElementById('modalManualName');
        const modalManualDescEl = document.getElementById('modalManualDesc');
        const modalManualPriceEl = document.getElementById('modalManualPrice');
        const modalManualWhatsappLinkEl = document.getElementById('modalManualWhatsappLink');

        const variantSelectionModalEl = document.getElementById('variantSelectionModal');
        const variantListEl = document.getElementById('variant-list');
        const modalVariantProductNameEl = document.getElementById('modalVariantProductName');
        const modalVariantProductDescEl = document.getElementById('modalVariantProductDesc');
        const selectVariantAndCheckoutBtn = document.getElementById('selectVariantAndCheckoutBtn');
        const selectedVariantIdInput = document.getElementById('selectedVariantId');
        const selectedVariantPriceInput = document.getElementById('selectedVariantPrice');
        const selectedVariantNameInput = document.getElementById('selectedVariantName');
        const selectedVariantStockInput = document.getElementById('selectedVariantStock');
        const baseProductIdInput = document.getElementById('baseProductId');
        const baseProductNameInput = document.getElementById('baseProductName'); // Added
        const baseProductDescInput = document.getElementById('baseProductDesc'); // Added
        const variantQuantityInput = document.getElementById('variantQuantity');
        const variantQuantityError = document.getElementById('variantQuantityError');

        const tosModalEl = document.getElementById('tosModal');
        const qrisModalEl = document.getElementById('qrisModal'); // <-- NEW
        const qrisImageEl = document.getElementById('qrisImage'); // <-- NEW
        const qrisAmountEl = document.getElementById('qrisAmount'); // <-- NEW
        const qrisExpiryEl = document.getElementById('qrisExpiry'); // <-- NEW
        const qrisErrorEl = document.getElementById('qrisError');   // <-- NEW
        const qrisOrderIdInput = document.getElementById('qrisOrderId'); // <-- NEW
        const qrisReferenceIdInput = document.getElementById('qrisReferenceId'); // <-- NEW
        const qrisExpiryTimestampInput = document.getElementById('qrisExpiryTimestamp'); // <-- NEW
        const checkQrisStatusBtn = document.getElementById('checkQrisStatusBtn'); // <-- NEW
        const checkQrisSpinner = document.getElementById('checkQrisSpinner'); // <-- NEW
        const checkQrisBtnText = document.getElementById('checkQrisBtnText'); // <-- NEW


        const successToast = document.getElementById('payment-success-toast');
        const attemptToast = document.getElementById('payment-attempt-toast');
        const errorToast = document.getElementById('error-toast');
        const errorToastMessage = document.getElementById('error-toast-message');

        let currentProductData = {}; // Store current product data
        let countdownInterval = null; // Variable for countdown timer

        // --- Product Click Handler ---
        function handleProductClick(button) {
            try {
                currentProductData = JSON.parse(button.dataset.product);
                // Ensure stock is treated as a number
                currentProductData.stock = parseInt(currentProductData.stock || 0, 10); // Parse base stock
                if (currentProductData.variants) {
                    currentProductData.variants.forEach(v => v.stock = parseInt(v.stock || 0, 10)); // Parse variant stock
                }
            } catch (e) {
                console.error("Error parsing product data:", e);
                showErrorToast('Data produk tidak valid.');
                return;
            }

             // Check if the base product itself is out of stock (for non-variant case)
             if (!currentProductData.has_variants && currentProductData.stock <= 0) {
                 showErrorToast('Stok produk ini habis.', 3000);
                 return; // Don't proceed if base product is out of stock
             }
             // Check if all variants are out of stock
             let allVariantsOutOfStock = true;
             if (currentProductData.has_variants && currentProductData.variants) {
                 for (const variant of currentProductData.variants) {
                     if (variant.stock > 0) {
                         allVariantsOutOfStock = false;
                         break;
                     }
                 }
             } else if (currentProductData.has_variants && (!currentProductData.variants || currentProductData.variants.length === 0)) {
                 allVariantsOutOfStock = true; // No variants means out of stock for variant product
             }

            if (currentProductData.has_variants && allVariantsOutOfStock) {
                showErrorToast('Semua varian produk ini habis.', 3000);
                return; // Don't proceed if all variants are out of stock
            }


            if (currentProductData.has_variants && currentProductData.variants && currentProductData.variants.length > 0) {
                 openVariantSelectionModal(currentProductData);
            } else {
                 if (currentProductData.price === undefined || currentProductData.price === null || currentProductData.price <= 0) {
                      showErrorToast('Harga produk ini tidak valid.', 3000);
                      return;
                 }
                 // Pass ACTUAL stock to checkout modal
                 openCheckoutModal(currentProductData.id, null, currentProductData.name, currentProductData.desc, currentProductData.price, currentProductData.stock);
            }
        }

        // --- Variant Selection Modal (Uses ACTUAL stock) ---
        function openVariantSelectionModal(productData) {
            modalVariantProductNameEl.textContent = productData.name;
            modalVariantProductDescEl.textContent = productData.desc || '';
            baseProductIdInput.value = productData.id;
            baseProductNameInput.value = productData.name; // Store base name
            baseProductDescInput.value = productData.desc || ''; // Store base desc
            variantListEl.innerHTML = '';
            selectVariantAndCheckoutBtn.disabled = true;
            selectedVariantIdInput.value = '';
            selectedVariantPriceInput.value = '';
            selectedVariantNameInput.value = '';
            selectedVariantStockInput.value = '0'; // Reset stock
            variantQuantityInput.value = 1; // Reset quantity
            variantQuantityInput.max = 1;   // Reset max
            variantQuantityError.classList.add('hidden');

            productData.variants.forEach(variant => {
                const isOutOfStock = variant.stock <= 0; // Use the actual stock count
                const listItem = document.createElement('label');
                listItem.className = `variant-item-radio flex items-center w-full rounded-xl cursor-pointer ${isOutOfStock ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-700/50'}`;
                listItem.innerHTML = `
                    <input type="radio" name="selected_variant" value="${variant.id}" data-price="${variant.price}" data-name="${variant.name}" data-stock="${variant.stock}" class="hidden" ${isOutOfStock ? 'disabled' : ''}>
                    <div class="flex flex-grow justify-between p-3 border border-gray-300 dark:border-gray-600 rounded-lg">
                        <div>
                            <span class="variant-name font-medium text-gray-900 dark:text-white block">${variant.name}</span>
                            <span class="variant-stock text-xs text-gray-500 dark:text-gray-400 block">${isOutOfStock ? 'Habis (Stok 0)' : `Stok: ${variant.stock}`}</span>
                        </div>
                        <div class="variant-price font-bold text-lg text-brand-icon-light dark:text-brand-icon-dark">Rp ${parseInt(variant.price).toLocaleString('id-ID')}</div>
                    </div>
                `;
                listItem.querySelector('input[type="radio"]').addEventListener('change', function() {
                    selectedVariantIdInput.value = this.value;
                    selectedVariantPriceInput.value = this.dataset.price;
                    selectedVariantNameInput.value = this.dataset.name;
                    selectedVariantStockInput.value = this.dataset.stock; // Store stock
                    selectVariantAndCheckoutBtn.disabled = false;
                    const maxQty = parseInt(this.dataset.stock, 10);
                    variantQuantityInput.max = maxQty > 0 ? maxQty : 1;
                    if (parseInt(variantQuantityInput.value, 10) > maxQty) {
                        variantQuantityInput.value = maxQty > 0 ? maxQty : 1;
                    }
                    variantQuantityError.classList.add('hidden');
                });
                variantListEl.appendChild(listItem);
            });

            variantSelectionModalEl.classList.remove('hidden', 'modal-leave-active');
            variantSelectionModalEl.classList.add('modal-enter-active');
        }

        // --- Event Listener for "Pilih dan Lanjut Bayar" button ---
        selectVariantAndCheckoutBtn.addEventListener('click', function() {
            const productId = baseProductIdInput.value;
            const variantId = selectedVariantIdInput.value;
            const baseName = baseProductNameInput.value;
            const baseDesc = baseProductDescInput.value;
            const variantName = selectedVariantNameInput.value;
            const variantPrice = selectedVariantPriceInput.value;
            const variantStock = selectedVariantStockInput.value;
            const quantity = parseInt(variantQuantityInput.value, 10);

            if (!variantId || !variantPrice || !variantName || !productId || !baseName) {
                console.error("Missing data to open checkout modal from variant selection.");
                showErrorToast("Gagal memproses pilihan varian.");
                return;
            }

            const checkoutProductName = `${baseName} - ${variantName}`;
            // Pass the selected quantity to the checkout modal
            openCheckoutModal(productId, variantId, checkoutProductName, baseDesc, variantPrice, variantStock, quantity);
            closeVariantSelectionModal();
        });


        // --- Checkout Modal (Uses ACTUAL stock) ---
        function openCheckoutModal(productId, variantId = null, productName, productDesc, productPrice, maxStock, initialQuantity = 1) {
             const basePrice = parseInt(productPrice, 10);
             maxStock = parseInt(maxStock, 10); // Ensure stock is integer

             modalProductIdInput.value = productId;
             modalVariantIdInput.value = variantId || '';
             modalProductNameEl.textContent = productName;
             modalProductDescEl.textContent = productDesc || '';
             modalBasePriceInput.value = basePrice;
             modalMaxStockInput.value = maxStock; // Use actual max stock

             // Set initial quantity and max value
             checkoutQuantityInput.value = initialQuantity;
             checkoutQuantityInput.max = maxStock > 0 ? maxStock : 1;
             if (initialQuantity > maxStock && maxStock > 0) {
                  checkoutQuantityInput.value = maxStock;
             } else if (maxStock <= 0) {
                  checkoutQuantityInput.value = 1; // Default to 1 even if stock is 0 initially
                  checkoutQuantityError.textContent = `Stok habis.`;
                  checkoutQuantityError.classList.remove('hidden');
                  payButtonEl.disabled = true; // Disable pay button
             } else {
                  checkoutQuantityError.classList.add('hidden');
                  payButtonEl.disabled = false; // Enable pay button
             }

             updateTotalPrice();

             // Reset form state
             checkoutErrorEl.classList.add('hidden');
             buyerNameInput.classList.remove('border-red-500', 'dark:border-red-500');
             buyerEmailInput.classList.remove('border-red-500', 'dark:border-red-500');
             buyerNameError.classList.add('hidden');
             buyerEmailError.classList.add('hidden');
             setLoading(false);

             checkoutModalEl.classList.remove('hidden', 'modal-leave-active');
             checkoutModalEl.classList.add('modal-enter-active');
        }

        // --- Quantity Change Function ---
        function changeQuantity(inputId, delta) {
            const inputElement = document.getElementById(inputId);
            const errorElementId = inputId === 'checkoutQuantity' ? 'checkoutQuantityError' : 'variantQuantityError';
            const errorElement = document.getElementById(errorElementId);
            // Determine which max stock input to use based on context
            const maxStockInputId = inputId === 'checkoutQuantity' ? 'modalMaxStock' : 'selectedVariantStock';
            const maxStockInput = document.getElementById(maxStockInputId);
            const maxStock = maxStockInput ? parseInt(maxStockInput.value, 10) : 0; // Default to 0 if element not found

            let currentValue = parseInt(inputElement.value, 10);
            let newValue = currentValue + delta;

            if (isNaN(maxStock) || maxStock <= 0) {
                 if (errorElement) {
                     errorElement.textContent = `Stok habis.`;
                     errorElement.classList.remove('hidden');
                 }
                 inputElement.value = 1;
                 if (inputId === 'checkoutQuantity') {
                     updateTotalPrice();
                     payButtonEl.disabled = true; // Disable pay button if stock is 0
                 }
                 return;
            }

            // Clamp value between 1 and maxStock
            if (newValue < 1) {
                newValue = 1;
            } else if (newValue > maxStock) {
                newValue = maxStock;
                if(errorElement) {
                    errorElement.textContent = `Stok hanya tersisa ${maxStock}.`;
                    errorElement.classList.remove('hidden');
                }
            } else {
                 if(errorElement) errorElement.classList.add('hidden'); // Hide error if valid
            }

            inputElement.value = newValue;

            // Update total price and button state only if in checkout modal
            if (inputId === 'checkoutQuantity') {
                updateTotalPrice();
                payButtonEl.disabled = false; // Ensure button is enabled if quantity is valid
            }
        }


        // --- Update Total Price ---
        function updateTotalPrice() {
            const basePrice = parseInt(modalBasePriceInput.value, 10);
            const quantity = parseInt(checkoutQuantityInput.value, 10);
            const totalPrice = basePrice * quantity;
            modalProductPriceEl.textContent = `Rp ${totalPrice.toLocaleString('id-ID')}`;
        }
        // --- Form Validation & Submission ---
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(String(email).toLowerCase());
        }

        checkoutFormEl.addEventListener('submit', async (e) => {
            e.preventDefault();
            let isValid = true;
            // Reset errors
            checkoutErrorEl.classList.add('hidden');
            checkoutQuantityError.classList.add('hidden');
            buyerNameInput.classList.remove('border-red-500', 'dark:border-red-500');
            buyerEmailInput.classList.remove('border-red-500', 'dark:border-red-500');
            buyerNameError.classList.add('hidden');
            buyerEmailError.classList.add('hidden');

            const buyerName = buyerNameInput.value.trim();
            const buyerEmail = buyerEmailInput.value.trim();
            const quantity = parseInt(checkoutQuantityInput.value, 10); // Get quantity
            const maxStock = parseInt(modalMaxStockInput.value, 10); // Get max stock

            // Validate inputs
            if (!buyerName) {
                buyerNameInput.classList.add('border-red-500', 'dark:border-red-500');
                buyerNameError.classList.remove('hidden');
                isValid = false;
            }
            if (!buyerEmail || !validateEmail(buyerEmail)) {
                buyerEmailInput.classList.add('border-red-500', 'dark:border-red-500');
                buyerEmailError.classList.remove('hidden');
                isValid = false;
            }
            // Validate quantity against stock
             if (isNaN(quantity) || quantity < 1 || (maxStock > 0 && quantity > maxStock) || maxStock <= 0) {
                 checkoutQuantityError.textContent = maxStock > 0 ? `Jumlah harus antara 1 dan ${maxStock}.` : 'Stok habis.';
                 checkoutQuantityError.classList.remove('hidden');
                 isValid = false;
                 payButtonEl.disabled = true; // Ensure button is disabled
             } else {
                 payButtonEl.disabled = false; // Ensure button is enabled if valid
             }


            if (!isValid) return;

            setLoading(true); // Show loading state
            const payload = {
                productId: modalProductIdInput.value,
                variantId: modalVariantIdInput.value || null,
                quantity: quantity, // Send quantity
                name: buyerName,
                email: buyerEmail,
            };

            try {
                // Send data to backend to get Payment Info
                const response = await fetch('<?= site_url('payment/pay-product') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken // Kirim CSRF Token
                    },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();

                 // Update CSRF token if backend sends a new one (optional but good practice)
                if (result.csrf_hash) {
                    csrfToken = result.csrf_hash;
                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    if (csrfMeta) csrfMeta.setAttribute('content', csrfToken);
                }


                if (!response.ok) {
                    // Check if it's a stock error from the backend
                    if (response.status === 400 && result.error && (result.error.toLowerCase().includes("stok") || result.error.toLowerCase().includes("stock"))) {
                         // Update max stock based on error message if possible, or set to 0
                         const match = result.error.match(/tersisa (\d+)/);
                         const remainingStock = match ? parseInt(match[1], 10) : 0;
                         modalMaxStockInput.value = remainingStock;
                         changeQuantity('checkoutQuantity', 0); // Re-validate/update quantity display
                         // No need to disable pay button here, changeQuantity handles it
                         throw new Error(result.error); // Throw the specific error from backend
                    }
                    // Throw generic error otherwise
                    throw new Error(result.error || `Error ${response.status}: ${response.statusText}`);
                }


                 // --- HANDLE DIFFERENT GATEWAY RESPONSES ---
                if (result.token) { // Midtrans Snap Token
                    closeCheckoutModal();
                    snap.pay(result.token, {
                        onSuccess: (result) => {
                             console.log('Payment Success:', result);
                             window.location.href = '<?= current_url() ?>?payment=success';
                        },
                        onPending: (result) => {
                             console.log('Payment Pending:', result);
                             window.location.href = '<?= current_url() ?>?payment=attempted';
                        },
                        onError: (result) => {
                            console.error('Payment Error:', result);
                            showErrorToast('Pembayaran gagal atau dibatalkan.');
                            setLoading(false); // Reset loading state on error
                        },
                        onClose: () => {
                             console.log('Payment popup closed');
                             setLoading(false); // Reset loading state on close
                        }
                    });
                } else if (result.gateway === 'tripay' && result.checkoutUrl) { // Tripay Checkout URL
                    closeCheckoutModal();
                    // Redirect or open in new tab (Tripay recommends redirect)
                    window.location.href = result.checkoutUrl;
                    // Note: If Tripay also returns QR, you might need similar logic as Orderkuota
                } else if (result.gateway === 'orderkuota' && result.qrUrl) { // NEW: Orderkuota QRIS
                    closeCheckoutModal();
                    openQrisModal(result.qrUrl, result.paidAmount, result.expiry, result.orderId, result.reference);
                }
                // --- END HANDLE GATEWAY RESPONSES ---
                else {
                     throw new Error('Respons pembayaran tidak dikenal atau tidak valid dari server.');
                }
            } catch (error) {
                console.error('Checkout Error:', error);
                showErrorInModal(error.message || 'Terjadi kesalahan. Silakan coba lagi.');
                setLoading(false);
            }
        });

         // --- Manual Detail Modal ---
         function openManualDetailModal(button) {
            modalManualIconEl.src = button.dataset.productIcon;
            modalManualNameEl.textContent = button.dataset.productName;
            modalManualDescEl.textContent = button.dataset.productDesc || '';
            const price = parseInt(button.dataset.productPrice);
            modalManualPriceEl.textContent = price > 0 ? `Rp ${price.toLocaleString('id-ID')}` : 'Harga via WhatsApp';
            modalManualWhatsappLinkEl.href = button.dataset.whatsappLink;
            manualDetailModalEl.classList.remove('hidden', 'modal-leave-active');
            manualDetailModalEl.classList.add('modal-enter-active');
        }

        // --- TOS Modal ---
        function openTosModal() {
            tosModalEl.classList.remove('hidden', 'modal-leave-active');
            tosModalEl.classList.add('modal-enter-active');
        }

        // --- QRIS Modal Functions (NEW) ---
        function openQrisModal(qrUrl, amount, expiryString, orderId, referenceId) {
            if (!qrUrl) {
                qrisImageEl.style.display = 'none';
                qrisErrorEl.classList.remove('hidden');
                qrisAmountEl.textContent = 'Rp -';
                qrisExpiryEl.textContent = 'Error';
            } else {
                qrisImageEl.src = qrUrl;
                qrisImageEl.style.display = 'block';
                qrisErrorEl.classList.add('hidden');
                qrisAmountEl.textContent = `Rp ${parseInt(amount).toLocaleString('id-ID')}`;
                qrisOrderIdInput.value = orderId;
                qrisReferenceIdInput.value = referenceId;

                // Set expiry and start countdown
                if (expiryString) {
                    try {
                        // Coba parse expiryString (asumsi format YYYY-MM-DD HH:MM:SS dari server)
                        // Perlu penyesuaian jika formatnya beda
                        // Penting: Menggunakan waktu server (+7 WIB) relatif terhadap waktu lokal browser
                        const serverTimeWIB = new Date(expiryString.replace(' ', 'T') + '+07:00');
                        const expiryTimestamp = serverTimeWIB.getTime(); // Get timestamp in milliseconds (UTC based)

                        // Debug: Tampilkan waktu server dan lokal
                        console.log("Server Expiry (WIB):", expiryString);
                        console.log("Parsed Expiry Date Obj:", serverTimeWIB);
                        console.log("Expiry Timestamp (UTC ms):", expiryTimestamp);
                        console.log("Current Browser Time (ms):", new Date().getTime());


                        qrisExpiryTimestampInput.value = expiryTimestamp;
                        startCountdown(expiryTimestamp);
                    } catch (e) {
                         console.error("Error parsing expiry date:", e);
                         qrisExpiryEl.textContent = "Error";
                         qrisExpiryTimestampInput.value = "";
                    }
                } else {
                    qrisExpiryEl.textContent = "Tidak ada batas waktu";
                    qrisExpiryTimestampInput.value = "";
                }
            }

            // Reset check status button state
            checkQrisStatusBtn.disabled = false;
            checkQrisSpinner.classList.add('hidden');
            checkQrisBtnText.textContent = 'Cek Status Pembayaran';

            qrisModalEl.classList.remove('hidden', 'modal-leave-active');
            qrisModalEl.classList.add('modal-enter-active');
        }


        function closeQrisModal() {
            closeModal(qrisModalEl);
            if (countdownInterval) {
                clearInterval(countdownInterval); // Stop timer
                countdownInterval = null;
            }
        }
        qrisModalEl.addEventListener('click', (e) => { if (e.target === qrisModalEl) closeQrisModal(); });

        // --- Countdown Timer Function (NEW) ---
        function startCountdown(expiryTimestamp) {
            if (countdownInterval) clearInterval(countdownInterval); // Clear existing timer

            if (!expiryTimestamp) {
                qrisExpiryEl.textContent = "N/A";
                return;
            }

            countdownInterval = setInterval(() => {
                const now = new Date().getTime(); // Waktu browser saat ini (UTC ms)
                const distance = expiryTimestamp - now;

                if (distance < 0) {
                    clearInterval(countdownInterval);
                    countdownInterval = null;
                    qrisExpiryEl.textContent = "Kedaluwarsa";
                    // Optionally disable check status button or show message
                    checkQrisStatusBtn.disabled = true;
                    checkQrisBtnText.textContent = 'Transaksi Kedaluwarsa';
                    return;
                }

                // Kalkulasi waktu
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                // Format tampilan (MM:SS)
                qrisExpiryEl.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }, 1000);
        }

        // --- Manual Check QRIS Status (NEW - Placeholder Endpoint) ---
        async function checkOrderStatusManual() {
            const orderId = qrisOrderIdInput.value;
            const referenceId = qrisReferenceIdInput.value;
            const expiryTimestamp = qrisExpiryTimestampInput.value;

            if (!referenceId || !orderId) {
                showErrorToast("Tidak ada ID transaksi untuk diperiksa.");
                return;
            }

            // Cek jika sudah expired dari timer
            const now = new Date().getTime();
            if (expiryTimestamp && now > parseInt(expiryTimestamp, 10)) {
                 showErrorToast("Transaksi sudah kedaluwarsa.");
                 checkQrisStatusBtn.disabled = true;
                 checkQrisBtnText.textContent = 'Transaksi Kedaluwarsa';
                 if (countdownInterval) clearInterval(countdownInterval);
                 qrisExpiryEl.textContent = "Kedaluwarsa";
                 return;
            }


            checkQrisStatusBtn.disabled = true;
            checkQrisSpinner.classList.remove('hidden');
            checkQrisBtnText.textContent = 'Memeriksa...';

            try {
                // --- Panggil endpoint backend untuk cek status ---
                // Pastikan endpoint ini aman dan hanya bisa diakses oleh user yang berhak
                // Endpoint ini akan memanggil ZeppelinClient::checkStatus di backend
                const response = await fetch('<?= site_url('payment/orderkuota/check_status') ?>', { // Ganti dengan URL endpoint Anda
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken // Kirim CSRF Token
                    },
                    body: JSON.stringify({ referenceId: referenceId }) // Kirim referenceId ke backend
                });

                 // Update CSRF token jika backend mengirim yang baru
                const newCsrf = response.headers.get('X-CSRF-TOKEN');
                if (newCsrf) {
                     csrfToken = newCsrf;
                     const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                     if (csrfMeta) csrfMeta.setAttribute('content', csrfToken);
                }


                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.error || `Gagal memeriksa status (${response.status})`);
                }

                const status = result.status ? result.status.toLowerCase() : 'unknown';

                if (status === 'success' || status === 'paid') {
                    closeQrisModal();
                    window.location.href = '<?= current_url() ?>?payment=success'; // Redirect ke halaman sukses
                } else if (status === 'failed' || status === 'expired') {
                     closeQrisModal();
                     showErrorToast(`Pembayaran ${status === 'expired' ? 'kedaluwarsa' : 'gagal'}. Silakan coba lagi.`);
                     if (countdownInterval) clearInterval(countdownInterval);
                     qrisExpiryEl.textContent = status === 'expired' ? "Kedaluwarsa" : "Gagal";
                     checkQrisBtnText.textContent = status === 'expired' ? 'Transaksi Kedaluwarsa' : 'Pembayaran Gagal';

                } else { // Masih pending atau status lain
                     showErrorToast("Pembayaran masih menunggu atau belum selesai. Silakan coba cek lagi nanti.");
                     checkQrisStatusBtn.disabled = false; // Enable tombol lagi
                     checkQrisSpinner.classList.add('hidden');
                     checkQrisBtnText.textContent = 'Cek Status Pembayaran';
                }

            } catch (error) {
                console.error("Error checking status:", error);
                showErrorToast(error.message || "Gagal memeriksa status. Coba lagi.");
                checkQrisStatusBtn.disabled = false; // Enable tombol lagi
                checkQrisSpinner.classList.add('hidden');
                checkQrisBtnText.textContent = 'Cek Status Pembayaran';
            }
        }


        // --- Close Modal Functions ---
        function closeModal(modalEl) {
            modalEl.classList.add('modal-leave-active');
            modalEl.classList.remove('modal-enter-active');
            setTimeout(() => modalEl.classList.add('hidden'), 300);
        }
        function closeCheckoutModal() { closeModal(checkoutModalEl); }
        function closeManualDetailModal() { closeModal(manualDetailModalEl); }
        function closeVariantSelectionModal() { closeModal(variantSelectionModalEl); }
        function closeTosModal() { closeModal(tosModalEl); }
        checkoutModalEl.addEventListener('click', (e) => { if (e.target === checkoutModalEl) closeCheckoutModal(); });
        manualDetailModalEl.addEventListener('click', (e) => { if (e.target === manualDetailModalEl) closeManualDetailModal(); });
        variantSelectionModalEl.addEventListener('click', (e) => { if (e.target === variantSelectionModalEl) closeVariantSelectionModal(); });
        tosModalEl.addEventListener('click', (e) => { if (e.target === tosModalEl) closeTosModal(); });
        qrisModalEl.addEventListener('click', (e) => { if (e.target === qrisModalEl) closeQrisModal(); });


        // --- UI Helper Functions ---
        function setLoading(isLoading) {
            payButtonEl.disabled = isLoading;
            payButtonTextEl.style.display = isLoading ? 'none' : 'flex';
            payButtonSpinnerEl.style.display = isLoading ? 'block' : 'none';
        }
        function showErrorInModal(message) {
            checkoutErrorEl.textContent = message;
            checkoutErrorEl.classList.remove('hidden');
        }
        function showErrorToast(message, duration = 5000) {
            errorToastMessage.textContent = message;
            errorToast.classList.remove('hidden', 'fade-out');
            // Clear any existing fade-out timeout
            if (errorToast.timeoutId) clearTimeout(errorToast.timeoutId);
            errorToast.timeoutId = setTimeout(() => {
                 errorToast.classList.add('fade-out')
                 setTimeout(() => errorToast.classList.add('hidden'), 500); // Wait for fade-out animation
            }, duration);
        }

        // --- Toast Display on Page Load ---
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const paymentStatus = urlParams.get('payment');
            let toastToShow = null;
            let duration = 5000; // 5 seconds for toast

            if (paymentStatus === 'success') toastToShow = successToast;
            else if (paymentStatus === 'attempted' || paymentStatus === 'pending') toastToShow = attemptToast;

             if (toastToShow) {
                 toastToShow.classList.remove('hidden', 'fade-out'); // Ensure it's visible and reset animation
                 // Clear existing timeout if any
                 if (toastToShow.timeoutId) clearTimeout(toastToShow.timeoutId);
                 // Set new timeout to fade out
                 toastToShow.timeoutId = setTimeout(() => {
                     toastToShow.classList.add('fade-out');
                     // Optionally remove from DOM after fade out, or just hide
                     setTimeout(() => toastToShow.classList.add('hidden'), 500); // 0.5s fade out duration
                      // Clean URL after showing toast
                      window.history.replaceState(null, '', window.location.pathname);
                 }, duration);
             } else {
                 // Ensure all toasts are hidden initially if no status parameter
                 if (successToast) successToast.classList.add('hidden');
                 if (attemptToast) attemptToast.classList.add('hidden');
                 if (errorToast) errorToast.classList.add('hidden');
             }
        });


         // --- CSRF Setup for Fetch ---
         // Dapatkan token CSRF awal dari meta tag
         let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
         if (!csrfToken) console.warn('CSRF meta tag not found.');

         // Override fungsi fetch global untuk menambahkan header CSRF dan token di body (jika perlu)
         const originalFetch = window.fetch;
         window.fetch = async (url, options) => {
             // Hanya tambahkan header jika bukan request GET dan token tersedia
             if (options && options.method && options.method.toUpperCase() !== 'GET' && csrfToken) {
                 // Pastikan headers adalah objek
                 if (!options.headers) {
                     options.headers = {};
                 }
                 // Tambahkan header X-CSRF-TOKEN
                 options.headers['X-CSRF-TOKEN'] = csrfToken;
             }

             // Lakukan fetch asli
             const response = await originalFetch(url, options);

             // Cek jika server mengirim header X-CSRF-TOKEN baru (CI4 biasanya tidak default)
             // Jika Anda mengkonfigurasi CI4 untuk mengirim token baru di header, uncomment ini
             /*
             const newCsrfToken = response.headers.get('X-CSRF-TOKEN');
             if (newCsrfToken) {
                 csrfToken = newCsrfToken;
                 const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                 if (csrfMeta) csrfMeta.setAttribute('content', csrfToken);
                 console.log('New CSRF token received from header:', csrfToken);
             }
             */

             // Cek jika server mengirim token baru dalam body JSON (cara umum CI4 dengan AJAX)
             // Clone response untuk membaca body tanpa mengganggu response asli
            if (response.headers.get('content-type')?.includes('application/json')) {
                try {
                    const clonedResponse = response.clone();
                    const bodyJson = await clonedResponse.json();
                    if (bodyJson && bodyJson.csrf_hash) { // Sesuaikan 'csrf_hash' jika nama field berbeda
                         csrfToken = bodyJson.csrf_hash;
                         const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                         if (csrfMeta) csrfMeta.setAttribute('content', csrfToken);
                         console.log('New CSRF token received from JSON body:', csrfToken);
                    }
                } catch (e) {
                     // Abaikan jika body bukan JSON valid atau tidak ada token
                    // console.warn('Could not parse JSON body for CSRF token:', e);
                }
            }


             return response; // Kembalikan response asli
         };

    </script>

</body>
</html>

