<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orderku - Permudah Penjualan Digital Anda</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #1a4d2e; /* Warna hijau tua yang mirip */
        }
        /* Efek gradasi subtle untuk latar belakang */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at top left, rgba(67, 137, 84, 0.3), transparent 40%),
                        radial-gradient(circle at bottom right, rgba(255, 215, 0, 0.15), transparent 50%);
            opacity: 0.8;
            z-index: -1;
            pointer-events: none;
        }
        /* Styling tambahan jika diperlukan */
        .hero-image-container {
            perspective: 1000px;
        }
        .hero-image {
            transform: rotateY(-10deg) rotateX(5deg);
            box-shadow: -10px 10px 30px rgba(0,0,0,0.4);
            transition: transform 0.3s ease-out;
        }
        .hero-image:hover {
             transform: rotateY(0deg) rotateX(0deg);
        }
    </style>
</head>
<body class="text-gray-100 antialiased overflow-x-hidden">

    <!-- Header -->
    <header class="py-5 px-4 md:px-8">
        <nav class="container mx-auto flex justify-between items-center">
            <!-- Logo/Nama Situs -->
            <div class="flex items-center space-x-2">
                 <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-gradient-to-br from-yellow-400 to-amber-500 shadow-lg">
                    <!-- Placeholder icon -->
                     <svg class="w-5 h-5 text-green-900" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                 </div>
                 <span class="text-xl font-bold text-white">Orderku</span>
            </div>

            <!-- Tombol Navigasi -->
            <div class="flex items-center space-x-3">
                <a href="/login" class="px-4 py-2 text-sm font-medium text-white bg-white/10 hover:bg-white/20 rounded-full transition duration-200">
                    Masuk
                </a>
                <a href="/daftar" class="px-4 py-2 text-sm font-medium text-green-900 bg-yellow-400 hover:bg-yellow-300 rounded-full transition duration-200 shadow-md">
                    Daftar Gratis
                </a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 md:px-8 py-16 md:py-24 flex flex-col md:flex-row items-center gap-12 md:gap-16">

        <!-- Kolom Teks -->
        <div class="md:w-1/2 text-center md:text-left">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-yellow-300 leading-tight mb-6">
                Permudah penjualan produk digital Anda.
            </h1>
            <p class="text-lg md:text-xl text-gray-200 mb-8 leading-relaxed">
                Platform terbaik untuk menjual produk digital, mengelola stok otomatis, dan menerima pembayaran dengan mudah. Semua dalam satu tautan sederhana.
            </p>
            <a href="/daftar" class="inline-block px-8 py-3 text-lg font-semibold text-green-900 bg-pink-300 hover:bg-pink-200 rounded-full transition duration-200 shadow-lg transform hover:scale-105">
                Coba Gratis Sekarang
            </a>
            <p class="text-xs text-gray-400 mt-4">*Mulai dengan akun gratis, upgrade kapan saja.</p>
        </div>

        <!-- Kolom Gambar -->
        <div class="md:w-1/2 flex justify-center hero-image-container">
             <!-- Placeholder Gambar Mockup Telepon -->
             <!-- Ganti dengan gambar yang lebih relevan jika ada -->
                         <div class="bg-gray-800 rounded-3xl p-4 w-64 md:w-72 shadow-2xl border-4 border-gray-700 hero-image">
                 <div class="aspect-video bg-gray-700 rounded-lg mb-4 flex items-center justify-center">
                    <span class="text-gray-500 text-sm">Contoh Tampilan Toko</span>
                 </div>
                 <div class="space-y-2">
                    <div class="h-4 bg-gray-700 rounded w-3/4"></div>
                    <div class="h-3 bg-gray-700 rounded w-1/2"></div>
                    <div class="h-8 bg-blue-500 rounded-lg w-full mt-4"></div>
                     <div class="h-8 bg-gray-700 rounded-lg w-full"></div>
                 </div>
             </div>
        </div>

    </main>

    <!-- Bagian Fitur (Contoh) -->
    <section class="bg-green-950/30 py-16 md:py-24">
        <div class="container mx-auto px-4 md:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-12">Fitur Unggulan</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-12">
                <!-- Fitur 1 -->
                <div class="bg-white/5 p-6 rounded-xl border border-white/10 hover:border-white/20 transition duration-300">
                     <div class="text-yellow-400 mb-4 text-3xl"><i class="fas fa-link"></i></div>
                    <h3 class="text-xl font-semibold text-white mb-2">Satu Link Simpel</h3>
                    <p class="text-gray-300 text-sm">Bagikan semua produk digital Anda melalui satu tautan bio yang mudah diingat.</p>
                </div>
                <!-- Fitur 2 -->
                <div class="bg-white/5 p-6 rounded-xl border border-white/10 hover:border-white/20 transition duration-300">
                    <div class="text-yellow-400 mb-4 text-3xl"><i class="fas fa-cogs"></i></div>
                    <h3 class="text-xl font-semibold text-white mb-2">Otomatisasi Stok</h3>
                    <p class="text-gray-300 text-sm">Kelola stok produk digital (akun, lisensi, dll) secara otomatis setelah pembayaran.</p>
                </div>
                <!-- Fitur 3 -->
                <div class="bg-white/5 p-6 rounded-xl border border-white/10 hover:border-white/20 transition duration-300">
                    <div class="text-yellow-400 mb-4 text-3xl"><i class="fas fa-credit-card"></i></div>
                    <h3 class="text-xl font-semibold text-white mb-2">Pembayaran Mudah</h3>
                    <p class="text-gray-300 text-sm">Integrasi dengan payment gateway populer untuk transaksi yang lancar.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-green-950/50 py-8 mt-16">
        <div class="container mx-auto px-4 md:px-8 text-center text-gray-400 text-sm">
            &copy; <?= date('Y') ?> Orderku. Hak Cipta Dilindungi Undang-undang.
            <!-- Tambahkan link lain jika perlu -->
        </div>
    </footer>

    <!-- Font Awesome (jika menggunakan ikon) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/js/all.min.js" xintegrity="sha512-u3fPA7V8qQmhBPNT5quvaXVa1mnnLSXUep5PS1qo5NRzHwG19aHmNJnj1Q8hpA/iksyIFlGVpSEjyErZcwB1JQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</body>
</html>
