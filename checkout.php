<?php
require_once 'includes/config.php';
$pageTitle = 'Checkout | ' . SITE_NAME;

// Must be logged in or have items in cart
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Fetch cart items
$stmt = $pdo->prepare("SELECT c.*, p.product_name, p.base_price, p.sale_price 
                       FROM cart c JOIN products p ON c.product_id = p.product_id 
                       WHERE c.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $price = ($item['sale_price'] ?: $item['base_price']);
    $subtotal += $price * $item['quantity'];
}
$shipping = ($subtotal >= 500) ? 0 : 65;
$total = $subtotal + $shipping;

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderNumber = 'SIB-' . strtoupper(uniqid());
    
    try {
        $pdo->beginTransaction();

        // 1. Create Order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, customer_email, shipping_first_name, shipping_last_name, shipping_address, shipping_city, shipping_province, shipping_postal_code, total_amount, payment_status, order_status) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'new')");
        $stmt->execute([
            $_SESSION['user_id'],
            $orderNumber,
            $_POST['email'],
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['address'],
            $_POST['city'],
            $_POST['province'],
            $_POST['postal_code'],
            $total
        ]);
        
        $orderId = $pdo->lastInsertId();

        // 2. Move items from Cart to Order_Items
        foreach ($cartItems as $item) {
            $price = ($item['sale_price'] ?: $item['base_price']);
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $orderId, 
                $item['product_id'], 
                $item['product_name'], 
                $item['quantity'], 
                $price, 
                $price * $item['quantity']
            ]);
        }

        // 3. Clear Cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);

        $pdo->commit();
        header("Location: success.php?order=" . $orderNumber);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Checkout failed: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
    <h2 class="mb-4">Checkout</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="row g-4">
            <div class="col-md-7">
                <div class="card border-0 shadow-sm p-4">
                    <h5 class="mb-3">Shipping Details</h5>
                    <div class="row g-3">
                        <div class="col-6"><input type="text" name="first_name" class="form-control" placeholder="First Name" required></div>
                        <div class="col-6"><input type="text" name="last_name" class="form-control" placeholder="Last Name" required></div>
                        <div class="col-12"><input type="email" name="email" class="form-control" placeholder="Email Address" required></div>
                        <div class="col-12"><input type="text" name="address" class="form-control" placeholder="Street Address" required></div>
                        <div class="col-6"><input type="text" name="city" class="form-control" placeholder="City" required></div>
                        <div class="col-6"><input type="text" name="province" class="form-control" placeholder="Province" required></div>
                        <div class="col-6"><input type="text" name="postal_code" class="form-control" placeholder="Postal Code" required></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-5">
                <div class="card border-0 shadow-sm p-4">
                    <h5 class="mb-3">Your Order</h5>
                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach ($cartItems as $item): ?>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span><?= htmlspecialchars($item['product_name']) ?> (x<?= $item['quantity'] ?>)</span>
                                <span><?= formatPrice(($item['sale_price'] ?: $item['base_price']) * $item['quantity']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                        <span>Total:</span>
                        <span class="text-gold"><?= formatPrice($total) ?></span>
                    </div>
                    <button type="submit" class="btn btn-gold w-100 py-3">Place Order (COD)</button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>