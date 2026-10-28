<?php
require_once 'includes/config.php';
header('Content-Type: application/json');

// Ensure we only process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        if ($action === 'add') {
            $productId = (int)$_POST['product_id'];
            $qty = (int)$_POST['quantity'];
            
            // Handle the variant_id safely
            $variantId = null;
            if (isset($_POST['variant_id']) && $_POST['variant_id'] !== '' && $_POST['variant_id'] !== 'null') {
                $variantId = (int)$_POST['variant_id'];
            }
            
            // Check if the item (with this specific variant) already exists in the cart
            if (isLoggedIn()) {
                $sql = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
                $params = [$_SESSION['user_id'], $productId];
            } else {
                $sql = "SELECT * FROM cart WHERE session_id = ? AND product_id = ?";
                $params = [$_SESSION['cart_session_id'], $productId];
            }

            // If a variant is selected, add it to the query, otherwise look for NULL
            if ($variantId !== null) {
                $sql .= " AND variant_id = ?";
                $params[] = $variantId;
            } else {
                $sql .= " AND variant_id IS NULL";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                // Update existing row quantity
                $existing = $stmt->fetch();
                $update = $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE cart_id = ?");
                $update->execute([$qty, $existing['cart_id']]);
            } else {
                // Insert new row
                if (isLoggedIn()) {
                    $insert = $pdo->prepare("INSERT INTO cart (user_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)");
                    $insert->execute([$_SESSION['user_id'], $productId, $variantId, $qty]);
                } else {
                    $insert = $pdo->prepare("INSERT INTO cart (session_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)");
                    $insert->execute([$_SESSION['cart_session_id'], $productId, $variantId, $qty]);
                }
            }
            echo json_encode(['success' => true]);
            
        } elseif ($action === 'update') {
            $cartId = (int)$_POST['cart_id'];
            $qty = (int)$_POST['quantity'];
            
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
            $stmt->execute([$qty, $cartId]);
            echo json_encode(['success' => true]);
            
        } elseif ($action === 'remove') {
            $cartId = (int)$_POST['cart_id'];
            
            $stmt = $pdo->prepare("DELETE FROM cart WHERE cart_id = ?");
            $stmt->execute([$cartId]);
            echo json_encode(['success' => true]);
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        }
        
    } catch (PDOException $e) {
        // If the database fails, return the exact error so JavaScript can display it
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit;
}
?>