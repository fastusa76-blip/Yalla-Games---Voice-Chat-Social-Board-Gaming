<?php
declare(strict_types=1);

/**
 * Yalla Games JSON API — PHP 8.1+ / MySQL 8+
 * Routes: ?action=health|orders|order-status|chat|admin-orders|admin-update
 */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

function respond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function body_json(): array {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    if (!is_array($data)) respond(400, ['ok' => false, 'error' => 'Invalid JSON request body.']);
    return $data;
}
function text_slice(string $value, int $max): string {
    return function_exists('mb_substr') ? mb_substr($value, 0, $max, 'UTF-8') : substr($value, 0, $max);
}
function text_lower(string $value): string {
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}
function clean_text(mixed $value, int $max): string {
    if (!is_string($value)) return '';
    $value = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '');
    return text_slice($value, $max);
}
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $host = getenv('DB_HOST') ?: '';
    $name = getenv('DB_NAME') ?: '';
    $user = getenv('DB_USER') ?: '';
    $pass = getenv('DB_PASS') ?: '';
    if ($host === '' || $name === '' || $user === '') {
        respond(503, ['ok' => false, 'error' => 'Backend database is not configured.']);
    }
    try {
        $pdo = new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable $e) {
        error_log('Yalla DB connection failed: ' . $e->getMessage());
        respond(503, ['ok' => false, 'error' => 'Database is temporarily unavailable.']);
    }
    return $pdo;
}
function require_admin(): void {
    $expected = getenv('ADMIN_API_KEY') ?: '';
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $provided = str_starts_with($header, 'Bearer ') ? substr($header, 7) : '';
    if (strlen($expected) < 32 || $provided === '' || !hash_equals($expected, $provided)) {
        respond(401, ['ok' => false, 'error' => 'Admin authentication failed.']);
    }
}
function limit_public_requests(string $scope, int $limit = 12): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = hash('sha256', $scope . '|' . $ip);
    $pdo = db();
    $pdo->prepare('DELETE FROM api_rate_limits WHERE window_started < (UTC_TIMESTAMP() - INTERVAL 1 HOUR)')->execute();
    $stmt = $pdo->prepare('SELECT request_count, window_started FROM api_rate_limits WHERE bucket_key = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if (!$row) {
        $pdo->prepare('INSERT INTO api_rate_limits (bucket_key, request_count, window_started) VALUES (?, 1, UTC_TIMESTAMP())')->execute([$key]);
        return;
    }
    if (strtotime((string)$row['window_started']) < time() - 60) {
        $pdo->prepare('UPDATE api_rate_limits SET request_count = 1, window_started = UTC_TIMESTAMP() WHERE bucket_key = ?')->execute([$key]);
        return;
    }
    if ((int)$row['request_count'] >= $limit) respond(429, ['ok' => false, 'error' => 'Too many requests. Please try again in a minute.']);
    $pdo->prepare('UPDATE api_rate_limits SET request_count = request_count + 1 WHERE bucket_key = ?')->execute([$key]);
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($action === 'health' && $method === 'GET') {
        db()->query('SELECT 1 FROM orders LIMIT 1');
        db()->query('SELECT 1 FROM api_rate_limits LIMIT 1');
        respond(200, ['ok' => true, 'service' => 'yalla-games-api', 'database' => 'connected']);
    }

    if ($action === 'orders' && $method === 'POST') {
        limit_public_requests('orders', 5);
        $data = body_json();
        $name = clean_text($data['name'] ?? null, 100);
        $phone = clean_text($data['phone'] ?? null, 30);
        $game = clean_text($data['game'] ?? null, 160);
        $quantity = filter_var($data['quantity'] ?? null, FILTER_VALIDATE_INT);
        $email = clean_text($data['email'] ?? null, 190);
        $notes = clean_text($data['notes'] ?? null, 1500);
        if ($name === '' || $game === '' || $quantity === false || $quantity < 1 || $quantity > 99) {
            respond(422, ['ok' => false, 'error' => 'Please provide a name, game and quantity from 1 to 99.']);
        }
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen((string)$digits) < 10 || strlen((string)$digits) > 15) respond(422, ['ok' => false, 'error' => 'Enter a valid WhatsApp phone number including country code.']);
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) respond(422, ['ok' => false, 'error' => 'Enter a valid email address.']);
        $reference = 'YG-' . gmdate('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $token = bin2hex(random_bytes(32));
        $stmt = db()->prepare('INSERT INTO orders (reference, access_token_hash, customer_name, customer_phone, customer_email, game_name, quantity, notes, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'pending\', UTC_TIMESTAMP(), UTC_TIMESTAMP())');
        $stmt->execute([$reference, hash('sha256', $token), $name, $phone, $email ?: null, $game, $quantity, $notes ?: null]);
        respond(201, [
            'ok' => true,
            'message' => 'Your order request has been received. It is pending confirmation by Yalla Games.',
            'order' => ['reference' => $reference, 'status' => 'pending', 'access_token' => $token],
            'whatsapp_url' => 'https://wa.me/923067115462?text=' . rawurlencode("Hello Yalla Games, I submitted order {$reference}. Please confirm availability and price."),
        ]);
    }

    if ($action === 'order-status' && $method === 'POST') {
        $data = body_json();
        $reference = clean_text($data['reference'] ?? null, 32);
        $token = clean_text($data['token'] ?? null, 100);
        if ($reference === '' || strlen($token) !== 64) respond(422, ['ok' => false, 'error' => 'Order reference and access token are required.']);
        $stmt = db()->prepare('SELECT reference, access_token_hash, game_name, quantity, status, admin_note, created_at, updated_at FROM orders WHERE reference = ? LIMIT 1');
        $stmt->execute([$reference]);
        $order = $stmt->fetch();
        if (!$order || !hash_equals((string)$order['access_token_hash'], hash('sha256', $token))) respond(404, ['ok' => false, 'error' => 'Order not found. Check your reference and private access token.']);
        unset($order['access_token_hash']);
        respond(200, ['ok' => true, 'order' => $order]);
    }

    if ($action === 'chat' && $method === 'POST') {
        limit_public_requests('chat', 30);
        $data = body_json();
        $message = clean_text($data['message'] ?? null, 500);
        if ($message === '') respond(422, ['ok' => false, 'error' => 'Please enter a message.']);
        $q = text_lower($message);
        if (preg_match('/order|buy|purchase|place/', $q)) {
            $reply = 'To place an order, use the order form and enter your name, WhatsApp number, game and quantity. You will receive a private order reference to check its status.';
        } elseif (preg_match('/contact|email|whatsapp|number|phone/', $q)) {
            $reply = 'Contact Yalla Games on WhatsApp at +92 306 7115462 or email jabham786@gmail.com.';
        } elseif (preg_match('/price|cost|available|stock/', $q)) {
            $reply = 'Prices and availability must be confirmed by our team. Send us the game name on WhatsApp at +92 306 7115462.';
        } elseif (preg_match('/game|recommend|suggest|choose/', $q)) {
            $reply = 'We have board-game, card and party-game, strategy and puzzle categories. Tell us your preferred game and our team can guide you.';
        } elseif (preg_match('/hello|hi|hey|salam|assalam/', $q)) {
            $reply = 'Hello! Welcome to Yalla Games. Would you like a game suggestion or help placing an order?';
        } else {
            $reply = 'I can help with game categories, ordering and contact details. For a specific question, message our team at +92 306 7115462.';
        }
        respond(200, ['ok' => true, 'reply' => $reply]);
    }

    if ($action === 'admin-orders' && $method === 'GET') {
        require_admin();
        $rows = db()->query('SELECT reference, customer_name, customer_phone, customer_email, game_name, quantity, notes, status, admin_note, created_at FROM orders ORDER BY created_at DESC LIMIT 200')->fetchAll();
        respond(200, ['ok' => true, 'orders' => $rows]);
    }

    if ($action === 'admin-update' && $method === 'POST') {
        require_admin();
        $data = body_json();
        $reference = clean_text($data['reference'] ?? null, 32);
        $status = clean_text($data['status'] ?? null, 20);
        $note = clean_text($data['admin_note'] ?? null, 500);
        $allowed = ['confirmed', 'rejected', 'fulfilled'];
        if ($reference === '' || !in_array($status, $allowed, true)) respond(422, ['ok' => false, 'error' => 'Invalid reference or status.']);
        $stmt = db()->prepare('UPDATE orders SET status = ?, admin_note = ?, updated_at = UTC_TIMESTAMP() WHERE reference = ?');
        $stmt->execute([$status, $note ?: null, $reference]);
        if ($stmt->rowCount() < 1) {
            $check = db()->prepare('SELECT reference FROM orders WHERE reference = ?');
            $check->execute([$reference]);
            if (!$check->fetch()) respond(404, ['ok' => false, 'error' => 'Order not found.']);
        }
        respond(200, ['ok' => true, 'message' => 'Order status updated.', 'reference' => $reference, 'status' => $status]);
    }

    respond(404, ['ok' => false, 'error' => 'API route not found.']);
} catch (Throwable $e) {
    error_log('Yalla API error: ' . $e->getMessage());
    respond(500, ['ok' => false, 'error' => 'Unexpected server error. Please try again later.']);
}
