<?php
require_once 'includes/config.php';

$page_title = 'Sibahle We Are Beautiful | Clothing, Health, Accessories';

// Prepare even fixed queries consistently so future filters stay parameterized.
$featured_stmt = $pdo->prepare(
    'SELECT p.product_id, p.product_name, p.product_slug, p.base_price, p.sale_price, p.image_url,
            c.category_name
     FROM products p
     INNER JOIN categories c ON c.category_id = p.category_id
     WHERE p.is_featured = 1 AND p.is_active = 1 AND c.is_active = 1
     ORDER BY p.created_at DESC
     LIMIT 8'
);
$featured_stmt->execute();
$featured_products = $featured_stmt->fetchAll();

$categories_stmt = $pdo->prepare(
    'SELECT category_id, category_name, category_slug
     FROM categories WHERE is_active = 1 ORDER BY category_name'
);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll();

include 'includes/header.php';
?>

<div class="pattern-border"></div>

<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <h1 class="display-3 fw-bold mb-3">
                    SIBAHLE<br><span class="hero-subtitle">WE ARE BEAUTIFUL</span>
                </h1>
                <p class="lead fw-bold text-gold mb-4 hero-tagline">CLOTHING • HEALTH • ACCESSORIES</p>
                <p class="mb-4 fs-5 hero-motto">"Sibahle Ngobuntu Bethu"</p>
                <p class="mb-5 fs-5">
                    Proudly South African fashion, wellness, beauty, and cultural pride.
                    Beautifully African, made for you, worn with pride.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="shop.php" class="btn btn-gold btn-lg">Shop Collection</a>
                    <a href="about.php" class="btn btn-outline-dark btn-lg">Our Story</a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="assets/images/hero.jpg" alt="Sibahle African Fashion" class="img-fluid rounded-4" style="max-height: 550px; object-fit: cover; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-sand trust-section">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-6 col-md-3 trust-badge">
                <i class="fas fa-hands-holding-heart" aria-hidden="true"></i>
                <h2 class="h6 text-uppercase fw-bold mt-2">Made With Love</h2>
            </div>
            <div class="col-6 col-md-3 trust-badge">
                <i class="fas fa-globe-africa" aria-hidden="true"></i>
                <h2 class="h6 text-uppercase fw-bold mt-2">Inspired By Culture</h2>
            </div>
            <div class="col-6 col-md-3 trust-badge">
                <i class="fas fa-fist-raised" aria-hidden="true"></i>
                <h2 class="h6 text-uppercase fw-bold mt-2">Empowering Confidence</h2>
            </div>
            <div class="col-6 col-md-3 trust-badge">
                <i class="fas fa-users" aria-hidden="true"></i>
                <h2 class="h6 text-uppercase fw-bold mt-2">Celebrating Diversity</h2>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-cream">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5">Our Products</h2>
            <div class="section-divider"></div>
        </div>
        <div class="row g-4 justify-content-center">
            <?php foreach ($categories as $category): ?>
                <?php
                $category_icon = match ($category['category_slug']) {
                    'clothes' => 'tshirt',
                    'herbal-products' => 'leaf',
                    default => 'spa',
                };
                ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="shop.php?category=<?= rawurlencode($category['category_slug']) ?>"
                       class="category-card text-decoration-none">
                        <div class="card border-0 shadow-sm h-100 text-center p-3 bg-white">
                            <div class="mb-3">
                                <i class="fas fa-<?= escape_html($category_icon) ?> fa-2x text-gold"
                                   aria-hidden="true"></i>
                            </div>
                            <h3 class="h6 mb-0 text-dark fw-bold">
                                <?= escape_html($category['category_name']) ?>
                            </h3>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h2>African Fashion &amp; Wellness</h2>
            <a href="shop.php" class="btn btn-outline-dark">
                View All <i class="fas fa-arrow-right ms-2" aria-hidden="true"></i>
            </a>
        </div>
        <div class="row g-4">
            <?php foreach ($featured_products as $product): ?>
                <?php $image_url = $product['image_url'] ?: 'placeholder.jpg'; ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <article class="product-card card h-100">
                        <div class="position-relative overflow-hidden">
                            <a href="product.php?slug=<?= rawurlencode($product['product_slug']) ?>">
                                <img src="assets/images/products/<?= escape_html($image_url) ?>"
                                     class="card-img-top product-img"
                                     alt="<?= escape_html($product['product_name']) ?>" loading="lazy">
                            </a>
                            <button class="btn btn-light btn-sm wishlist-btn position-absolute top-0 end-0 m-2 rounded-circle shadow-sm"
                                    type="button"
                                    data-product-id="<?= (int) $product['product_id'] ?>"
                                    aria-label="Add <?= escape_html($product['product_name']) ?> to wishlist">
                                <i class="far fa-heart text-danger" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <small class="text-muted text-uppercase product-category">
                                <?= escape_html($product['category_name']) ?>
                            </small>
                            <h3 class="h6 card-title mt-2">
                                <a href="product.php?slug=<?= rawurlencode($product['product_slug']) ?>"
                                   class="text-dark text-decoration-none">
                                    <?= escape_html($product['product_name']) ?>
                                </a>
                            </h3>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="price fw-bold">
                                    <?= escape_html(formatPrice($product['sale_price'] ?: $product['base_price'])) ?>
                                </span>
                                <button class="btn btn-gold btn-sm add-to-cart" type="button"
                                        data-product-id="<?= (int) $product['product_id'] ?>"
                                        aria-label="Add <?= escape_html($product['product_name']) ?> to cart">
                                    <i class="fas fa-cart-plus" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-5 bg-african-dark">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-5 mb-4 mb-lg-0 text-center">
                <div class="brand-emblem rounded-circle d-inline-flex align-items-center justify-content-center shadow-lg">
                    <div class="text-center">
                        <p class="h5 text-white mb-0">CELEBRATING</p>
                        <p class="h2 text-dark fw-bold">BEAUTY</p>
                        <p class="h5 text-white mb-0">CULTURE</p>
                        <p class="h5 text-white mb-0">HERITAGE</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 ps-lg-5">
                <h2 class="text-white mb-4">ABOUT SIBAHLE</h2>
                <p class="lead text-gold fw-bold mb-4">Confidence • Comfort • Quality • Authentically African</p>
                <p class="text-white-50 mb-4">
                    SIBAHLE WE ARE BEAUTIFUL is a proudly South African lifestyle brand combining fashion,
                    wellness, beauty, and cultural pride. We create handcrafted products that promote self-care,
                    confidence, and natural living.
                </p>
                <p class="fs-5 brand-quote">
                    "Beautifully African. Made for you. Worn with Pride."
                </p>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-gold-light">
    <div class="container text-center">
        <h2 class="h3 mb-3">Join Our Wellness Community</h2>
        <p class="mb-4">Subscribe for exclusive offers, wellness tips and new arrivals.</p>
        <form class="newsletter-form mx-auto" id="newsletterForm">
            <div class="input-group">
                <label class="visually-hidden" for="newsletter-email">Email address</label>
                <input id="newsletter-email" type="email" class="form-control"
                       placeholder="Enter your email" required>
                <button class="btn btn-gold" type="submit">Subscribe</button>
            </div>
        </form>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
