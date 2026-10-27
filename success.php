<?php
require_once 'includes/config.php';
$pageTitle = 'Order Successful | ' . SITE_NAME;
include 'includes/header.php';
?>

<div class="container py-5 text-center">
    <div class="card border-0 shadow-lg py-5 rounded-4">
        <div class="card-body">
            <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
            <h1 class="display-5 fw-bold">Thank You!</h1>
            <p class="lead">Your order has been placed successfully.</p>
            <p class="text-muted">Order Number: <strong><?= htmlspecialchars($_GET['order'] ?? 'N/A') ?></strong></p>
            <a href="shop.php" class="btn btn-gold mt-3">Continue Shopping</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>