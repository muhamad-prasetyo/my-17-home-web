# Dokumentasi Proyek Aplikasi Absensi (Effiwork)

Dokumen ini menjelaskan cara instalasi, konfigurasi, dan penggunaan dasar untuk backend Laravel dan frontend Flutter dari aplikasi absensi Effiwork.

## Daftar Isi
1.  [Dokumentasi Backend (Laravel)](#dokumentasi-backend-laravel)
    *   [Prasyarat](#prasyarat-laravel)
    *   [Instalasi Laravel](#instalasi-laravel)
    *   [Konfigurasi Laravel](#konfigurasi-laravel)
    *   [Menjalankan Server Laravel](#menjalankan-server-laravel)
    *   [Struktur Database & Migrasi](#struktur-database--migrasi)
    *   [Endpoint API Utama](#endpoint-api-utama)
2.  [Dokumentasi Frontend (Flutter)](#dokumentasi-frontend-flutter)
    *   [Prasyarat](#prasyarat-flutter)
    *   [Instalasi Flutter](#instalasi-flutter)
    *   [Konfigurasi Flutter](#konfigurasi-flutter)
    *   [Menjalankan Aplikasi Flutter](#menjalankan-aplikasi-flutter)
    *   [Fitur Utama Aplikasi](#fitur-utama-aplikasi)
    *   [Konfigurasi Dinamis Base URL (Testing)](#konfigurasi-dinamis-base-url-testing)

---

## 1. Dokumentasi Backend (Laravel)

### Prasyarat Laravel
*   PHP (versi yang sesuai dengan proyek Laravel Anda, misal: >= 8.1)
*   Composer (Dependency Manager untuk PHP)
*   Web Server (opsional untuk development, karena Laravel punya server bawaan. Contoh: Apache, Nginx)
*   Database (MySQL, PostgreSQL, SQLite, dll. yang didukung Laravel)
*   Git (untuk clone repository)

### Instalasi Laravel
1.  **Clone Repository Backend:**
    ```bash
    git clone [URL_REPOSITORY_LARAVEL_ANDA] laravel_effiwork_backend
    cd laravel_effiwork_backend
    ```
2.  **Install Dependencies PHP:**
    ```bash
    composer install
    ```
3.  **Buat File Environment:**
    Salin file `.env.example` menjadi `.env`.
    ```bash
    cp .env.example .env
    ```
4.  **Generate Application Key:**
    ```bash
    php artisan key:generate
    ```

### Konfigurasi Laravel
Buka dan edit file `.env` untuk mengatur konfigurasi utama:

1.  **Konfigurasi Database:**
    Sesuaikan detail koneksi database Anda:
    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=nama_database_anda
    DB_USERNAME=username_database_anda
    DB_PASSWORD=password_database_anda
    ```
2.  **URL Aplikasi (Opsional untuk API):**
    ```env
    APP_URL=http://localhost
    ```
3.  **Konfigurasi Mail (Jika ada fitur email):**
    Sesuaikan dengan layanan email yang Anda gunakan.
4.  **Konfigurasi Lainnya:**
    Periksa variabel environment lain yang mungkin spesifik untuk proyek Anda (misalnya, key untuk layanan pihak ketiga).

### Menjalankan Server Laravel
Untuk development, Anda bisa menggunakan server bawaan Laravel:
```bash
php artisan serve
```
Secara default, server akan berjalan di `http://127.0.0.1:8000`.

Jika Anda ingin server bisa diakses dari perangkat lain di jaringan yang sama (misalnya dari aplikasi Flutter di smartphone), jalankan dengan host `0.0.0.0`:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```
Pastikan firewall di mesin Anda mengizinkan koneksi masuk pada port yang digunakan.

### Struktur Database & Migrasi
Proyek ini menggunakan Laravel Migrations untuk mengelola skema database.
1.  **Jalankan Migrasi:**
    Untuk membuat tabel-tabel yang dibutuhkan di database Anda (sesuai dengan konfigurasi di `.env`):
    ```bash
    php artisan migrate
    ```
2.  **Seeders (Opsional):**
    Jika proyek memiliki database seeders untuk mengisi data awal (misalnya data admin, role, dll.):
    ```bash
    php artisan db:seed
    ```
    Atau jika ada seeder spesifik:
    ```bash
    php artisan db:seed --class=NamaSeederClass
    ```

### Endpoint API Utama
Berikut adalah beberapa contoh endpoint API yang mungkin ada (sesuaikan dengan implementasi Anda):
*   `POST /api/login`: Login pengguna.
*   `POST /api/logout`: Logout pengguna.
*   `GET /api/user`: Mendapatkan data pengguna yang sedang login.
*   `POST /api/user/update-profile`: Memperbarui profil pengguna.
*   `POST /api/update-password`: Mengubah kata sandi pengguna.
*   `POST /api/checkin`: Melakukan absensi masuk.
*   `POST /api/checkout`: Melakukan absensi keluar.
*   `GET /api/attendances`: Mendapatkan riwayat absensi.
*   `POST /api/leave-requests`: Mengajukan izin/cuti.
*   `GET /api/leave-requests`: Mendapatkan daftar izin/cuti.
*   `GET /api/company`: Mendapatkan detail perusahaan (untuk QR Code, dll.).
*   *(Tambahkan endpoint lain yang relevan di sini)*

---

## 2. Dokumentasi Frontend (Flutter)

### Prasyarat Flutter
*   Flutter SDK (versi yang stabil dan sesuai dengan proyek)
*   IDE (Android Studio, VS Code dengan ekstensi Flutter & Dart)
*   Emulator Android / iOS Simulator, atau perangkat fisik Android/iOS
*   Git (untuk clone repository)

### Instalasi Flutter
1.  **Clone Repository Frontend:**
    ```bash
    git clone [URL_REPOSITORY_FLUTTER_ANDA] flutter_effiwork_app
    cd flutter_effiwork_app
    ```
2.  **Install Dependencies Flutter:**
    ```bash
    flutter pub get
    ```
3.  **Konfigurasi Firebase (Jika Belum):**
    *   Pastikan file `firebase_options.dart` sudah ada dan terkonfigurasi dengan benar untuk proyek Firebase Anda (Android & iOS).
    *   Untuk Android, pastikan file `google-services.json` ada di `android/app/`.
    *   Untuk iOS, pastikan file `GoogleService-Info.plist` ada di `ios/Runner/` dan ditambahkan ke target Xcode.

### Konfigurasi Flutter
1.  **Konfigurasi `baseUrl` Backend:**
    Aplikasi ini menggunakan file `.env` di root direktori proyek Flutter untuk menentukan alamat API backend.
    *   Buat file bernama `.env` di root direktori `flutter_effiwork_app/`.
    *   Isi file `.env` dengan format berikut, sesuaikan dengan alamat IP dan port server Laravel Anda:
        ```env
        BASE_URL=http://ALAMAT_IP_LARAVEL_ANDA:PORT
        ```
        Contoh:
        ```env
        BASE_URL=http://192.168.112.13:8000
        ```
        Atau jika Laravel berjalan di mesin yang sama dengan emulator Android:
        ```env
        BASE_URL=http://10.0.2.2:8000
        ```
    *   Aplikasi akan otomatis memuat konfigurasi ini saat startup. Jika tidak ada file `.env` atau `BASE_URL` tidak diset di sana, aplikasi akan menggunakan nilai default yang ada di `lib/core/constants/variables.dart`.

2.  **App Icon & Splash Screen:**
    Konfigurasi untuk `flutter_launcher_icons` dan `flutter_native_splash` ada di `pubspec.yaml`. Jika Anda ingin mengubah logo:
    *   Ganti file `assets/images/logo_app.png` dengan logo baru Anda.
    *   Jalankan perintah berikut untuk memperbarui ikon dan splash screen:
        ```bash
        flutter pub get
        flutter pub run flutter_launcher_icons:main
        flutter pub run flutter_native_splash:create
        flutter clean
        ```
        (Pastikan `flutter pub get` dijalankan jika ada perubahan pada `pubspec.yaml` atau untuk memastikan semua dependensi dev terbaru.)

### Menjalankan Aplikasi Flutter
1.  **Pastikan Backend Laravel Berjalan:** Server Laravel harus aktif dan bisa diakses dari jaringan yang sama dengan perangkat/emulator Flutter.
2.  **Pilih Device/Emulator:** Pastikan device atau emulator Anda terdeteksi oleh Flutter (`flutter devices`).
3.  **Jalankan Aplikasi:**
    ```bash
    flutter run
    ```
    Untuk menjalankan pada device tertentu:
    ```bash
    flutter run -d [DEVICE_ID]
    ```

### Fitur Utama Aplikasi
*(Detailkan fitur-fitur yang sudah ada di sini, contoh:)*
*   Login & Logout Pengguna
*   Tampilan Dashboard (Ringkasan Kehadiran, Jadwal)
*   Absensi Masuk & Keluar (menggunakan QR Code dan/atau pengenalan wajah)
*   Riwayat Absensi Harian dan Bulanan
*   Pengajuan Izin/Cuti dengan detail dan lampiran
*   Daftar dan Status Pengajuan Izin/Cuti
*   Halaman Profil Pengguna (Informasi Pribadi, Kesehatan, Kontak)
*   Edit Profil Pengguna
*   Ubah Kata Sandi
*   Notifikasi Real-time (via Firebase Cloud Messaging) untuk status absensi, pengajuan izin, dll.
*   Pengaturan Aplikasi:
    *   Pengaturan Akun (Ubah Profil, Kata Sandi)
    *   Konfigurasi Base URL dinamis (untuk keperluan testing)
    *   Bahasa (jika diimplementasikan)
    *   Tema (jika diimplementasikan)

### Konfigurasi Dinamis Base URL (Testing)
Untuk mempermudah testing dengan berbagai alamat IP backend tanpa mengubah file `.env` dan merestart aplikasi setiap saat:
1.  Buka aplikasi Effiwork di smartphone/emulator.
2.  Navigasi ke menu **Pengaturan** (biasanya ikon gear atau dari tab profil).
3.  Pilih item **"Konfigurasi Base URL (Testing)"**.
4.  Di halaman ini, Anda akan melihat `Base URL Aktif Saat Ini`. Ini bisa berasal dari:
    *   URL yang Anda setel manual sebelumnya (disimpan di SharedPreferences).
    *   URL dari file `.env` Anda.
    *   URL default aplikasi jika keduanya di atas tidak ada.
5.  **Untuk Mengubah `baseUrl`:**
    *   Masukkan alamat URL backend Laravel yang baru (contoh: `http://192.168.1.100:8000`) ke dalam field "Set Custom Base URL".
    *   Tekan tombol **"Simpan & Terapkan"**. URL baru ini akan disimpan di SharedPreferences dan menjadi aktif.
6.  **Untuk Mereset ke Konfigurasi Default/Env:**
    *   Kosongkan field "Set Custom Base URL" lalu tekan **"Simpan & Terapkan"**. Ini akan menghapus URL kustom dari SharedPreferences.
    *   Atau, tekan tombol **"Reset ke Default/Env"**. Ini juga akan menghapus URL kustom dari SharedPreferences.
    *   Setelah direset, aplikasi akan kembali menggunakan `BASE_URL` dari file `.env` Anda, atau nilai default jika `.env` tidak ada/tidak valid.
7.  **Penting:**
    *   Setelah mengubah `baseUrl` melalui fitur ini, beberapa bagian aplikasi mungkin memerlukan **restart penuh aplikasi** (tutup paksa lalu buka kembali) agar perubahan diterapkan sepenuhnya, terutama untuk koneksi jaringan atau service yang sudah diinisialisasi dengan `baseUrl` sebelumnya.
    *   Fitur ini ditujukan **hanya untuk keperluan testing dan development**.

---