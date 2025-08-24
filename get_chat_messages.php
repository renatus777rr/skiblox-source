<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';

// Start session if needed for user auth
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current user
$user = current_user($pdo);
if (!$user || !isset($user['id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Prepare and execute query
try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.message, c.created_at, c.file_path, c.file_name, c.is_image, u.username
        FROM chat_messages c
        JOIN users u ON u.id = c.user_id
        ORDER BY c.id DESC
        LIMIT 100
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Optional: sanitize output (e.g., strip tags from message)
    foreach ($rows as &$row) {
        if (isset($row['message'])) {
            $row['message'] = htmlspecialchars($row['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        if (isset($row['username'])) {
            $row['username'] = htmlspecialchars($row['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
    }

    // Output JSON
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    // Optional debug:
    // echo json_encode(['error' => $e->getMessage()]);
}