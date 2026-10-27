<?php
require_once 'includes/config.php';
$pageTitle = 'Shopping Cart | ' . SITE_NAME;

// Fetch cart items
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT c.*, p.product_name, p.base_price, p.sale_price, p.image_url, p.stock_quantity,
                           v.variant_type, v.variant_value, v.price_adjustment
                           FROM cart c 
                           JOIN products p ON c.product_id = p.product_id 
                           LEFT JOIN product_variants v ON c.variant_id = v.variant_id 
                           WHERE c.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
} else {
    $stmt = $pdo->prepare("SELECT c.*, p.product_name, p.base_price, p.sale_price, p.image_url, p.stock_quantity,
                           v.variant_type, v.variant_value, v.price_adjustment
                           FROM cart c 
                           JOIN products p ON c.product_id = p.product_id 
                           LEFT JOIN product_variants v ON c.variant_id = v.variant_id 
                           WHERE c.session_id = ?");
    $stmt->execute([$_SESSION['cart_session_id']]);
}
$cartItems = $stmt->fetchAll();

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $price = ($item['sale_price'] ?: $item['base_price']) + ($item['price_adjustment'] ?? 0);
    $subtotal += $price * $item['quantity'];
}
$shipping = ($subtotal >= 500 || $subtotal == 0) ? 0 : 65;
$total = $subtotal + $shipping;

include 'includes/header.php';
?>

<div class="container py-5">
    <h2 class="mb-4 fw-bold">Shopping Cart</h2>
    
    <?php if (empty($cartItems)): ?>
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <i class="fas fa-shopping-bag fa-4x text-muted mb-4"></i>
                <h4>Your cart is empty</h4>
                <p class="text-muted">Looks like you haven't added anything yet.</p>
                <a href="shop.php" class="btn btn-gold mt-3">Start Shopping</a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-0">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th class="pe-4"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item): 
                                    $price = ($item['sale_price'] ?: $item['base_price']) + ($item['price_adjustment'] ?? 0);
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <img src="assets/images/products/<?= $item['image_url'] ?: 'placeholder.jpg' ?>" 
                                                 width="70" height="70" class="rounded me-3 object-fit-cover shadow-sm">
                                            <div>
                                                <h6 class="mb-0 fw-bold"><?= htmlspecialchars($item['product_name']) ?></h6>
                                                <?php if ($item['variant_value']): ?>
                                                <small class="text-muted"><?= htmlspecialchars($item['variant_type']) ?>: <?= htmlspecialchars($item['variant_value']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= formatPrice($price) ?></td>
                                    <td>
                                        <div class="input-group input-group-sm" style="width: 110px;">
                                            <button class="btn btn-outline-secondary qty-btn" data-action="decrease" data-cart-id="<?= $item['cart_id'] ?>">-</button>
                                            <input type="text" class="form-control text-center qty-input" value="<?= $item['quantity'] ?>" readonly>
                                            <button class="btn btn-outline-secondary qty-btn" data-action="increase" data-cart-id="<?= $item['cart_id'] ?>">+</button>
                                        </div>
                                    </td>
                                    <td class="fw-bold text-gold"><?= formatPrice($price * $item['quantity']) ?></td>
                                    <td class="pe-4 text-end">
                                        <button class="btn btn-sm btn-outline-danger remove-item" data-cart-id="<?= $item['cart_id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h4 class="mb-4 fw-bold">Order Summary</h4>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-bold"><?= formatPrice($subtotal) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 border-bottom pb-3">
                            <span class="text-muted">Shipping</span>
                            <span class="fw-bold"><?= $shipping === 0 ? 'Free' : formatPrice($shipping) ?></span>
                        </div>
                        <?php if ($shipping > 0): ?>
                        <small class="text-muted d-block mb-3"><i class="fas fa-info-circle me-1"></i> Add <?= formatPrice(500 - $subtotal) ?> more for free delivery!</small>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="fs-5 fw-bold">Total</span>
                            <span class="fs-4 fw-bold text-gold"><?= formatPrice($total) ?></span>
                        </div>
                        <a href="checkout.php" class="btn btn-gold w-100 py-3 fs-5">Proceed to Checkout</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Handle quantity updates and item removal via AJAX
document.querySelectorAll('.qty-btn, .remove-item').forEach(btn => {
    btn.addEventListener('click', function() {
        const cartId = this.getAttribute('data-cart-id');
        const action = this.getAttribute('data-action') || 'remove';
        const input = this.parentElement.querySelector('.qty-input');
        let currentQty = input ? parseInt(input.value) : 0;
        
        let newQty = currentQty;
        if (action === 'increase') newQty++;
        if (action === 'decrease' && currentQty > 1) newQty--;

        fetch('ajax_cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=update&cart_id=${cartId}&quantity=${newQty}`
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) location.reload();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>