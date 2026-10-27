<?php
require_once 'includes/config.php';

$page_title = 'Shop | ' . SITE_NAME;

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $respond = static function (array $payload, int $status = 200): void {
        http_response_code($status);
        echo json_encode($payload, JSON_THROW_ON_ERROR);
        exit;
    };

    if (
        !isset($_POST['csrf_token'])
        || !is_string($_POST['csrf_token'])
        || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $respond(['success' => false, 'message' => 'Your session has expired. Refresh the page and try again.'], 419);
    }

    $product_id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    if ($product_id === false || $product_id === null) {
        $respond(['success' => false, 'message' => 'Invalid product.'], 400);
    }

    try {
        if ($_POST['ajax_action'] === 'add_to_cart') {
            $pdo->beginTransaction();

            $product_stmt = $pdo->prepare(
                'SELECT stock_quantity FROM products WHERE product_id = ? AND is_active = 1 FOR UPDATE'
            );
            $product_stmt->execute([$product_id]);
            $product = $product_stmt->fetch();

            if (!$product) {
                $pdo->rollBack();
                $respond(['success' => false, 'message' => 'This product is no longer available.'], 404);
            }

            $scope_sql = isLoggedIn() ? 'user_id = ?' : 'session_id = ?';
            $scope_value = isLoggedIn() ? (int) $_SESSION['user_id'] : $_SESSION['cart_session_id'];
            $cart_params = [$scope_value, $product_id];

            $quantity_stmt = $pdo->prepare(
                "SELECT COALESCE(SUM(quantity), 0) FROM cart
                 WHERE {$scope_sql} AND product_id = ? AND variant_id IS NULL"
            );
            $quantity_stmt->execute($cart_params);
            $cart_quantity = (int) $quantity_stmt->fetchColumn();

            if ($cart_quantity >= (int) $product['stock_quantity']) {
                $pdo->rollBack();
                $respond(['success' => false, 'message' => 'There is not enough stock to add another item.'], 409);
            }

            $existing_stmt = $pdo->prepare(
                "SELECT cart_id FROM cart
                 WHERE {$scope_sql} AND product_id = ? AND variant_id IS NULL
                 ORDER BY cart_id ASC LIMIT 1 FOR UPDATE"
            );
            $existing_stmt->execute($cart_params);
            $cart_id = $existing_stmt->fetchColumn();

            if ($cart_id !== false) {
                $update_stmt = $pdo->prepare('UPDATE cart SET quantity = quantity + 1 WHERE cart_id = ?');
                $update_stmt->execute([(int) $cart_id]);
            } else {
                $user_id = isLoggedIn() ? (int) $_SESSION['user_id'] : null;
                $session_id = isLoggedIn() ? null : $_SESSION['cart_session_id'];
                $insert_stmt = $pdo->prepare(
                    'INSERT INTO cart (user_id, session_id, product_id, quantity) VALUES (?, ?, ?, 1)'
                );
                $insert_stmt->execute([$user_id, $session_id, $product_id]);
            }

            $pdo->commit();
            $respond([
                'success' => true,
                'message' => 'Added to your cart.',
                'cart_count' => getCartCount($pdo),
            ]);
        }

        if ($_POST['ajax_action'] === 'toggle_wishlist') {
            if (!isLoggedIn()) {
                $respond([
                    'success' => false,
                    'login_required' => true,
                    'message' => 'Sign in to save products to your wishlist.',
                ], 401);
            }

            $product_stmt = $pdo->prepare('SELECT 1 FROM products WHERE product_id = ? AND is_active = 1');
            $product_stmt->execute([$product_id]);
            if (!$product_stmt->fetchColumn()) {
                $respond(['success' => false, 'message' => 'This product is no longer available.'], 404);
            }

            $user_id = (int) $_SESSION['user_id'];
            $delete_stmt = $pdo->prepare('DELETE FROM wishlist WHERE user_id = ? AND product_id = ?');
            $delete_stmt->execute([$user_id, $product_id]);

            if ($delete_stmt->rowCount() > 0) {
                $respond(['success' => true, 'wishlisted' => false, 'message' => 'Removed from your wishlist.']);
            }

            $insert_stmt = $pdo->prepare('INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)');
            $insert_stmt->execute([$user_id, $product_id]);
            $respond(['success' => true, 'wishlisted' => true, 'message' => 'Added to your wishlist.']);
        }

        $respond(['success' => false, 'message' => 'Unsupported action.'], 400);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Shop AJAX request failed: ' . $e->getMessage());
        $respond(['success' => false, 'message' => 'The request could not be completed. Please try again.'], 500);
    }
}

$category_slug = isset($_GET['category']) && is_string($_GET['category'])
    ? trim($_GET['category'])
    : '';
$search = isset($_GET['search']) && is_string($_GET['search'])
    ? trim(substr($_GET['search'], 0, 100))
    : '';
$sort_options = ['newest', 'price_low', 'price_high', 'name'];
$sort = isset($_GET['sort']) && is_string($_GET['sort']) && in_array($_GET['sort'], $sort_options, true)
    ? $_GET['sort']
    : 'newest';

$categories_stmt = $pdo->prepare(
    'SELECT category_name, category_slug FROM categories WHERE is_active = 1 ORDER BY category_name'
);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll();

$selected_category_name = '';
foreach ($categories as $category) {
    if ($category['category_slug'] === $category_slug) {
        $selected_category_name = $category['category_name'];
        break;
    }
}

$sql = 'SELECT p.*, c.category_name, c.category_slug
        FROM products p
        INNER JOIN categories c ON c.category_id = p.category_id
        WHERE p.is_active = 1 AND c.is_active = 1';
$params = [];

if ($category_slug !== '') {
    $sql .= ' AND c.category_slug = ?';
    $params[] = $category_slug;
}

if ($search !== '') {
    $sql .= ' AND (p.product_name LIKE ? OR p.description LIKE ?)';
    $search_term = '%' . $search . '%';
    $params[] = $search_term;
    $params[] = $search_term;
}

switch ($sort) {
    case 'price_low':
        $sql .= ' ORDER BY COALESCE(p.sale_price, p.base_price) ASC, p.product_id DESC';
        break;
    case 'price_high':
        $sql .= ' ORDER BY COALESCE(p.sale_price, p.base_price) DESC, p.product_id DESC';
        break;
    case 'name':
        $sql .= ' ORDER BY p.product_name ASC';
        break;
    default:
        $sql .= ' ORDER BY p.created_at DESC, p.product_id DESC';
}

$product_stmt = $pdo->prepare($sql);
$product_stmt->execute($params);
$products = $product_stmt->fetchAll();

$wishlisted_products = [];
if (isLoggedIn()) {
    $wishlist_stmt = $pdo->prepare('SELECT product_id FROM wishlist WHERE user_id = ?');
    $wishlist_stmt->execute([(int) $_SESSION['user_id']]);
    $wishlisted_products = array_map('intval', $wishlist_stmt->fetchAll(PDO::FETCH_COLUMN));
}

include 'includes/header.php';
?>

<div class="container py-5" id="shop-page" data-csrf-token="<?= escape_html($_SESSION['csrf_token']) ?>">
    <div class="mb-4">
        <span class="text-gold text-uppercase small fw-semibold">Sibahle Collection</span>
        <h1 class="mb-2"><?= escape_html($selected_category_name !== '' ? $selected_category_name : 'Shop all products') ?></h1>
        <p class="text-muted mb-0">Discover products made with care and inspired by our heritage.</p>
    </div>

    <div id="shop-message" class="mb-3" role="status" aria-live="polite"></div>

    <div class="row g-4">
        <aside class="col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5 mb-3">Categories</h2>
                    <nav aria-label="Product categories">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <a class="text-decoration-none <?= $category_slug === '' ? 'fw-bold text-gold' : 'text-dark' ?>"
                                   href="shop.php<?= $search !== '' ? '?' . escape_html(http_build_query(['search' => $search, 'sort' => $sort])) : '' ?>">
                                    All products
                                </a>
                            </li>
                            <?php foreach ($categories as $category): ?>
                                <?php
                                $category_query = ['category' => $category['category_slug']];
                                if ($search !== '') {
                                    $category_query['search'] = $search;
                                }
                                if ($sort !== 'newest') {
                                    $category_query['sort'] = $sort;
                                }
                                ?>
                                <li class="mb-2">
                                    <a class="text-decoration-none <?= $category_slug === $category['category_slug'] ? 'fw-bold text-gold' : 'text-dark' ?>"
                                       href="shop.php?<?= escape_html(http_build_query($category_query)) ?>">
                                        <?= escape_html($category['category_name']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </aside>

        <section class="col-lg-9" aria-label="Products">
            <form method="get" action="shop.php" class="row g-2 align-items-center mb-4">
                <?php if ($category_slug !== ''): ?>
                    <input type="hidden" name="category" value="<?= escape_html($category_slug) ?>">
                <?php endif; ?>
                <div class="col-sm">
                    <label class="visually-hidden" for="product-search">Search products</label>
                    <input class="form-control" type="search" id="product-search" name="search"
                           placeholder="Search products" value="<?= escape_html($search) ?>">
                </div>
                <div class="col-sm-auto">
                    <button class="btn btn-outline-dark" type="submit">Search</button>
                </div>
                <div class="col-sm-auto ms-sm-auto">
                    <label class="visually-hidden" for="product-sort">Sort products</label>
                    <select class="form-select" id="product-sort" name="sort" onchange="this.form.submit()">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                        <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name: A-Z</option>
                    </select>
                </div>
            </form>

            <p class="text-muted mb-3"><?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?></p>

            <?php if ($products === []): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-search fa-2x text-muted mb-3" aria-hidden="true"></i>
                        <h2 class="h5">No products found</h2>
                        <p class="text-muted mb-0">Try another category or search term.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-3 g-md-4">
                    <?php foreach ($products as $product): ?>
                        <?php
                        $product_id = (int) $product['product_id'];
                        $has_sale = $product['sale_price'] !== null && (float) $product['sale_price'] > 0;
                        $image = $product['image_url'] ?: 'placeholder.jpg';
                        $is_wishlisted = in_array($product_id, $wishlisted_products, true);
                        ?>
                        <div class="col-6 col-md-4">
                            <article class="product-card card border-0 shadow-sm h-100">
                                <div class="position-relative overflow-hidden">
                                    <a href="product.php?slug=<?= rawurlencode($product['product_slug']) ?>"
                                       class="d-block" aria-label="View <?= escape_html($product['product_name']) ?>">
                                        <img src="assets/images/products/<?= escape_html($image) ?>"
                                             class="card-img-top product-img"
                                             alt="<?= escape_html($product['product_name']) ?>"
                                             loading="lazy">
                                    </a>
                                    <?php if ($has_sale): ?>
                                        <span class="badge bg-danger position-absolute top-0 start-0 m-2">Sale</span>
                                    <?php endif; ?>
                                    <button class="btn btn-light btn-sm rounded-circle position-absolute top-0 end-0 m-2 wishlist-btn"
                                            type="button"
                                            data-product-id="<?= $product_id ?>"
                                            aria-label="<?= $is_wishlisted ? 'Remove from wishlist' : 'Add to wishlist' ?>"
                                            aria-pressed="<?= $is_wishlisted ? 'true' : 'false' ?>"
                                            title="<?= $is_wishlisted ? 'Remove from wishlist' : 'Add to wishlist' ?>">
                                        <i class="<?= $is_wishlisted ? 'fas' : 'far' ?> fa-heart text-danger" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <small class="text-muted"><?= escape_html($product['category_name']) ?></small>
                                    <h2 class="h6 card-title mt-1">
                                        <a href="product.php?slug=<?= rawurlencode($product['product_slug']) ?>"
                                           class="text-dark text-decoration-none">
                                            <?= escape_html($product['product_name']) ?>
                                        </a>
                                    </h2>
                                    <div class="mt-auto">
                                        <div class="mb-3">
                                            <?php if ($has_sale): ?>
                                                <span class="text-muted text-decoration-line-through small">
                                                    <?= escape_html(formatPrice($product['base_price'])) ?>
                                                </span>
                                                <span class="price fw-bold text-danger ms-1">
                                                    <?= escape_html(formatPrice($product['sale_price'])) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="price fw-bold">
                                                    <?= escape_html(formatPrice($product['base_price'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <button class="btn btn-gold btn-sm w-100 add-to-cart"
                                                type="button"
                                                data-product-id="<?= $product_id ?>"
                                                <?= (int) $product['stock_quantity'] < 1 ? 'disabled' : '' ?>>
                                            <i class="fas fa-cart-plus me-1" aria-hidden="true"></i>
                                            <?= (int) $product['stock_quantity'] < 1 ? 'Out of stock' : 'Add to Cart' ?>
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
