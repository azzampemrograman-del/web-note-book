<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// Cek koneksi database
$db = getDBConnection();
if (!$db['success']) {
    echo json_encode([
        'success' => false,
        'db_error' => true,
        'message' => 'Gagal terhubung ke database: ' . $db['error'],
        'hint' => 'Pastikan Apache & MySQL di XAMPP Control Panel sudah AKTIF (Start).'
    ]);
    exit;
}

$conn = $db['conn'];

// Baca method dan input
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Parsing JSON body jika ada
$rawInput = file_get_contents('php://input');
$body = json_decode($rawInput, true) ?? [];

switch ($action) {
    case 'get_meta':
        // Ambil daftar kategori dan statistik jumlah catatan
        $catStmt = $conn->query("
            SELECT c.*, 
                (SELECT COUNT(*) FROM notes n WHERE n.category_id = c.id AND n.is_deleted = 0) as notes_count 
            FROM categories c 
            ORDER BY c.name ASC
        ");
        $categories = [];
        while ($row = $catStmt->fetch_assoc()) {
            $categories[] = $row;
        }

        // Hitung statistik global
        $statsStmt = $conn->query("
            SELECT 
                COUNT(CASE WHEN is_deleted = 0 THEN 1 END) as total_active,
                COUNT(CASE WHEN is_pinned = 1 AND is_deleted = 0 THEN 1 END) as total_pinned,
                COUNT(CASE WHEN is_locked = 1 AND is_deleted = 0 THEN 1 END) as total_locked,
                COUNT(CASE WHEN is_deleted = 1 THEN 1 END) as total_trash
            FROM notes
        ");
        $stats = $statsStmt->fetch_assoc();

        echo json_encode([
            'success' => true,
            'categories' => $categories,
            'stats' => $stats
        ]);
        break;

    case 'get_notes':
        $filter = $_GET['filter'] ?? 'all';
        $categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
        $search = trim($_GET['search'] ?? '');

        $sql = "SELECT n.id, n.category_id, n.title, n.is_pinned, n.is_locked, n.color_tag, n.is_deleted, n.created_at, n.updated_at,
                       c.name as category_name, c.color as category_color,
                       CASE 
                           WHEN n.is_locked = 1 THEN '🔒 Catatan ini terkunci oleh PIN keamanan'
                           ELSE SUBSTRING(n.content, 1, 150)
                       END as preview_content
                FROM notes n
                LEFT JOIN categories c ON n.category_id = c.id
                WHERE 1=1";

        $params = [];
        $types = '';

        if ($filter === 'trash') {
            $sql .= " AND n.is_deleted = 1";
        } else {
            $sql .= " AND n.is_deleted = 0";

            if ($filter === 'pinned') {
                $sql .= " AND n.is_pinned = 1";
            } elseif ($filter === 'locked') {
                $sql .= " AND n.is_locked = 1";
            } elseif ($categoryId) {
                $sql .= " AND n.category_id = ?";
                $params[] = $categoryId;
                $types .= 'i';
            }
        }

        if ($search !== '') {
            $sql .= " AND (n.title LIKE ? OR (n.is_locked = 0 AND n.content LIKE ?))";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= 'ss';
        }

        // Urutan: Pinned dahulu, kemudian updated_at terbaru
        $sql .= " ORDER BY n.is_pinned DESC, n.updated_at DESC";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();

        $notes = [];
        while ($row = $res->fetch_assoc()) {
            $notes[] = $row;
        }

        echo json_encode([
            'success' => true,
            'notes' => $notes
        ]);
        break;

    case 'get_note':
        $noteId = intval($_GET['id'] ?? 0);
        $pin = trim($_GET['pin'] ?? '');

        if (!$noteId) {
            echo json_encode(['success' => false, 'message' => 'ID catatan tidak valid']);
            exit;
        }

        $stmt = $conn->prepare("SELECT * FROM notes WHERE id = ?");
        $stmt->bind_param('i', $noteId);
        $stmt->execute();
        $note = $stmt->get_result()->fetch_assoc();

        if (!$note) {
            echo json_encode(['success' => false, 'message' => 'Catatan tidak ditemukan']);
            exit;
        }

        // Cek apakah catatan terkunci dan PIN dibutuhkan
        if ($note['is_locked']) {
            if ($pin === '' || $pin !== $note['pin_code']) {
                echo json_encode([
                    'success' => false,
                    'is_locked' => true,
                    'title' => $note['title'],
                    'message' => 'Catatan terkunci! Silakan masukkan PIN 4 digit yang benar.'
                ]);
                exit;
            }
        }

        // Hapus pin_code dari output demi keamanan
        unset($note['pin_code']);

        echo json_encode([
            'success' => true,
            'note' => $note
        ]);
        break;

    case 'save_note':
        $id = intval($body['id'] ?? 0);
        $title = trim($body['title'] ?? 'Catatan Baru');
        $content = $body['content'] ?? '';
        $categoryId = !empty($body['category_id']) ? intval($body['category_id']) : null;
        $isPinned = !empty($body['is_pinned']) ? 1 : 0;
        $isLocked = !empty($body['is_locked']) ? 1 : 0;
        $pinCode = !empty($body['pin_code']) ? trim($body['pin_code']) : null;
        $colorTag = $body['color_tag'] ?? 'default';

        if ($title === '') {
            $title = 'Catatan Tanpa Judul';
        }

        if ($id > 0) {
            // Update catatan
            // Jika isLocked aktif tapi pinCode tidak dikirim, pertahankan PIN yang sudah ada
            if ($isLocked && empty($pinCode)) {
                $stmt = $conn->prepare("
                    UPDATE notes 
                    SET title = ?, content = ?, category_id = ?, is_pinned = ?, is_locked = ?, color_tag = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param('ssiissi', $title, $content, $categoryId, $isPinned, $isLocked, $colorTag, $id);
            } else {
                $stmt = $conn->prepare("
                    UPDATE notes 
                    SET title = ?, content = ?, category_id = ?, is_pinned = ?, is_locked = ?, pin_code = ?, color_tag = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param('ssiissii', $title, $content, $categoryId, $isPinned, $isLocked, $pinCode, $colorTag, $id);
            }
            $stmt->execute();
            $updatedId = $id;
        } else {
            // Buat catatan baru
            $stmt = $conn->prepare("
                INSERT INTO notes (title, content, category_id, is_pinned, is_locked, pin_code, color_tag, is_deleted)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0)
            ");
            $stmt->bind_param('ssiisss', $title, $content, $categoryId, $isPinned, $isLocked, $pinCode, $colorTag);
            $stmt->execute();
            $updatedId = $conn->insert_id;
        }

        echo json_encode([
            'success' => true,
            'note_id' => $updatedId,
            'message' => 'Catatan berhasil disimpan!'
        ]);
        break;

    case 'toggle_pin':
        $id = intval($body['id'] ?? 0);
        $stmt = $conn->prepare("UPDATE notes SET is_pinned = IF(is_pinned = 1, 0, 1), updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['success' => true]);
        break;

    case 'delete_note':
        $id = intval($body['id'] ?? 0);
        $permanent = !empty($body['permanent']);

        if ($permanent) {
            $stmt = $conn->prepare("DELETE FROM notes WHERE id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE notes SET is_deleted = 1, updated_at = NOW() WHERE id = ?");
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => $permanent ? 'Catatan dihapus permanen' : 'Catatan dipindahkan ke sampah']);
        break;

    case 'restore_note':
        $id = intval($body['id'] ?? 0);
        $stmt = $conn->prepare("UPDATE notes SET is_deleted = 0, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Catatan berhasil dipulihkan']);
        break;

    case 'empty_trash':
        $conn->query("DELETE FROM notes WHERE is_deleted = 1");
        echo json_encode(['success' => true, 'message' => 'Sampah berhasil dikosongkan']);
        break;

    case 'save_category':
        $name = trim($body['name'] ?? '');
        $color = trim($body['color'] ?? '#6366f1');
        $icon = trim($body['icon'] ?? 'folder');

        if ($name === '') {
            echo json_encode(['success' => false, 'message' => 'Nama kategori tidak boleh kosong']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO categories (name, color, icon) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $name, $color, $icon);
        $stmt->execute();

        echo json_encode(['success' => true, 'id' => $conn->insert_id, 'message' => 'Kategori berhasil ditambahkan']);
        break;

    case 'delete_category':
        $id = intval($body['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Kategori berhasil dihapus']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal']);
        break;
}
