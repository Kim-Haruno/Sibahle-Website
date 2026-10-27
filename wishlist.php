<?php
require_once 'includes/config.php';
$pageTitle = 'My Wishlist | ' . SITE_NAME;

// Require login to view wishlist
requireLogin();

// Handle item removal
if (isset($_GET['remove'])) {
    $productId = (int)$_GET['remove'];
    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $productId]);
    header('Location: wishlist.php');
    exit;
}

// Fetch wishlist items
$stmt = $pdo->prepare("SELECT w.wishlist_id, p.* 
                       FROM wishlist w 
                       JOIN products p ON w.product_id = p.product_id 
                       WHERE w.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$wishlist = $stmt->fetchAll();

include 'includes/header.php';
?>

<section class="py-5 bg-light">
    <div class="container">
        <h2 class="mb-4 fw-bold">My Wishlist</h2>

        <?php if (empty($wishlist)): ?>
            <div class="card border-0 shadow-sm text-center py-5">
                <div class="card-body">
                    <i class="far fa-heart fa-3x text-muted mb-3"></i>
                    <h4>Your wishlist is empty</h4>
                    <p class="text-muted">Save your favorite items here to buy them later.</p>
                    <a href="shop.php" class="btn btn-gold mt-3">Go to Shop</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($wishlist as $item): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="product-card card h-100 border-0 shadow-sm">
                            <div class="position-relative overflow-hidden">
                                <img src="assets/images/products/<?= $item['image_url'] ?: 'placeholder.jpg' ?>" 
                                     class="card-img-top product-img" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                <a href="wishlist.php?remove=<?= $item['product_id'] ?>" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2 rounded-circle shadow-sm" title="Remove from wishlist">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title">
                                    <a href="product.php?slug=<?= $item['product_slug'] ?>" class="text-dark text-decoration-none">
                                        <?= htmlspecialchars($item['product_name']) ?>
                                    </a>
                                </h6>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="price fw-bold"><?= formatPrice($item['base_price']) ?></span>
                                    <button class="btn btn-gold btn-sm add-to-cart" data-id="<?= $item['product_id'] ?>">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
// Reuse the Add to Cart script from the main page
document.querySelectorAll('.add-to-cart').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.getAttribute('data-id');
        fetch('ajax_cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=add&product_id=${productId}&quantity=1`
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert('Added to cart!');
                location.reload();
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>