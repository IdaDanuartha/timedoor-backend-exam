# Timedoor Bookstore Management System

> Sistem manajemen toko buku berbasis Laravel 12.0 untuk mengelola koleksi buku, rating, dan rekomendasi pelanggan.

## Daftar Isi
- [Tentang Proyek](#tentang-proyek)
- [Fitur Utama](#fitur-utama)
- [Teknologi](#teknologi)
- [Instalasi](#instalasi)
- [Struktur Database](#struktur-database)
- [Penggunaan](#penggunaan)
- [Troubleshooting](#troubleshooting)

---

## Tentang Proyek

Aplikasi ini dibuat untuk membantu John Doe mengelola toko bukunya dengan fitur:
- Manajemen 100.000+ buku
- Tracking 500.000+ rating pelanggan
- Rekomendasi berbasis data
- Identifikasi buku dan penulis populer

### Requirements
- **Laravel**: 12.0
- **PHP**: 8.2
- **Database**: MySQL 8.0+
- **Memory**: 2GB RAM minimum
- **Disk**: 5GB minimum

---

## Fitur Utama

### 1. Daftar Buku dengan Filter
- **Filter**: Category (AND/OR logic), Author, Year, Availability, Location, Rating
- **Search**: Title, Author, ISBN, Publisher
- **Sort**: Rating, Votes, Recent Popularity, Alphabetical
- **Display**: Book info, Average rating, Total voters, Trending indicator, Availability

### 2. Top 20 Penulis
**3 Ranking Tabs:**
- **Popularity**: Berdasarkan jumlah voter (rating > 5)
- **Rating**: Berdasarkan rata-rata rating
- **Trending**: Penulis yang sedang naik daun (30 hari terakhir vs sebelumnya)

**Statistik:**
- Total ratings
- Best rated book
- Worst rated book

### 3. Rating System
- Pilih Author → Pilih Book (AJAX loading)
- Rating scale 1-10
- Optional review (max 1000 char)
- Validasi: 24 jam cooldown, no duplicate, book-author validation

---

## 🛠️ Teknologi

```
Backend:  Laravel 12.0, PHP 8.2, MySQL 8.0
Frontend: Blade, Tailwind CSS, JavaScript
Data:     Faker (1K authors, 3K categories, 100K books, 500K ratings)
```

**Penting:**
- ❌ No caching (Redis, file cache, etc.)
- ✅ Migrations & seeders only
- ✅ Database optimization dengan indexing
- ✅ Efficient large dataset handling

---

## Instalasi

### 1. Clone & Install
```bash
git clone https://github.com/IdaDanuartha/timedoor-backend-exam.git
cd johns-bookstore
composer install
```

### 2. Setup Environment
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure Database
Edit `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bookstore
DB_USERNAME=root
DB_PASSWORD=your_password

# Important: No caching
CACHE_DRIVER=array
```

### 4. Create Database
```bash
mysql -u root -p
CREATE DATABASE bookstore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### 5. Run Migration & Seeding
```bash
php artisan migrate
php artisan db:seed
# Seeding: ~8-12 menit untuk semua data
```

### 6. Start Server
```bash
php artisan serve
# Buka: http://localhost:8000
```

---

## Struktur Database

### Tables & Relationships

```
authors (1,000 records)
  ├─ id, name, email, bio
  └─ has many → books

categories (3,000 records)
  ├─ id, name, description
  └─ belongs to many → books

books (100,000 records)
  ├─ id, title, isbn, author_id, publisher
  ├─ publication_year, availability_status, store_location
  ├─ description, price
  ├─ belongs to → author
  ├─ belongs to many → categories
  └─ has many → ratings

ratings (500,000 records)
  ├─ id, book_id, user_id, rating (1-10)
  ├─ review, created_at
  └─ belongs to → book
```

### Key Indexes
```sql
-- Primary indexes
books: title, author_id, publication_year, availability_status, store_location
ratings: book_id, rating, created_at, user_id

-- Composite indexes
books: (author_id, availability_status)
ratings: (book_id, created_at)
```

---

## Penggunaan

### Halaman 1: Daftar Buku
**URL:** `http://localhost:8000/books`

**Fitur:**
- Filter multi-kriteria (category, author, year, status, location, rating)
- Search bar (title, author, ISBN, publisher)
- Sort options (rating, votes, trending, alphabetical)
- Pagination (50 buku per halaman)

**Contoh:**
```
Filter: Category = Fiction AND Romance
        Rating = 7-10
        Status = Available
Sort:   By Average Rating
Result: Menampilkan buku Fiction+Romance dengan rating tinggi yang available
```

### Halaman 2: Top Penulis
**URL:** `http://localhost:8000/authors/top`

**3 Tabs:**
1. **Popularity Tab**: Penulis dengan voter terbanyak (rating > 5)
2. **Rating Tab**: Penulis dengan rata-rata rating tertinggi
3. **Trending Tab**: Penulis dengan momentum tertinggi

**Formula Trending:**
```
Trending Score = (Avg 30 hari terakhir - Avg 30 hari sebelumnya) × log(Jumlah voter baru)
```

### Halaman 3: Input Rating
**URL:** `http://localhost:8000/ratings/create`

**Langkah:**
1. Pilih Author dari dropdown
2. Pilih Book (auto-load via AJAX)
3. Pilih Rating (1-10)
4. Tulis Review (optional)
5. Submit

**Validasi:**
- ✅ Book harus milik Author yang dipilih
- ✅ 1 rating per book per user
- ✅ Cooldown 24 jam antar rating
- ❌ Error jika duplicate atau terlalu cepat

**Rating Scale:**
```
10-9: Masterpiece
8:    Excellent
7:    Very Good
6:    Good
5:    Average
4-1:  Below Average to Terrible
```

---

## Troubleshooting

### Problem 1: Seeding Lama
**Solusi:**
```php
// Edit seeder, kurangi data untuk testing
$totalAuthors = 100;      // dari 1000
$totalBooks = 1000;       // dari 100000
$totalRatings = 5000;     // dari 500000
// Seeding: ~30 detik
```

### Problem 2: Memory Error
**Solusi:**
```bash
# Increase PHP memory
php -d memory_limit=2048M artisan db:seed
```

### Problem 3: Query Lambat
**Solusi:**
```bash
# Check indexes
mysql> SHOW INDEX FROM books;

# Optimize tables
mysql> ANALYZE TABLE books;
mysql> OPTIMIZE TABLE books;
```

### Problem 4: AJAX Book Not Loading
**Debug:**
```javascript
// Check console browser (F12)
// Verify endpoint: /api/books-by-author?author_id=X
```

### Problem 5: Duplicate Rating Tidak Dicegah
**Debug:**
```bash
# Check logs
tail -f storage/logs/laravel.log

# Test user identifier
# Coba dari browser berbeda atau clear cookies
```

---

## Performa

### Seeding Time
```
Authors:     ~200-500 miliseconds    (1,000 records)
Categories:  ~500-800 miliseconds    (3,000 records)
Books:       ~10-12 seconds          (100,000 records)
Ratings:     ~12-14 seconds          (500,000 records)
-------------------------------------------
Total:       ~23-27 seconds
```

### Query Performance
```
Books List Page:       ~150-200ms
Top Authors Page:      ~300-500ms
Rating Submission:     ~50ms
Search Operation:      ~100ms
Filter Operation:      ~200ms
```

### Database Size
```
Total Records:  ~604,000
Database Size:  ~1.5-2 GB
Index Size:     ~200-300 MB
```

---

## Development Notes

### Testing Mode (Quick)
```php
// Edit di masing-masing Seeder untuk testing cepat:
AuthorSeeder:    $totalAuthors = 100;
CategorySeeder:  $totalCategories = 300;
BookSeeder:      $totalBooks = 1000;
RatingSeeder:    $totalRatings = 5000;

// Run
php artisan migrate:fresh --seed
```

### Production Mode (Full)
```php
// Gunakan nilai default:
AuthorSeeder:    $totalAuthors = 1000;
CategorySeeder:  $totalCategories = 3000;
BookSeeder:      $totalBooks = 100000;
RatingSeeder:    $totalRatings = 500000;

// Run
php artisan migrate:fresh --seed
```

### Selective Seeding
```bash
# Seed hanya table tertentu
php artisan db:seed --class=AuthorSeeder
php artisan db:seed --class=CategorySeeder
php artisan db:seed --class=BookSeeder
# Skip RatingSeeder jika tidak perlu
```

---

## Key Features Summary

| Feature | Description |
|---------|-------------|
| **Large Dataset** | 100K books, 500K ratings |
| **No Caching** | Pure database optimization |
| **Smart Filtering** | Multi-criteria dengan AND/OR |
| **Trending Algorithm** | Statistical analysis 30 days |
| **Rating Protection** | 24h cooldown, duplicate check |
| **AJAX Loading** | Dynamic book selection |
| **Responsive UI** | Tailwind CSS |
| **Factory Pattern** | Easy testing & seeding |

---

## Support

Jika mengalami masalah:
1. Check `storage/logs/laravel.log`
2. Verify database connection
3. Ensure all migrations ran successfully
4. Check PHP & MySQL versions
5. Review error messages carefully

---

## License

This project is for educational purposes.

**Created for Timedoor Bookstore**