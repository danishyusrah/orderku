<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Produk Digital Anda Telah Tiba!</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji"; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f4f4f7; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(to right, #6d28d9, #4f46e5); color: #ffffff; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 26px; font-weight: 600; }
        .content { padding: 30px 35px; }
        .content p { margin-bottom: 20px; color: #555; font-size: 15px; }
        .content strong { color: #333; font-weight: 600; } /* Perbaiki font-weight */
        .stock-data-box { background-color: #f9f9fb; border: 1px dashed #d1d1d6; border-radius: 6px; padding: 15px 20px; margin: 15px 0; font-size: 14px; color: #2d2d2d; line-height: 1.6; overflow-wrap: break-word; }
        /* Style for definition list (dl) - Diperbaiki */
        .stock-data-box dl { margin: 0; padding: 0; }
        .stock-data-box dt { font-weight: bold; color: #555; display: block; /* Ubah ke block */ margin-bottom: 3px; font-size: 13px; } /* Sesuaikan ukuran font & margin */
        .stock-data-box dd {
            margin-left: 0; /* Hapus margin kiri */
            margin-bottom: 10px; /* Tambah jarak bawah */
            font-family: 'Courier New', Courier, monospace;
            word-break: break-word; /* Ubah ke break-word agar tidak memotong di tengah kata */
            background-color: #eee; /* Tambah latar belakang untuk menyorot */
            padding: 5px 8px; /* Tambah padding */
            border-radius: 4px; /* Tambah radius sudut */
            display: block; /* Pastikan block */
            line-height: 1.4; /* Sesuaikan line-height */
            white-space: pre-wrap; /* Jaga spasi tapi wrap */
        }
        .stock-data-box dd a { color: #4f46e5; text-decoration: underline; }
        .stock-data-box .error-message { color: #dc2626; font-style: italic; font-size: 12px; }
        .stock-data-box .raw-data pre { font-size: 12px; background-color: #eee; padding: 5px; border-radius: 4px; color: #555; }
        .item-separator { border-top: 1px solid #eee; margin-top: 15px; padding-top: 15px; }
        .footer { background-color: #f0f0f5; color: #888; padding: 20px; text-align: center; font-size: 12px; border-top: 1px solid #e0e0e0; }
        .footer a { color: #6d28d9; text-decoration: none; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Pesanan Selesai & Produk Anda!</h1>
        </div>
        <div class="content">
            <p>Halo <strong><?= esc($buyer_name) ?></strong>,</p>
            <p>Terima kasih banyak telah melakukan pembelian <?= ($quantity > 1) ? esc($quantity) . ' item' : '' ?> produk "<strong><?= esc($product_name) ?></strong>". Kami sangat menghargainya!</p>

            <?php if ($quantity > 1): ?>
                <p>Berikut adalah <?= esc($quantity) ?> data produk digital yang Anda pesan:</p>
                <?php foreach ($decodedStockDataArray as $index => $stockData): ?>
                    <div class="stock-data-box <?= ($index > 0) ? 'item-separator' : '' ?>">
                        <p style="margin-bottom: 10px; font-size: 12px; color: #777;"><strong>Item ke-<?= ($index + 1) ?>:</strong></p>
                        <?php if (isset($stockData->error)): ?>
                             <p class="error-message"><?= esc($stockData->error) ?></p>
                             <?php if(isset($stockData->raw_data)): ?>
                             <div class="raw-data">
                                 <p style="font-size:11px; color:#555; margin-bottom:3px;">Data asli:</p>
                                 <pre><?= esc($stockData->raw_data) ?></pre>
                             </div>
                             <?php endif; ?>
                        <?php else: ?>
                            <dl>
                                <?php if (isset($stockData->email)): ?>
                                    <dt>Email:</dt>
                                    <dd><?= esc($stockData->email) ?></dd>
                                <?php endif; ?>
                                <?php if (isset($stockData->password)): ?>
                                    <dt>Password:</dt>
                                    <dd><?= esc($stockData->password) ?></dd> <!-- Consider masking -->
                                <?php endif; ?>
                                <?php if (isset($stockData->{'2fa'}) && !empty($stockData->{'2fa'})): ?>
                                    <dt>2FA:</dt>
                                    <dd><?= esc($stockData->{'2fa'}) ?></dd>
                                <?php endif; ?>
                                <?php if (isset($stockData->gdrive_link) && !empty($stockData->gdrive_link)): ?>
                                    <dt>Link:</dt>
                                    <dd><a href="<?= esc($stockData->gdrive_link, 'attr') ?>" target="_blank" rel="noopener noreferrer"><?= esc($stockData->gdrive_link) ?></a></dd>
                                <?php endif; ?>
                                <?php
                                    // Handle any other fields that might exist in the JSON
                                    $knownKeys = ['email', 'password', '2fa', 'gdrive_link', 'error', 'raw_data'];
                                    foreach ($stockData as $key => $value) {
                                        if (!in_array($key, $knownKeys) && !empty($value)) {
                                            echo '<dt>' . esc(ucfirst(str_replace('_', ' ', $key))) . ':</dt>';
                                            // Format tautan jika value adalah URL
                                            if (filter_var($value, FILTER_VALIDATE_URL)) {
                                                echo '<dd><a href="' . esc($value, 'attr') . '" target="_blank" rel="noopener noreferrer">' . esc($value) . '</a></dd>';
                                            } else {
                                                echo '<dd>' . esc($value) . '</dd>';
                                            }
                                        }
                                    }
                                ?>
                            </dl>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: // Single item purchase ?>
                <p>Berikut adalah data produk digital yang Anda pesan:</p>
                <?php $stockData = $decodedStockDataArray[0] ?? null; ?>
                <div class="stock-data-box">
                    <?php if (!$stockData): ?>
                         <p class="error-message">Data produk tidak tersedia.</p>
                    <?php elseif (isset($stockData->error)): ?>
                         <p class="error-message"><?= esc($stockData->error) ?></p>
                         <?php if(isset($stockData->raw_data)): ?>
                         <div class="raw-data">
                             <p style="font-size:11px; color:#555; margin-bottom:3px;">Data asli:</p>
                             <pre><?= esc($stockData->raw_data) ?></pre>
                         </div>
                         <?php endif; ?>
                    <?php else: ?>
                        <dl>
                            <?php if (isset($stockData->email)): ?>
                                <dt>Email:</dt>
                                <dd><?= esc($stockData->email) ?></dd>
                            <?php endif; ?>
                            <?php if (isset($stockData->password)): ?>
                                <dt>Password:</dt>
                                <dd><?= esc($stockData->password) ?></dd> <!-- Consider masking -->
                            <?php endif; ?>
                            <?php if (isset($stockData->{'2fa'}) && !empty($stockData->{'2fa'})): ?>
                                <dt>2FA:</dt>
                                <dd><?= esc($stockData->{'2fa'}) ?></dd>
                            <?php endif; ?>
                            <?php if (isset($stockData->gdrive_link) && !empty($stockData->gdrive_link)): ?>
                                <dt>Link:</dt>
                                <dd><a href="<?= esc($stockData->gdrive_link, 'attr') ?>" target="_blank" rel="noopener noreferrer"><?= esc($stockData->gdrive_link) ?></a></dd>
                            <?php endif; ?>
                             <?php
                                // Handle any other fields
                                $knownKeys = ['email', 'password', '2fa', 'gdrive_link', 'error', 'raw_data'];
                                foreach ($stockData as $key => $value) {
                                    if (!in_array($key, $knownKeys) && !empty($value)) {
                                        echo '<dt>' . esc(ucfirst(str_replace('_', ' ', $key))) . ':</dt>';
                                         // Format tautan jika value adalah URL
                                        if (filter_var($value, FILTER_VALIDATE_URL)) {
                                            echo '<dd><a href="' . esc($value, 'attr') . '" target="_blank" rel="noopener noreferrer">' . esc($value) . '</a></dd>';
                                        } else {
                                            echo '<dd>' . esc($value) . '</dd>';
                                        }
                                    }
                                }
                            ?>
                        </dl>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <p>Silakan salin dan gunakan data di atas sesuai kebutuhan.</p>
            <p>Jika Anda memiliki pertanyaan atau mengalami kendala, jangan ragu untuk membalas email ini atau menghubungi Customer Service kami.</p>
            <p>Terima kasih sekali lagi & selamat menikmati produk Anda!</p>

        </div>
        <div class="footer">
            <p>&copy; <?= date('Y') ?> <?= esc(env('email.fromName', 'Toko Anda')) ?>. All rights reserved.</p>
            <!-- <p><a href="#">Kunjungi Toko</a> | <a href="#">Hubungi Kami</a></p> -->
        </div>
    </div>
</body>
</html>
