# Reset Password melalui SMTP TLS

Fitur reset password mengirim tautan sekali pakai melalui mailer default Laravel. Untuk pengiriman nyata, isi konfigurasi berikut pada `.env` deployment:

```dotenv
APP_URL=https://inventaris.example.go.id

MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_REQUIRE_TLS=true
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

`MAIL_SCHEME=smtp` pada port `587` menggunakan STARTTLS. `MAIL_REQUIRE_TLS=true` memastikan koneksi gagal jika server SMTP tidak menyediakan TLS, sehingga kredensial dan isi email tidak dikirim melalui koneksi tanpa enkripsi.

Setelah mengubah `.env`, bersihkan cache konfigurasi:

```bash
php artisan config:clear
```

Pastikan `APP_URL` dapat diakses oleh penerima email karena nilai tersebut digunakan untuk membuat tautan reset. Jangan commit username, password SMTP, atau app password ke repository.
