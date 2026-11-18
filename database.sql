-- Skrip Inisialisasi Database Notebook
CREATE DATABASE IF NOT EXISTS `notebook_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `notebook_db`;

-- Tabel Kategori
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `color` VARCHAR(30) NOT NULL DEFAULT '#6366f1',
    `icon` VARCHAR(50) NOT NULL DEFAULT 'folder',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Catatan
CREATE TABLE IF NOT EXISTS `notes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NULL,
    `title` VARCHAR(255) NOT NULL DEFAULT 'Catatan Tanpa Judul',
    `content` LONGTEXT NULL,
    `is_pinned` TINYINT(1) NOT NULL DEFAULT 0,
    `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
    `pin_code` VARCHAR(255) NULL,
    `color_tag` VARCHAR(30) NOT NULL DEFAULT 'default',
    `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Awal Kategori
INSERT INTO `categories` (`id`, `name`, `color`, `icon`) VALUES
(1, 'Pekerjaan', '#3b82f6', 'briefcase'),
(2, 'Pribadi & Rahasia', '#ef4444', 'shield'),
(3, 'Ide & Inspirasi', '#f59e0b', 'lightbulb'),
(4, 'Keuangan', '#10b981', 'wallet')
ON DUPLICATE KEY UPDATE `name`=`name`;

-- Data Awal Catatan
INSERT INTO `notes` (`id`, `category_id`, `title`, `content`, `is_pinned`, `is_locked`, `pin_code`, `color_tag`, `is_deleted`) VALUES
(1, 1, '📌 Selamat Datang di NoteBook!', 'Selamat datang di aplikasi catatan penting pribadi Anda.\n\nBeberapa fitur utama yang bisa Anda gunakan:\n- **Model 3-Panel:** Akses kategori, daftar catatan, dan editor dalam satu layar tanpa reload.\n- **Sematkan (Pin):** Catatan prioritas tetap di atas.\n- **Kunci Catatan (PIN):** Lindungi catatan sensitif dengan PIN 4-digit.\n- **Pencarian Kilat:** Ketik kata kunci apa saja di kolom pencarian untuk menemukan catatan dalam hitungan milidetik.\n- **Auto-Save:** Tidak perlu takut kehilangan data, catatan tersimpan otomatis saat Anda mengetik.\n\nSelamat berkreasi!', 1, 0, NULL, 'indigo', 0),
(2, 2, '🔐 Data Akun & Sandi Cadangan', 'Catatan ini contoh catatan berharga/sensitif yang bisa dikunci menggunakan PIN.\n\nContoh data penting:\n- Kode Pemulihan 2FA: 8492-1049-5582\n- No Rekening Cadangan: 123-456-7890 (BCA an Pribadi)\n- Master Passphrase hint: Nama hewan peliharaan pertama + tahun kelulusan.', 1, 1, '1234', 'red', 0),
(3, 3, '💡 Rencana Fitur & Ide Aplikasi Web', '1. Tambah integrasi upload gambar/attachment\n2. Fitur export catatan ke format PDF dan Markdown\n3. Pengingat / alarm berbasis tanggal\n4. Dark Mode otomatis sesuai pengaturan sistem OS', 0, 0, NULL, 'amber', 0)
ON DUPLICATE KEY UPDATE `title`=`title`;
