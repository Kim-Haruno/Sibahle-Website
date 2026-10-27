<?php
require_once 'includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to use the wishlist.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $productId = (int)$_POST['product_id'];
    $userId = $_SESSION['user_id'];

    // Check if already in wishlist
    $stmt = $pdo->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    
    if ($stmt->rowCount() > 0) {
        // Remove it (toggle functionality)
        $del = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $del->execute([$userId, $productId]);
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        // Add it
        $ins = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $ins->execute([$userId, $productId]);
        echo json_encode(['success' => true, 'action' => 'added']);
    }
} else {
    echo json_encode(['success' => false]);
}
?>