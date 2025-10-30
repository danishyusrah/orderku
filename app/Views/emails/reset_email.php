<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Password Akun Itsku ID Anda</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji"; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f4f4f7; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(to right, #6d28d9, #4f46e5); color: #ffffff; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 30px 35px; }
        .content p { margin-bottom: 20px; color: #555; font-size: 15px; }
        .content strong { color: #333; }
        .button { display: inline-block; background-color: #4f46e5; color: #ffffff; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 15px; margin-top: 10px; margin-bottom: 20px; }
        .button:hover { background-color: #4338ca; }
        .link { word-break: break-all; color: #4f46e5; }
        .footer { background-color: #f0f0f5; color: #888; padding: 20px; text-align: center; font-size: 12px; border-top: 1px solid #e0e0e0; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Permintaan Reset Password</h1>
        </div>
        <div class="content">
            <p>Halo <?= esc($username) ?>,</p>
            <p>Kami menerima permintaan untuk mereset password akun Itsku ID Anda yang terkait dengan email ini.</p>
            <p>Jika Anda merasa tidak melakukan permintaan ini, abaikan saja email ini.</p>
            <p>Untuk melanjutkan proses reset password, silakan klik tombol di bawah ini:</p>

            <a href="<?= $resetLink ?>" class="button">Reset Password Saya</a>

            <p>Tombol di atas akan mengarahkan Anda ke halaman untuk membuat password baru. Link ini hanya berlaku selama <?= $expiryMinutes ?> menit.</p>

            <p>Jika tombol di atas tidak berfungsi, salin dan tempel URL berikut ke browser Anda:</p>
            <p><a href="<?= $resetLink ?>" class="link"><?= $resetLink ?></a></p>

            <p>Terima kasih,<br>Tim Itsku ID</p>
        </div>
        <div class="footer">
            <p>&copy; <?= date('Y') ?> Itsku ID. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
