# NoteBook — Aplikasi Catatan Penting & Rahasia (3-Panel Model)

Aplikasi web modern berbasis **PHP & MySQL** dengan antarmuka elegan **Model 3-Panel** (Apple Notes / Bear style), dirancang khusus untuk menyimpan dan mengorganisasi catatan penting, dokumen pribadi, checklist to-do, hingga data sensitif dengan aman.

---

## 🌟 Fitur Utama

1. **Antarmuka Model 3-Panel:**
   - **Panel 1 (Kiri):** Navigasi folder (Semua Catatan, Tersemat, Terkunci, Sampah) & Kategori kustom dengan indikator warna dan jumlah catatan.
   - **Panel 2 (Tengah):** Daftar catatan dengan cuplikan konten, status pin/lock, waktu pembaruan, dan **Live Instant Search (Ctrl+K)**.
   - **Panel 3 (Kanan):** Editor distraction-free luas dengan toolbar format teks (Markdown), pemilih kategori, dan status penyimpanan.

2. **Keamanan Catatan Penting:**
   - **Kunci PIN (4-digit):** Catatan rahasia/sensitif dapat dikunci dengan PIN. Di daftar catatan, konten disembunyikan dan hanya bisa dibuka setelah memasukkan PIN yang benar.
   - **Sampah & Pemulihan (Trash):** Catatan tidak langsung hilang permanen saat dihapus, melainkan masuk ke Sampah terlebih dahulu dan bisa dipulihkan kapan saja.

3. **Produktivitas & Kenyamanan:**
   - **Auto-Save:** Catatan tersimpan secara otomatis setiap kali Anda berhenti mengetik (tanpa perlu tekan tombol Simpan manual).
   - **Sematkan (Pin to Top):** Tandai catatan prioritas tinggi agar selalu berada di posisi paling atas.
   - **Export & Print:** Simpan catatan ke format **Markdown (.md)** atau cetak/simpan ke **PDF**.
   - **Dark Mode & Light Mode:** Dukungan tema gelap nyaman di mata dengan penyimpanan preferensi otomatis.

---

## 🚀 Cara Menjalankan di XAMPP

1. **Pastikan Apache & MySQL Aktif:**
   - Buka **XAMPP Control Panel**.
   - Klik **Start** pada modul **Apache** dan **MySQL**.

2. **Inisialisasi Database:**
   - Buka browser dan akses:
     ```
     http://localhost/note-book/
     ```
   - Jika database `notebook_db` belum ada, klik tombol **"Setup Database Otomatis"** pada banner di bagian atas.
   - Atau Anda juga dapat mengimpor file `database.sql` langsung melalui **phpMyAdmin** (`http://localhost/phpmyadmin/`).

3. **Konfigurasi Database (Jika Dibutuhkan):**
   - File konfigurasi terdapat di [config.php](file:///c:/xampp/htdocs/note-book/config.php).
   - Default XAMPP:
     - Host: `localhost`
     - User: `root`
     - Password: `(kosong)`
     - Database: `notebook_db`

---

## 📁 Struktur Berkas

- `index.php` — Tampilan utama antarmuka 3-Panel, modal PIN, modal kategori, dan toolbar.
- `app.js` — Logika frontend (AJAX request, live search, debounce auto-save, modal verify PIN, tema gelap).
- `api.php` — REST API endpoint backend PHP untuk membaca, membuat, mengubah, menghapus, dan verifikasi PIN catatan.
- `config.php` — Pengaturan koneksi MySQL.
- `setup.php` — Inisialisasi otomatis pembuatan database dan tabel beserta data contoh.
- `database.sql` — Skrip skema database MySQL lengkap.
- `style.css` — Penataan gaya custom, font Plus Jakarta Sans, styling scrollbar, dan mode cetak.
