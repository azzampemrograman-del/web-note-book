<?php
// Konfigurasi Database (Mendukung Local XAMPP & Production Environment Variable Vercel)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'notebook_db');
define('DB_PORT', getenv('DB_PORT') ? intval(getenv('DB_PORT')) : 3306);

function getDBConnection($selectDb = true) {
    try {
        if ($selectDb) {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        } else {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
        }

        if ($conn->connect_error) {
            return [
                'success' => false,
                'error' => $conn->connect_error,
                'errno' => $conn->connect_errno
            ];
        }

        $conn->set_charset('utf8mb4');
        return [
            'success' => true,
            'conn' => $conn
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
