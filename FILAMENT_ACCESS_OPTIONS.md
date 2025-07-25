# Pilihan Konfigurasi Akses Filament

## Opsi Saat Ini (Semua Email Bisa Akses)
```php
public function canAccessPanel(Panel $panel): bool
{
    // Option 1: User dengan role admin
    if ($this->hasRole(['super_admin', 'admin', 'panel_user'])) {
        return true;
    }
    
    // Option 2: User yang diapprove secara manual
    if (isset($this->is_approved) && $this->is_approved) {
        return true;
    }
    
    // Option 3: Semua user dengan email verified
    if ($this->hasVerifiedEmail()) {
        return true;
    }
    
    // Option 4: Semua user yang login bisa akses
    return true;
}
```

## Pilihan Konfigurasi Lainnya

### 1. Hanya Role-Based (Paling Aman)
```php
public function canAccessPanel(Panel $panel): bool
{
    return $this->hasRole(['super_admin', 'admin', 'panel_user']);
}
```

### 2. Role + Manual Approval
```php
public function canAccessPanel(Panel $panel): bool
{
    return $this->hasRole(['super_admin', 'admin', 'panel_user']) || 
           (isset($this->is_approved) && $this->is_approved);
}
```

### 3. Hanya Email Verified (Saat Ini Dipakai)
```php
public function canAccessPanel(Panel $panel): bool
{
    return $this->hasVerifiedEmail();
}
```

### 4. Semua User Bisa Akses (Paling Permisif)
```php
public function canAccessPanel(Panel $panel): bool
{
    return true;
}
```

### 5. Berdasarkan Department
```php
public function canAccessPanel(Panel $panel): bool
{
    $allowedDepartments = ['IT', 'HR', 'Management'];
    return in_array($this->department, $allowedDepartments);
}
```

## Cara Menggunakan

Pilih salah satu opsi di atas dan ganti method `canAccessPanel` di `app/Models/User.php` sesuai kebutuhan Anda.

### Untuk Memberikan Role ke User:
```php
$user = User::find(1);
$user->assignRole('admin');
```

### Untuk Approve User Manual:
```php
$user = User::find(1);
$user->is_approved = true;
$user->save();
```

## Rekomendasi
- **Development**: Gunakan opsi 4 (semua user bisa akses)
- **Production**: Gunakan opsi 1 atau 2 (role-based) untuk keamanan
- **Semi-Production**: Gunakan opsi 3 (email verified) sebagai kompromi
