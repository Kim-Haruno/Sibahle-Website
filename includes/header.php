<?php
require_once 'config.php';

if (!isset($categories)) {
    $categories_stmt = $pdo->prepare(
        'SELECT category_name, category_slug FROM categories WHERE is_active = 1 ORDER BY category_name'
    );
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll();
}
$cart_count = getCartCount($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape_html($page_title ?? SITE_NAME) ?></title>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <meta name="description" content="Sibahle We Are Beautiful - African inspired apparel, herbal teas, bath salts and wellness products. Sibahle Ngobuntu Bethu.">
    
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="top-bar bg-dark text-white py-2">
        <div class="container d-flex justify-content-between align-items-center">
            <small><i class="fas fa-truck me-2"></i>Free delivery on orders over R500</small>
            <small><i class="fas fa-phone me-2"></i>+27 74 498 6813</small>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/images/logo.png" alt="Sibahle Logo" style="height: 60px; width: auto; margin-right: 10px;">
                <div>
                    <span class="brand-name d-block" style="line-height: 1;">Sibahle</span>
                    <small class="brand-tagline" style="font-size: 0.6rem;">NGOBUNTU BETHU</small>
                </div>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Shop</a>
                        <ul class="dropdown-menu">
                            <?php foreach ($categories as $category): ?>
                                <li>
                                    <a class="dropdown-item"
                                       href="shop.php?category=<?= rawurlencode($category['category_slug']) ?>">
                                        <?= escape_html($category['category_name']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                </ul>
                
                                <div class="nav-icons d-flex align-items-center gap-3">
                    
                    <!-- Smart Account/Admin Link -->
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <a href="admin/index.php" class="btn btn-sm btn-outline-dark fw-bold" style="border-radius: 20px;">
                                <i class="fas fa-cog me-1"></i> Admin Dashboard
                            </a>
                        <?php else: ?>
                            <a href="account.php" class="text-dark text-decoration-none fw-bold small text-uppercase">
                                <i class="fas fa-user me-1"></i> My Account
                            </a>
                        <?php endif; ?>
                        
                        <!-- Logout Button -->
                        <a href="logout.php" class="text-danger ms-2" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
                        
                    <?php else: ?>
                        <!-- Not logged in -->
                        <a href="login.php" class="text-dark text-decoration-none fw-bold small text-uppercase">
                            <i class="fas fa-user me-1"></i> Login
                        </a>
                    <?php endif; ?>

                    <!-- Wishlist -->
                    <a href="wishlist.php" class="text-dark ms-2"><i class="fas fa-heart"></i></a>
                    
                    <!-- Cart -->
                    <a href="cart.php" class="text-dark position-relative ms-2">
                        <i class="fas fa-shopping-bag fs-5"></i>
                        <?php $count = getCartCount($pdo); if ($count > 0): ?>
                        <span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle" style="font-size: 0.6rem;">
                            <?= $count ?>
                        </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <main>