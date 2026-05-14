# 🧪 QC-System

> Sistem informasi berbasis web untuk pencatatan dan monitoring hasil uji mikrobiologi lingkungan di Departemen Quality Control.

---

## 📋 Deskripsi

**QC-System** adalah aplikasi web yang dirancang untuk mendukung operasional Departemen Quality Control dalam pencatatan hasil uji mikrobiologi lingkungan. Sistem ini menggantikan pencatatan manual dengan solusi digital yang terstruktur, terpusat, dan mudah diakses oleh berbagai level pengguna.

Aplikasi ini dibangun menggunakan **Laravel 11** dengan arsitektur **Domain-Driven Design (DDD)**, menerapkan pola **Service**, **Repository**, dan **DTO** untuk memastikan kode yang scalable, maintainable, dan testable.

---

## ✨ Fitur Utama

- 🔐 **Manajemen Pengguna & Autentikasi** — Multi-role access control (Super Admin, Admin QC, Analyst, Supervisor, Manager)
- 🏢 **Master Data** — Manajemen ruangan dan lokasi pengambilan sampel
- 📝 **Pencatatan Hasil Uji** — Input hasil uji mikrobiologi dengan validasi otomatis
- 📊 **Dashboard Monitoring** — Visualisasi data dan status pengujian real-time
- 🔍 **Penelusuran & Filter** — Pencarian data berdasarkan berbagai kriteria
- 📤 **Laporan** — Ekspor dan viewing laporan hasil uji
- 🔒 **Kebijakan Password** — Manajemen policy password dengan riwayat perubahan
- 📋 **Audit Trail** — Pencatatan aktivitas pengguna untuk compliance

---

## 🏗️ Arsitektur & Teknologi

### Stack Teknologi

- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: Blade Templates + Tailwind CSS + Alpine.js
- **Database**: MySQL 8.0+
- **Build Tool**: Vite
- **Package Manager**: Composer, npm

### Pola Arsitektur

Proyek ini menerapkan **Domain-Driven Design (DDD)** dengan struktur:

```
src/Domain/
├── User/
│   ├── Models/
│   ├── DTOs/
│   ├── Interfaces/
│   ├── Repositories/
│   └── Services/
├── Room/
├── Location/
├── PasswordPolicy/
└── ...
```

**Prinsip Utama:**
- **Thin Controller** — Controller hanya handle HTTP request/response
- **Service Layer** — Semua business logic di Service
- **Repository Pattern** — Akses data hanya melalui Repository
- **DTO Pattern** — Transfer data tervalidasi antar layer
- **SOLID Principles** — Kode yang fleksibel dan mudah diubah

---

## 🚀 Instalasi & Setup

### Prasyarat

Pastikan sudah terinstall:
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+
- Git

### Clone Repository

```bash
git clone https://github.com/qaininaa/skripsi-v2.git qc-system
cd qc-system
```

### Install Dependensi

```bash
# PHP dependencies
composer install

# Node dependencies
npm install
```

### Konfigurasi Environment

```bash
# Copy .env.example ke .env
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Setup Database

```bash
# Jalankan migration
php artisan migrate

# Seed data awal (users, password settings, rooms, locations)
php artisan db:seed
```

### Jalankan Aplikasi

**Terminal 1 — Development Server:**
```bash
php artisan serve
```

**Terminal 2 — Build Assets:**
```bash
npm run dev
```

Akses aplikasi di `http://localhost:8000`

## � Struktur Proyek

```
qc-system/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # HTTP Controllers
│   │   ├── Middleware/         # Custom middleware
│   │   └── Requests/           # Form Requests & validation
│   ├── Services/               # Application services
│   ├── Providers/              # Service providers
│   └── View/
│       ├── Components/         # Blade components
│       └── Composers/          # View composers
├── src/
│   └── Domain/                 # Domain layer (DDD)
│       ├── User/
│       ├── Room/
│       ├── Location/
│       ├── PasswordPolicy/
│       └── ...
├── database/
│   ├── migrations/             # Database migrations
│   ├── seeders/                # Database seeders
│   └── factories/              # Model factories
├── resources/
│   ├── views/                  # Blade templates
│   │   ├── layouts/
│   │   ├── components/
│   │   ├── dashboard/
│   │   ├── user-management/
│   │   ├── room-management/
│   │   └── location-management/
│   ├── css/                    # Tailwind CSS
│   └── js/                     # Alpine.js & utilities
├── public/
│   ├── icons/                  # SVG icons
│   └── images/                 # Static images
├── routes/
│   └── web.php                 # Web routes
├── config/                     # Configuration files
├── .env.example                # Environment template
├── composer.json
├── package.json
└── README.md
```

---

## 🔑 Fitur Utama & Akses

### Super Admin
- Manajemen pengguna (create, read, update, delete)
- Pengaturan kebijakan password
- Audit trail

### Admin QC
- Master data: Ruangan & Lokasi
- Manajemen laporan
- Tugas pelaporan

### Analyst
- Input laporan hasil uji
- View laporan

### Supervisor & Manager
- Review laporan masuk
- Laporan sedang dikerjakan
- Arsip laporan

---

## 🛠️ Development Guidelines

### Mengikuti AGENTS.md

Proyek ini menerapkan guidelines ketat yang terdokumentasi di `AGENTS.md`:

1. **Thin Controller** — Tidak ada business logic di controller
2. **Service Layer** — Semua logic di service
3. **Repository Pattern** — Akses data hanya via repository
4. **DTO Pattern** — Validasi & transformasi di request class
5. **View Composer** — Shared data via composer, bukan controller
6. **Naming Convention** — Konsisten dan deskriptif

### Membuat Fitur Baru

**Alur standar:**

1. **Migration** — Buat tabel di database
2. **Model** — Buat model di `src/Domain/{Entity}/Models/`
3. **DTO** — Buat DTO di `src/Domain/{Entity}/Dtos/`
4. **Interface** — Buat repository interface
5. **Repository** — Implementasi repository
6. **Service** — Buat service dengan business logic
7. **Request** — Buat form request dengan validasi & `toDTO()`
8. **Controller** — Thin controller yang memanggil service
9. **Views** — Buat blade templates
10. **Routes** — Daftarkan routes di `routes/web.php`
11. **Provider** — Bind interface ke implementation di `AppServiceProvider`

---

## 📝 Contoh: Membuat Fitur Baru

Misalnya membuat fitur "Report Type":

### 1. Migration
```bash
php artisan make:migration create_report_types_table
```

### 2. Model & Domain Structure
```
src/Domain/ReportType/
├── Models/ReportType.php
├── DTOs/
│   ├── CreateReportTypeDto.php
│   └── UpdateReportTypeDto.php
├── Interfaces/ReportTypeRepositoryInterface.php
├── Repositories/ReportTypeRepository.php
└── Services/ReportTypeService.php
```

### 3. Request & Validation
```
app/Http/Requests/ReportType/
├── ReportTypeStoreRequest.php
└── ReportTypeUpdateRequest.php
```

### 4. Controller
```
app/Http/Controllers/ReportType/ReportTypeController.php
```

### 5. Views
```
resources/views/report-type-management/
├── index.blade.php
├── create.blade.php
└── edit.blade.php
```

### 6. Routes
```php
Route::middleware('role:admin')->group(function () {
    Route::resource('report-types', ReportTypeController::class);
});
```

### 7. Provider Binding
```php
// AppServiceProvider.php
$this->app->bind(
    ReportTypeRepositoryInterface::class,
    ReportTypeRepository::class
);
```

---

## 🧪 Testing

```bash
# Run tests
php artisan test

# Run with coverage
php artisan test --coverage
```

---

## 📦 Build untuk Production

```bash
# Build assets
npm run build

# Optimize untuk production
php artisan optimize
php artisan config:cache
php artisan route:cache
```

---

## 🐛 Troubleshooting

### Database connection error
- Pastikan MySQL running
- Cek konfigurasi `.env` (DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD)

### Assets tidak ter-load
```bash
npm run build
php artisan storage:link
```

### Permission denied
```bash
chmod -R 775 storage bootstrap/cache
```

---

## 📚 Dokumentasi Tambahan

- **AGENTS.md** — Guidelines arsitektur & development
- **Laravel Documentation** — https://laravel.com/docs
- **Tailwind CSS** — https://tailwindcss.com/docs
- **Alpine.js** — https://alpinejs.dev/

---

## 👥 Tim Pengembang

- **Karina Ghaisani** — Developer

---

**Last Updated:** May 2026
