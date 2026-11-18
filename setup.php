<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

$response = [
    'success' => false,
    'message' => ''
];

// Hubungi MySQL tanpa memilih database terlebih dahulu
$dbCheck = getDBConnection(false);

if (!$dbCheck['success']) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal terhubung ke MySQL Server: ' . $dbCheck['error'],
        'hint' => 'Pastikan Apache & MySQL di XAMPP Control Panel sudah dalam status START (aktif).'
    ]);
    exit;
}

$conn = $dbCheck['conn'];

try {
    // 1. Buat database jika belum ada
    $sqlDb = "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
    if (!$conn->query($sqlDb)) {
        throw new Exception("Gagal membuat database: " . $conn->error);
    }

    $conn->select_db(DB_NAME);

    // 2. Buat tabel categories
    $sqlCategories = "CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `color` VARCHAR(30) NOT NULL DEFAULT '#6366f1',
        `icon` VARCHAR(50) NOT NULL DEFAULT 'folder',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    if (!$conn->query($sqlCategories)) {
        throw new Exception("Gagal membuat tabel categories: " . $conn->error);
    }

    // 3. Buat tabel notes
    $sqlNotes = "CREATE TABLE IF NOT EXISTS `notes` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    if (!$conn->query($sqlNotes)) {
        throw new Exception("Gagal membuat tabel notes: " . $conn->error);
    }

    // 4. Seed data awal jika tabel masih kosong
    $checkCat = $conn->query("SELECT COUNT(*) as cnt FROM `categories`");
    $catRow = $checkCat->fetch_assoc();
    if ($catRow['cnt'] == 0) {
        $conn->query("INSERT INTO `categories` (`id`, `name`, `color`, `icon`) VALUES
            (1, 'Pekerjaan', '#3b82f6', 'briefcase'),
            (2, 'Pribadi & Rahasia', '#ef4444', 'shield'),
            (3, 'Ide & Inspirasi', '#f59e0b', 'lightbulb'),
            (4, 'Keuangan', '#10b981', 'wallet');");
    }

    $checkNotes = $conn->query("SELECT COUNT(*) as cnt FROM `notes`");
    $noteRow = $checkNotes->fetch_assoc();
    if ($noteRow['cnt'] == 0) {
        $conn->query("INSERT INTO `notes` (`id`, `category_id`, `title`, `content`, `is_pinned`, `is_locked`, `pin_code`, `color_tag`, `is_deleted`) VALUES
            (1, 1, '📌 Selamat Datang di NoteBook!', 'Selamat datang di aplikasi catatan penting pribadi Anda.\n\nFitur-fitur unggulan yang siap Anda gunakan:\n- **Model 3-Panel:** Navigasi kategori, daftar catatan, dan editor dalam satu tampilan responsif.\n- **Sematkan (Pin):** Catatan prioritas selalu tampil paling atas.\n- **Kunci Catatan (PIN):** Lindungi data rahasia dengan PIN 4-digit.\n- **Pencarian Kilat:** Cari kata kunci langsung memfilter secara real-time.\n- **Auto-Save:** Catatan tersimpan otomatis saat Anda mengetik.\n- **Export / Print:** Unduh catatan ke format cetak/PDF atau Markdown.', 1, 0, NULL, 'indigo', 0),
            (2, 2, '🔐 Data Akun & Sandi Cadangan', 'Catatan ini dikunci dengan PIN (Default PIN: 1234).\n\nContoh data rahasia:\n- Kode Pemulihan 2FA: 8492-1049-5582\n- No Rekening Cadangan: 123-456-7890 (BCA an Pribadi)\n- Master Passphrase hint: Nama hewan peliharaan pertama + tahun kelulusan.', 1, 1, '1234', 'red', 0),
            (3, 3, '💡 Rencana Fitur & Ide Aplikasi Web', '1. Tambah integrasi upload gambar/attachment\n2. Fitur export catatan ke format PDF dan Markdown\n3. Pengingat / alarm berbasis tanggal\n4. Dark Mode otomatis sesuai pengaturan sistem OS', 0, 0, NULL, 'amber', 0);");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Database `notebook_db` dan tabel berhasil diinisialisasi!'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
