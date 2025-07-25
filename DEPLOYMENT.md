# Deployment Guide - Sistem Absensi Otomatis

Dokumen ini adalah panduan lengkap untuk melakukan deployment aplikasi absensi ke berbagai lingkungan, termasuk PC lokal, server VPS, dan Shared Hosting.

---

## 🖥️ A. Setup di PC Lokal/Baru (Windows)

Gunakan bagian ini jika Anda ingin menjalankan aplikasi di komputer Windows lain.

### 1. Persiapan Awal
- **Clone Project**: `git clone <repository-url>`
- **Masuk ke Direktori**: `cd laravel-absensi-backend-master`

### 2. Install Dependencies
```bash
composer install
```

### 3. Setup Environment
```bash
# Salin file environment
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Konfigurasi Database & Migrasi
- Buka file `.env` dan sesuaikan koneksi database Anda (DB_HOST, DB_DATABASE, dll.).
- Jalankan migrasi:
  ```bash
  php artisan migrate
  ```

### 5. Setup Penjadwal Tugas (Task Scheduler)
Untuk menjalankan proses penandaan "Alfa" secara otomatis.
1. Tekan `Windows + R`, ketik `taskschd.msc`, lalu Enter.
2. Klik **"Create Basic Task..."**.
3. **Name**: `Laravel Scheduler Absensi`
4. **Trigger**: Pilih **Daily** dan set waktu ke `02:00:00`.
5. **Action**: Pilih **Start a program**.
   - **Program/script**: `C:\path\to\your\php.exe` (Contoh: `C:\xampp\php\php.exe`)
   - **Add arguments**: `C:\path\to\your\project\artisan schedule:run`
   - **Start in**: `C:\path\to\your\project\`
6. Klik **Finish**.

---

## ☁️ B. Deploy ke VPS (Linux - Ubuntu/CentOS)

Gunakan bagian ini untuk server yang Anda kelola sendiri. Diasumsikan Anda sudah memiliki akses SSH dan server dengan LEMP/LAMP stack terinstal.

### 1. Upload Project ke Server
Login ke server Anda via SSH, lalu clone project dari repository Git.
```bash
# Pindah ke direktori web root (umumnya /var/www/html)
cd /var/www/html

# Clone project
git clone <your-repository-url.git> laravel-absensi
cd laravel-absensi
```

### 2. Install Dependencies & Setup Environment
```bash
# Install dependency Composer (mode produksi)
composer install --no-dev --optimize-autoloader

# Salin dan siapkan file .env
cp .env.example .env
php artisan key:generate
```

### 3. Konfigurasi Database
- Buat database baru di MySQL/MariaDB.
- Buka file `.env` menggunakan `nano` atau `vim`: `nano .env`
- Masukkan informasi koneksi database Anda (DB_DATABASE, DB_USERNAME, DB_PASSWORD).
- Jalankan migrasi dan seeder (jika ada):
  ```bash
  php artisan migrate --seed
  ```

### 4. Konfigurasi Web Server (Contoh Nginx)
Buat file konfigurasi server block baru.
```bash
sudo nano /etc/nginx/sites-available/laravel-absensi
```
Isi dengan konfigurasi berikut (sesuaikan `server_name` dan `root`):
```nginx
server {
    listen 80;
    server_name your_domain.com www.your_domain.com;
    root /var/www/html/laravel-absensi/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php index.html index.htm;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock; # Sesuaikan versi PHP Anda
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```
Aktifkan konfigurasi dan restart Nginx.
```bash
# Buat symbolic link
sudo ln -s /etc/nginx/sites-available/laravel-absensi /etc/nginx/sites-enabled/

# Test konfigurasi Nginx
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

### 5. Atur Hak Akses (Permissions)
Web server memerlukan izin tulis ke beberapa direktori.
```bash
# Berikan kepemilikan ke user web server (umumnya www-data)
sudo chown -R www-data:www-data storage bootstrap/cache

# Atur izin direktori dan file
sudo chmod -R 775 storage
sudo chmod -R 775 bootstrap/cache
```

### 6. Setup Cron Job untuk Otomatisasi
Gunakan script yang sudah disediakan untuk setup otomatis.
```bash
# Beri izin eksekusi pada script
chmod +x setup-cron.sh

# Jalankan script
./setup-cron.sh
```
Jika script gagal, lakukan secara manual:
```bash
# Buka editor crontab
crontab -e

# Tambahkan baris ini di paling bawah, lalu simpan
* * * * * cd /var/www/html/laravel-absensi && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🌐 C. Deploy ke Shared Hosting (cPanel)

Deploy di Shared Hosting sedikit berbeda, terutama dalam struktur direktori dan setup cron job.

### 1. Persiapan & Upload
- **Kompres Proyek**: Buat file `.zip` dari semua isi project Anda (kecuali folder `vendor`).
- **Upload**: Gunakan **File Manager** di cPanel untuk meng-upload file `.zip` ke root direktori Anda (misalnya, di luar `public_html`).
- **Ekstrak**: Ekstrak file `.zip` tersebut.

### 2. Install Dependencies
Jika Anda punya akses SSH, jalankan `composer install --no-dev`. Jika tidak, Anda harus meng-upload folder `vendor` dari komputer lokal Anda.

### 3. Setup Database
- Gunakan wizard **"MySQL Databases"** di cPanel untuk membuat database, user, dan password baru.
- Jangan lupa untuk memberikan hak akses penuh (All Privileges) user ke database tersebut.

### 4. Konfigurasi `.env`
- Salin `env.example` menjadi `.env`.
- Edit file `.env` melalui File Manager dan masukkan informasi database yang baru Anda buat.
- Set `APP_ENV=production` dan `APP_DEBUG=false`.

### 5. Pindahkan File dari `public`
Struktur Shared Hosting biasanya menggunakan `public_html` sebagai web root.
- Pindahkan **semua isi** dari folder `laravel-absensi/public` ke dalam folder `public_html`.
- Buka file `public_html/index.php` dan ubah dua baris path berikut:
  ```php
  // Ganti dari:
  require __DIR__.'/../vendor/autoload.php';
  $app = require_once __DIR__.'/../bootstrap/app.php';

  // Menjadi (sesuaikan 'laravel-absensi' dengan nama folder proyek Anda):
  require __DIR__.'/../laravel-absensi/vendor/autoload.php';
  $app = require_once __DIR__.'/../laravel-absensi/bootstrap/app.php';
  ```

### 6. Setup Cron Job di cPanel
- Cari menu **"Cron Jobs"** di cPanel.
- Di bagian "Add New Cron Job", atur jadwalnya. Pilih **"Once Per Minute"** (`* * * * *`).
- Di kolom **"Command"**, masukkan perintah berikut (pastikan path PHP dan artisan benar):
  ```bash
  /usr/local/bin/php /home/your_cpanel_user/laravel-absensi/artisan schedule:run >> /dev/null 2>&1
  ```
  *   *Anda bisa mendapatkan path PHP yang benar dari tim support hosting Anda.*
  *   *Ganti `your_cpanel_user` dengan username cPanel Anda.*
- Klik **"Add New Cron Job"**.

---

## 🚨 Troubleshooting Umum
- **Error 500**: Biasanya karena hak akses (permission) folder `storage` atau file `.env` yang salah. Cek log error di `storage/logs/laravel.log`.
- **Halaman Putih (Blank Page)**: Cek hak akses dan pastikan path di `public_html/index.php` sudah benar.
- **Cron Job Tidak Berjalan**: Pastikan path ke `php` dan `artisan` sudah benar. Coba jalankan command-nya langsung di terminal (jika ada akses SSH) untuk melihat error.

## 🔧 Konfigurasi Command

### Mengubah Waktu Eksekusi
Edit file `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule): void
{
    // Ubah waktu sesuai kebutuhan
    $schedule->command('attendance:mark-alfa')->dailyAt('02:00'); // Jam 2 pagi
    // Atau
    $schedule->command('attendance:mark-alfa')->dailyAt('06:00'); // Jam 6 pagi
}
```

### Logging
Command akan mencatat log di:
- `storage/logs/laravel.log`

## 🧪 Testing

### Test Command Manual
```bash
php artisan attendance:mark-alfa
```

### Test Scheduler
```bash
php artisan schedule:run
```

### Cek Log
```bash
tail -f storage/logs/laravel.log
```

## 📋 Checklist Deployment

### PC Windows
- [ ] Project ter-copy
- [ ] Dependencies ter-install
- [ ] Environment ter-setup
- [ ] Database ter-migrate
- [ ] Task Scheduler ter-setup

### VPS Linux
- [ ] Project ter-upload
- [ ] Dependencies ter-install
- [ ] Environment ter-setup
- [ ] Permissions ter-set
- [ ] Cron job ter-setup
- [ ] Command ter-test

## 🚨 Troubleshooting

### Command Tidak Berjalan
1. Cek log: `tail -f storage/logs/laravel.log`
2. Test manual: `php artisan attendance:mark-alfa`
3. Cek cron job: `crontab -l`

### Permission Denied
```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Database Connection Error
1. Cek konfigurasi `.env`
2. Test koneksi database
3. Jalankan `php artisan migrate:status`

## 📞 Support

Jika ada masalah, cek:
1. Log file di `storage/logs/laravel.log`
2. Laravel log: `php artisan log:clear && php artisan attendance:mark-alfa`
3. Cron log: `grep CRON /var/log/syslog` 