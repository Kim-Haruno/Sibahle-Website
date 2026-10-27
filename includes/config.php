<?php
define('DB_HOST', 'sql204.infinityfree.com');
define('DB_USER', 'if0_43263816');
define('DB_PASS', 'N9aZ6J14UqPf7'); // Paste the password you revealed
define('DB_NAME', 'if0_43263816_sibahle');

define('SITE_URL', 'http://sibahle.great-site.net');
define('SITE_NAME', 'Sibahle We Are Beautiful');
define('CURRENCY', 'R');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed.');
}

if (!isset($_SESSION['cart_session_id'])) {
    $_SESSION['cart_session_id'] = bin2hex(random_bytes(16));
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ../login.php');
        exit;
    }
}

function formatPrice($price): string {
    return CURRENCY . number_format((float) $price, 2);
}

function escape_html($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function getCartCount(PDO $pdo): int {
    if (isLoggedIn()) {
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE session_id = ?');
        $stmt->execute([$_SESSION['cart_session_id']]);
    }
    return (int) $stmt->fetchColumn();
}

function get_cart_items(PDO $pdo, bool $lock_rows = false): array {
    // The column is chosen from a fixed allowlist; only its matching value is bound.
    $scope_column = isLoggedIn() ? 'user_id' : 'session_id';
    $scope_value = isLoggedIn() ? (int) $_SESSION['user_id'] : $_SESSION['cart_session_id'];
    $lock_clause = $lock_rows ? ' FOR UPDATE' : '';

    $stmt = $pdo->prepare(
        "SELECT c.cart_id, c.product_id, c.variant_id, c.quantity,
                p.product_name, p.product_slug, p.base_price, p.sale_price,
                p.image_url, p.stock_quantity, p.is_active,
                v.variant_type, v.variant_value, v.price_adjustment,
                v.stock_quantity AS variant_stock
         FROM cart c
         INNER JOIN products p ON p.product_id = c.product_id
         LEFT JOIN product_variants v ON v.variant_id = c.variant_id
         WHERE c.{$scope_column} = ?
         ORDER BY c.added_at DESC, c.cart_id DESC
         {$lock_clause}"
    );
    $stmt->execute([$scope_value]);

    return $stmt->fetchAll();
}

function calculate_cart_unit_price(array $cart_item): float {
    $base_price = $cart_item['sale_price'] !== null
        ? (float) $cart_item['sale_price']
        : (float) $cart_item['base_price'];

    return $base_price + (float) ($cart_item['price_adjustment'] ?? 0);
}

function calculate_cart_totals(array $cart_items): array {
    $subtotal = 0.0;
    foreach ($cart_items as $cart_item) {
        $subtotal += calculate_cart_unit_price($cart_item) * (int) $cart_item['quantity'];
    }

    $shipping = $subtotal >= 500 ? 0.0 : 65.0;

    return [
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total' => $subtotal + $shipping,
    ];
}
?>