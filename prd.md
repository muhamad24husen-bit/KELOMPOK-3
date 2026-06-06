# Product Requirements Document (PRD)
**Modul:** Sistem Manajemen Peran & Otorisasi (RBAC & Multi-Tenancy)
**Proyek:** Building Node Sensor Monitoring System (BNSMS)
**Fokus Utama:** Isolasi Data Klien & Keamanan Operasional

---

## 1. Pendahuluan
### 1.1 Tujuan Modul
Membangun sistem hak akses berjenjang yang memastikan pemisahan tegas antara pengelola *software* (SaaS Provider), pemilik gedung (Tenant), dan staf operasional. Sistem ini wajib menjamin tidak ada kebocoran data (suhu ruangan, kontrol perangkat) antar entitas yang berbeda.

### 1.2 Masalah yang Diselesaikan
* Mencegah pengguna dari Perusahaan A melihat atau mengendalikan perangkat keras milik Perusahaan B.
* Membatasi staf biasa (*Viewer*) agar tidak bisa sembarangan mematikan sistem penting di ruang server tanpa izin otoritas (*Operator*).
* Menghindari teknisi lapangan (*Maintenance*) dari kebingungan akibat akses ke dasbor finansial/kuota klien.

---

## 2. Matriks Hak Akses (Permissions Matrix)

Tabel berikut menentukan wewenang spesifik (Create, Read, Update, Delete, Execute) untuk masing-masing entitas:

| Fitur / Modul | Super Admin | Client (Pemilik) | Maintenance | Operator | Viewer |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Manajemen Akun Client** | Akses Penuh | Akses Sendiri | Ditolak | Ditolak | Ditolak |
| **Gedung & Ruangan** | Akses Baca | Akses Penuh | Akses Baca | Akses Baca | Ditolak |
| **Manajemen Staf (Akun)** | Ditolak | Akses Penuh | Ditolak | Ditolak | Ditolak |
| **Registrasi Alat (ESP32)** | Ditolak | Ditolak | Akses Penuh | Akses Baca | Ditolak |
| **Konfigurasi I/O Mapping**| Ditolak | Ditolak | Akses Penuh | Akses Baca | Ditolak |
| **Live Monitoring (Data)**| Ditolak | Semua Ruang | Semua Ruang | Semua Ruang | Ruang Sendiri |
| **Kendali Perangkat (AC/Lampu)**| Ditolak | Ditolak | Diagnostik | Eksekusi | Ditolak (Hanya Request) |
| **Approval System (Tiket)** | Ditolak | Ditolak | Ditolak | Eksekusi (Acc/Reject) | Ditolak |

---

## 3. Skenario Penggunaan & Alur Otorisasi

### 3.1 Isolasi Tenant (The Client Scope)
* **Kondisi:** User *Client* login.
* **Aksi:** Saat melihat daftar Gedung, sistem secara otomatis menambahkan filter (Global Scope) `WHERE client_id = X`.
* **Hasil:** Klien hanya melihat gedung yang didaftarkan menggunakan akun perusahaannya.

### 3.2 Skenario Lintas-Akses (The Operation Request)
* **Kondisi:** User *Viewer* login dan melihat ruangannya panas.
* **Aksi:** Viewer tidak memiliki tombol "Turunkan Suhu AC". Ia menekan tombol "Kirim Permintaan Bantuan".
* **Proses:** Sistem mencatat tiket di `operation_requests`.
* **Hasil:** User *Operator* menerima notifikasi tiket tersebut. Hanya *Operator* yang memiliki wewenang mengirim *command MQTT* ke perangkat fisik.

---

## 4. Spesifikasi Teknis (Implementasi Laravel 13)

### 4.1 Struktur Database yang Relevan
1.  **`users`**: Tabel utama untuk autentikasi login (menyimpan `email`, `password`, dan `global_role`).
2.  **`clients`**: Profil perusahaan, bertindak sebagai jangkar *Tenant ID*.
3.  **`staff`**: Tabel perantara (Pivot/Relational). Menyimpan informasi bahwa `user_id` tertentu adalah pekerja untuk `client_id` tertentu dengan `staff_role` (Operator/Maintenance/Viewer).

### 4.2 Skema Middleware (Gatekeepers)
Aplikasi harus menggunakan perlindungan Middleware berlapis:

* **`CheckGlobalRole`:** Memastikan akses URL tingkat atas (contoh: rute `/superadmin/clients` hanya bisa diakses `global_role = super_admin`).
* **`TenantIdentifier`:** Otomatis mendeteksi pengguna yang login dan memuat `client_id` mereka ke dalam Session atau Laravel Service Container.
* **`CheckStaffRole`:** Melindungi URL tingkat operasional (contoh: rute `/dashboard/discovery-node` hanya bisa diakses oleh `staff_role = maintenance`).

### 4.3 Contoh Logika Laravel Global Scope (Tenant Isolation)
Untuk mencegah kebocoran data, setiap Model aset (seperti Model `Building`, `Room`, `Node`) wajib menggunakan mekanisme ini:

```php
protected static function booted()
{
    static::addGlobalScope('client', function (Builder $builder) {
        if (auth()->check() && auth()->user()->global_role !== 'super_admin') {
            // Memfilter semua query berdasarkan client_id user yang sedang login
            $builder->where('client_id', auth()->user()->staff_profile->client_id);
        }
    });
}