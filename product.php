<?php
require_once 'includes/config.php';

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header('Location: shop.php');
    exit;
}

// Fetch product
$stmt = $pdo->prepare("SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id WHERE p.product_slug = ? AND p.is_active = 1");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    die("<div class='container py-5 text-center'><h2>Product not found.</h2><a href='shop.php' class='btn btn-gold mt-3'>Return to shop</a></div>");
}

$pageTitle = $product['product_name'] . ' | ' . SITE_NAME;

// Fetch variants (if any)
$varStmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ?");
$varStmt->execute([$product['product_id']]);
$variants = $varStmt->fetchAll();

// Fetch gallery images
$imgStmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ?");
$imgStmt->execute([$product['product_id']]);
$gallery = $imgStmt->fetchAll();

include 'includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-gold text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="shop.php" class="text-gold text-decoration-none">Shop</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($product['product_name']) ?></li>
            </ol>
        </nav>

        <div class="row g-5">
            <div class="col-md-6">
                <!-- Main Image -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
                    <img src="assets/images/products/<?= $product['image_url'] ?: 'placeholder.jpg' ?>" 
                         id="mainImage" 
                         class="img-fluid w-100" 
                         alt="<?= htmlspecialchars($product['product_name']) ?>" 
                         style="object-fit: cover;">
                </div>

                <!-- Thumbnail Gallery -->
                <?php if ($gallery): ?>
                    <div class="row g-2">
                        <?php foreach ($gallery as $img): ?>
                            <div class="col-3">
                                <img src="assets/images/products/<?= htmlspecialchars($img['image_url']) ?>" 
                                     class="img-fluid rounded border gallery-thumb" 
                                     style="height: 80px; width: 100%; object-fit: cover; cursor: pointer; transition: 0.2s;"
                                     onclick="document.getElementById('mainImage').src = this.src;"
                                     onmouseover="this.style.opacity='0.7'"
                                     onmouseout="this.style.opacity='1'">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-6">
                <small class="text-uppercase text-muted fw-bold" style="letter-spacing: 2px;"><?= htmlspecialchars($product['category_name']) ?></small>
                <h1 class="display-5 fw-bold mt-2 mb-3"><?= htmlspecialchars($product['product_name']) ?></h1>
                <h3 class="text-gold fw-bold mb-4"><?= formatPrice($product['base_price']) ?></h3>
                
                <p class="lead text-muted mb-4"><?= nl2br(htmlspecialchars($product['short_description'] ?? '')) ?></p>
                <p><?= nl2br(htmlspecialchars($product['description'] ?? '')) ?></p>

                <form id="addToCartForm" class="mt-4">
                    <?php if ($variants): ?>
                        <div class="mb-4">
                            <label class="fw-bold mb-2">Select Option:</label>
                            <select class="form-select form-select-lg" id="variantSelect">
                                <?php foreach ($variants as $v): ?>
                                    <option value="<?= $v['variant_id'] ?>" 
                                            data-stock="<?= (int)$v['stock_quantity'] ?>"
                                            <?= (int)$v['stock_quantity'] <= 0 ? 'disabled' : '' ?>>
                                        <?= htmlspecialchars($v['variant_value']) ?>
                                        <?= (int)$v['stock_quantity'] <= 0 ? ' [SOLD OUT]' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex gap-3">
                        <div class="input-group" style="width: 130px;">
                            <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('qty').stepDown()">-</button>
                            <input type="number" id="qty" class="form-control text-center" value="1" min="1" max="<?= $product['stock_quantity'] ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('qty').stepUp()">+</button>
                        </div>
                        <button type="button" class="btn btn-gold btn-lg flex-grow-1 add-to-cart-btn" 
                                data-id="<?= $product['product_id'] ?>" 
                                data-main-stock="<?= (int)$product['stock_quantity'] ?>">
                            <i class="fas fa-shopping-bag me-2"></i> Add to Cart
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Toast Notification Container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
    <div id="cartToast" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body fs-6">
                <i class="fas fa-check-circle me-2"></i> Added to cart successfully!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const variantSelect = document.getElementById('variantSelect');
    const addToCartBtn = document.querySelector('.add-to-cart-btn');
    
    // Get the main product stock
    const mainStock = parseInt(addToCartBtn.getAttribute('data-main-stock'));

    function checkStock() {
        let isSoldOut = false;

        // 1. Check main product stock
        if (mainStock <= 0) {
            isSoldOut = true;
        }

        // 2. Check selected variant stock
        if (variantSelect && variantSelect.selectedIndex >= 0) {
            const selectedOption = variantSelect.options[variantSelect.selectedIndex];
            const variantStock = parseInt(selectedOption.getAttribute('data-stock'));
            if (variantStock <= 0) {
                isSoldOut = true;
            }
        }

        // 3. Update button based on result
        if (isSoldOut) {
            addToCartBtn.disabled = true;
            addToCartBtn.innerHTML = '<i class="fas fa-ban me-2"></i> Sold Out';
            addToCartBtn.classList.add('btn-secondary');
            addToCartBtn.classList.remove('btn-gold');
        } else {
            addToCartBtn.disabled = false;
            addToCartBtn.innerHTML = '<i class="fas fa-shopping-bag me-2"></i> Add to Cart';
            addToCartBtn.classList.remove('btn-secondary');
            addToCartBtn.classList.add('btn-gold');
        }
    }

    // Run check on page load
    checkStock();

    // Listen for changes in the dropdown
    if (variantSelect) {
        variantSelect.addEventListener('change', checkStock);
    }

    // Handle Add to Cart
    addToCartBtn.addEventListener('click', function() {
        if (this.disabled) return; 

        const productId = this.getAttribute('data-id');
        const qty = document.getElementById('qty').value;
        
        // Ensure we pass 'null' as a string if there are no variants
        const variantId = variantSelect ? variantSelect.value : 'null';

        let bodyData = `action=add&product_id=${productId}&quantity=${qty}&variant_id=${variantId}`;

        const originalText = addToCartBtn.innerHTML;
        addToCartBtn.innerHTML = 'Adding...';
        addToCartBtn.disabled = true;

        fetch('ajax_cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: bodyData
        })
        .then(res => {
            if (!res.ok) throw new Error("Server status: " + res.status);
            return res.json();
        })
        .then(data => {
            if(data.success) {
                // Show the Bootstrap Toast
                const toastEl = document.getElementById('cartToast');
                const toast = new bootstrap.Toast(toastEl, { delay: 1500 });
                toast.show();
                
                // Wait 1.5 seconds so the user can read it, then reload to update the cart count
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
                addToCartBtn.innerHTML = originalText;
                addToCartBtn.disabled = false;
            }
        })
        .catch(error => {
            alert('Network Error: ' + error.message);
            addToCartBtn.innerHTML = originalText;
            addToCartBtn.disabled = false;
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>