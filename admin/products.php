<?php
require_once __DIR__ . '/../includes/config.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Manage Products | ' . SITE_NAME;
$page_heading = 'Manage Products';
$active_page = 'products';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$form_data = [
    'product_id' => '',
    'product_name' => '',
    'category_id' => '',
    'base_price' => '',
    'stock_quantity' => '0',
    'description' => '',
    'image_url' => '',
];
$errors = [];
$notice = $_SESSION['admin_products_notice'] ?? null;
unset($_SESSION['admin_products_notice']);

$categories_stmt = $pdo->prepare(
    'SELECT category_id, category_name FROM categories ORDER BY category_name'
);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll();

$make_slug = static function (string $name): string {
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');

    return $slug !== '' ? substr($slug, 0, 180) : 'product';
};

$redirect_with_notice = static function (string $message): never {
    $_SESSION['admin_products_notice'] = $message;
    header('Location: products.php', true, 303);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token'])
        || !is_string($_POST['csrf_token'])
        || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $errors[] = 'Your session has expired. Refresh the page and try again.';
    } else {
        $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';

        // --- Handle Add Category ---
        if ($action === 'add_category') {
            $cat_name = trim($_POST['category_name']);
            $cat_slug = strtolower(preg_replace('/[^a-zA-Z0-9-]+/', '-', $cat_name));
            
            if (empty($cat_name)) {
                $errors[] = 'Category name cannot be empty.';
            } else {
                $check = $pdo->prepare("SELECT category_id FROM categories WHERE category_name = ?");
                $check->execute([$cat_name]);
                if ($check->rowCount() > 0) {
                    $errors[] = 'That category already exists.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO categories (category_name, category_slug) VALUES (?, ?)");
                    $stmt->execute([$cat_name, $cat_slug]);
                    $redirect_with_notice('Category added successfully.');
                }
            }
        }
        // --- End Add Category ---

        // --- Handle Variant Actions ---
        elseif ($action === 'add_variant') {
            $v_product_id = (int)$_POST['v_product_id'];
            $v_type = $_POST['v_type'];
            $v_value = trim($_POST['v_value']);
            $v_price = (float)$_POST['v_price'];
            $v_stock = (int)$_POST['v_stock'];

            $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, variant_type, variant_value, price_adjustment, stock_quantity) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$v_product_id, $v_type, $v_value, $v_price, $v_stock]);
            $redirect_with_notice('Variant added successfully.');
        } 
        elseif ($action === 'update_variant') {
            $v_id = (int)$_POST['variant_id'];
            $v_price = (float)$_POST['v_price'];
            $v_stock = (int)$_POST['v_stock'];

            $stmt = $pdo->prepare("UPDATE product_variants SET price_adjustment = ?, stock_quantity = ? WHERE variant_id = ?");
            $stmt->execute([$v_price, $v_stock, $v_id]);
            $redirect_with_notice('Variant updated successfully.');
        }
        elseif ($action === 'delete_variant') {
            $v_id = (int)$_POST['variant_id'];
            $stmt = $pdo->prepare("DELETE FROM product_variants WHERE variant_id = ?");
            $stmt->execute([$v_id]);
            $redirect_with_notice('Variant deleted successfully.');
        }
        // --- End Variant Actions ---

        elseif ($action === 'delete') {
            $product_id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            if ($product_id === false || $product_id === null) {
                $errors[] = 'Invalid product selected for deletion.';
            } else {
                try {
                    $pdo->beginTransaction();

                    $product_stmt = $pdo->prepare(
                        'SELECT product_id FROM products WHERE product_id = ? FOR UPDATE'
                    );
                    $product_stmt->execute([$product_id]);
                    if (!$product_stmt->fetch()) {
                        $pdo->rollBack();
                        $errors[] = 'That product could not be found.';
                    } else {
                        $order_item_stmt = $pdo->prepare(
                            'SELECT 1 FROM order_items WHERE product_id = ? LIMIT 1'
                        );
                        $order_item_stmt->execute([$product_id]);

                        if ($order_item_stmt->fetchColumn()) {
                            $deactivate_stmt = $pdo->prepare(
                                'UPDATE products SET is_active = 0 WHERE product_id = ?'
                            );
                            $deactivate_stmt->execute([$product_id]);
                            $pdo->commit();
                            $redirect_with_notice('Product has order history, so it was deactivated instead of permanently deleted.');
                        }

                        $delete_stmt = $pdo->prepare('DELETE FROM products WHERE product_id = ?');
                        $delete_stmt->execute([$product_id]);
                        $pdo->commit();
                        $redirect_with_notice('Product deleted successfully.');
                    }
                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log('Admin product deletion failed: ' . $e->getMessage());
                    $errors[] = 'The product could not be deleted. Please try again.';
                }
            }
        } elseif ($action === 'save') {
            $product_id = null;
            if (isset($_POST['product_id']) && $_POST['product_id'] !== '') {
                $product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
                ]);
                if ($product_id === false) {
                    $errors[] = 'Invalid product selected for editing.';
                }
            }

            foreach (['product_name', 'category_id', 'base_price', 'stock_quantity', 'description', 'image_url'] as $field) {
                if (isset($_POST[$field]) && is_string($_POST[$field])) {
                    $form_data[$field] = trim($_POST[$field]);
                }
            }
            $form_data['product_id'] = $product_id ? (string) $product_id : '';

            $category_id = filter_var($form_data['category_id'], FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            $stock_quantity = filter_var($form_data['stock_quantity'], FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 0, 'max_range' => 2147483647],
            ]);
            $price = filter_var($form_data['base_price'], FILTER_VALIDATE_FLOAT);

            if ($form_data['product_name'] === '' || mb_strlen($form_data['product_name']) > 200) {
                $errors[] = 'Product name is required and must be 200 characters or fewer.';
            }
            if ($category_id === false) {
                $errors[] = 'Choose a valid category.';
            }
            if ($price === false || $price < 0 || $price > 99999999.99) {
                $errors[] = 'Enter a valid non-negative price.';
            }
            if ($stock_quantity === false) {
                $errors[] = 'Stock must be a non-negative whole number.';
            }

            if ($category_id !== false) {
                $category_stmt = $pdo->prepare('SELECT 1 FROM categories WHERE category_id = ?');
                $category_stmt->execute([$category_id]);
                if (!$category_stmt->fetchColumn()) {
                    $errors[] = 'Choose an existing category.';
                }
            }

            if ($product_id !== null && $errors === []) {
                $existing_stmt = $pdo->prepare('SELECT product_id FROM products WHERE product_id = ?');
                $existing_stmt->execute([$product_id]);
                if (!$existing_stmt->fetchColumn()) {
                    $errors[] = 'The product you are editing no longer exists.';
                }
            }

            // --- Handle Main Image Upload ---
            $uploaded_image = $form_data['image_url'];
            
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['product_image']['tmp_name'];
                $file_name = $_FILES['product_image']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($file_ext, $allowed)) {
                    $uploaded_image = uniqid('prod_') . '.' . $file_ext;
                    $upload_dir = '../assets/images/products/';
                    
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    if (!move_uploaded_file($file_tmp, $upload_dir . $uploaded_image)) {
                        $errors[] = 'Failed to save the uploaded image. Check folder permissions.';
                    }
                } else {
                    $errors[] = 'Invalid image format. Only JPG, PNG, and WEBP are allowed.';
                }
            } elseif (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = 'There was an error uploading the image. Please try again.';
            }

            // --- Handle Gallery Images Upload (Max 4) ---
            $gallery_files = [];
            if (isset($_FILES['gallery_images']) && is_array($_FILES['gallery_images']['name'])) {
                $file_count = count($_FILES['gallery_images']['name']);
                $limit = min($file_count, 4); 
                
                for ($i = 0; $i < $limit; $i++) {
                    if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_OK) {
                        $tmp_name = $_FILES['gallery_images']['tmp_name'][$i];
                        $name = $_FILES['gallery_images']['name'][$i];
                        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                            $new_name = uniqid('gal_') . '.' . $ext;
                            $upload_dir = '../assets/images/products/';
                            
                            if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                                $gallery_files[] = $new_name;
                            }
                        }
                    }
                }
            }

            if ($errors === []) {
                try {
                    if ($product_id !== null) {
                        $update_stmt = $pdo->prepare(
                            'UPDATE products
                             SET product_name = ?, category_id = ?, base_price = ?,
                                 stock_quantity = ?, description = ?, image_url = ?
                             WHERE product_id = ?'
                        );
                        $update_stmt->execute([
                            $form_data['product_name'],
                            $category_id,
                            $price,
                            $stock_quantity,
                            $form_data['description'] !== '' ? $form_data['description'] : null,
                            $uploaded_image !== '' ? $uploaded_image : null,
                            $product_id,
                        ]);
                        
                        if (!empty($gallery_files)) {
                            $img_stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
                            foreach ($gallery_files as $img) {
                                $img_stmt->execute([$product_id, $img]);
                            }
                        }

                        $redirect_with_notice('Product updated successfully.');
                    }

                    $base_slug = $make_slug($form_data['product_name']);
                    $slug = $base_slug;
                    $suffix = 2;
                    $slug_check = $pdo->prepare('SELECT 1 FROM products WHERE product_slug = ?');
                    while (true) {
                        $slug_check->execute([$slug]);
                        if (!$slug_check->fetchColumn()) {
                            break;
                        }
                        $slug = substr($base_slug, 0, 180 - strlen((string) $suffix)) . '-' . $suffix;
                        $suffix++;
                    }

                    $insert_stmt = $pdo->prepare(
                        'INSERT INTO products (
                            category_id, product_name, product_slug, description,
                            base_price, stock_quantity, image_url
                         ) VALUES (?, ?, ?, ?, ?, ?, ?)'
                    );
                    $insert_stmt->execute([
                        $category_id,
                        $form_data['product_name'],
                        $slug,
                        $form_data['description'] !== '' ? $form_data['description'] : null,
                        $price,
                        $stock_quantity,
                        $uploaded_image !== '' ? $uploaded_image : null,
                    ]);
                    
                    if (!empty($gallery_files)) {
                        $new_product_id = $pdo->lastInsertId();
                        $img_stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
                        foreach ($gallery_files as $img) {
                            $img_stmt->execute([$new_product_id, $img]);
                        }
                    }

                    $redirect_with_notice('Product added successfully.');
                } catch (PDOException $e) {
                    error_log('Admin product save failed: ' . $e->getMessage());
                    $errors[] = 'The product could not be saved. Check for duplicate data and try again.';
                }
            }
        } else {
            $errors[] = 'Unsupported product action.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['edit'])) {
    $edit_id = filter_var($_GET['edit'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    if ($edit_id === false || $edit_id === null) {
        $errors[] = 'Invalid product selected for editing.';
    } else {
        $edit_stmt = $pdo->prepare(
            'SELECT product_id, product_name, category_id, base_price, stock_quantity, description, image_url
             FROM products WHERE product_id = ?'
        );
        $edit_stmt->execute([$edit_id]);
        $product_to_edit = $edit_stmt->fetch();
        if ($product_to_edit) {
            $form_data = array_merge($form_data, $product_to_edit);
        } else {
            $errors[] = 'The product you selected could not be found.';
        }
    }
}

$products_stmt = $pdo->prepare(
    'SELECT p.product_id, p.product_name, p.product_slug, p.base_price, p.stock_quantity,
            p.image_url, p.is_active, p.created_at, c.category_name
     FROM products p
     INNER JOIN categories c ON c.category_id = p.category_id
     ORDER BY p.created_at DESC, p.product_id DESC'
);
$products_stmt->execute();
$products = $products_stmt->fetchAll();
?>
<?php include __DIR__ . '/includes/admin-header.php'; ?>

        <?php if (is_string($notice) && $notice !== ''): ?>
            <div class="alert alert-success" role="status"><?= escape_html($notice) ?></div>
        <?php endif; ?>
        <?php if ($errors !== []): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= escape_html($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Add Category Form -->
        <section class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">Add New Category</h2>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3 align-items-end">
                    <input type="hidden" name="csrf_token" value="<?= escape_html($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="add_category">
                    
                    <div class="col-md-8">
                        <label class="form-label" for="category-name">Category Name (e.g., Handmade Candles)</label>
                        <input class="form-control" id="category-name" name="category_name" type="text" required placeholder="Enter category name">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-gold w-100" type="submit">Add Category</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0"><?= $form_data['product_id'] !== '' ? 'Edit Product' : 'Add New Product' ?></h2>
            </div>
            <div class="card-body">
                <form method="post" action="products.php<?= $form_data['product_id'] !== '' ? '?edit=' . (int) $form_data['product_id'] : '' ?>"
                      class="row g-3" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= escape_html($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="product_id" value="<?= escape_html($form_data['product_id']) ?>">
                    
                    <input type="hidden" name="image_url" value="<?= escape_html($form_data['image_url']) ?>">

                    <div class="col-md-6">
                        <label class="form-label" for="product-name">Name</label>
                        <input class="form-control" id="product-name" name="product_name" type="text"
                               maxlength="200" required value="<?= escape_html($form_data['product_name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="category-id">Category ID</label>
                        <select class="form-select" id="category-id" name="category_id" required>
                            <option value="">Select category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['category_id'] ?>"
                                    <?= (string) $form_data['category_id'] === (string) $category['category_id'] ? 'selected' : '' ?>>
                                    <?= (int) $category['category_id'] ?> — <?= escape_html($category['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="base-price">Price (R)</label>
                        <input class="form-control" id="base-price" name="base_price" type="number"
                               min="0" max="99999999.99" step="0.01" required
                               value="<?= escape_html($form_data['base_price']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="stock-quantity">Stock</label>
                        <input class="form-control" id="stock-quantity" name="stock_quantity" type="number"
                               min="0" step="1" required value="<?= escape_html($form_data['stock_quantity']) ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="product-image">Product Image (Main)</label>
                        <input class="form-control mb-2" id="product-image" name="product_image" type="file" accept="image/jpeg, image/png, image/webp">
                        <?php if (!empty($form_data['image_url'])): ?>
                            <small class="text-muted d-block">Current Main: <?= escape_html($form_data['image_url']) ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="gallery-images">Gallery Images (Max 4 extra)</label>
                        <input class="form-control" id="gallery-images" name="gallery_images[]" type="file" accept="image/jpeg, image/png, image/webp" multiple>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= escape_html($form_data['description']) ?></textarea>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-gold" type="submit">
                            <?= $form_data['product_id'] !== '' ? 'Update Product' : 'Add New Product' ?>
                        </button>
                        <?php if ($form_data['product_id'] !== ''): ?>
                            <a class="btn btn-outline-secondary" href="products.php">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </section>

        <!-- Variant Management Section (Only shows when editing a product) -->
        <?php if ($form_data['product_id'] !== ''): ?>
            <?php
            // Fetch variants for this product
            $var_stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ?");
            $var_stmt->execute([$form_data['product_id']]);
            $variants = $var_stmt->fetchAll();
            ?>
            <section class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Manage Variants (<?= escape_html($form_data['product_name']) ?>)</h2>
                </div>
                <div class="card-body">
                    
                    <!-- Table of existing variants -->
                    <?php if ($variants): ?>
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Value</th>
                                    <th>Price Adj. (R)</th>
                                    <th>Stock</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($variants as $v): ?>
                                    <tr>
                                        <td class="text-capitalize"><?= escape_html($v['variant_type']) ?></td>
                                        <td class="fw-bold"><?= escape_html($v['variant_value']) ?></td>
                                        <td colspan="3">
                                            <div class="d-flex gap-2 justify-content-end align-items-center">
                                                <form method="POST" class="d-flex gap-2 align-items-center">
                                                    <input type="hidden" name="csrf_token" value="<?= escape_html($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="action" value="update_variant">
                                                    <input type="hidden" name="variant_id" value="<?= $v['variant_id'] ?>">
                                                    
                                                    <input type="number" name="v_price" class="form-control form-control-sm" step="0.01" value="<?= $v['price_adjustment'] ?>" style="width: 100px;" title="Price Adjustment">
                                                    <input type="number" name="v_stock" class="form-control form-control-sm" value="<?= $v['stock_quantity'] ?>" style="width: 80px;" title="Stock">
                                                    
                                                    <button type="submit" class="btn btn-sm btn-outline-success">Save</button>
                                                </form>
                                                
                                                <!-- Delete Variant Form -->
                                                <form method="POST" onsubmit="return confirm('Delete this variant?');">
                                                    <input type="hidden" name="csrf_token" value="<?= escape_html($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="action" value="delete_variant">
                                                    <input type="hidden" name="variant_id" value="<?= $v['variant_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted">No variants added yet. (e.g., Flavors, Sizes)</p>
                    <?php endif; ?>

                    <hr>
                    <!-- Form to add a new variant -->
                    <h6 class="mb-3">Add New Variant</h6>
                    <form method="POST" class="row g-2 align-items-end">
                        <input type="hidden" name="csrf_token" value="<?= escape_html($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="add_variant">
                        <input type="hidden" name="v_product_id" value="<?= $form_data['product_id'] ?>">
                        
                        <div class="col-md-3">
                            <label class="form-label small">Type</label>
                            <select name="v_type" class="form-select form-select-sm" required>
                                <option value="size">Size</option>
                                <option value="flavour">Flavour</option>
                                <option value="colour">Colour</option>
                                <option value="volume">Volume</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Value (e.g., Lavender)</label>
                            <input type="text" name="v_value" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Price Adj. (R)</label>
                            <input type="number" name="v_price" class="form-control form-control-sm" value="0.00" step="0.01" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Stock</label>
                            <input type="number" name="v_stock" class="form-control form-control-sm" value="10" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-gold w-100">Add Variant</button>
                        </div>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <section class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">Products</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Product</th>
                            <th scope="col">Category</th>
                            <th scope="col">Price</th>
                            <th scope="col">Stock</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products === []): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No products found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($product['image_url'])): ?>
                                                <img src="../assets/images/products/<?= escape_html($product['image_url']) ?>" alt="img" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                            <?php endif; ?>
                                            <div>
                                                <span class="fw-semibold"><?= escape_html($product['product_name']) ?></span>
                                                <small class="d-block text-muted"><?= escape_html($product['product_slug']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= escape_html($product['category_name']) ?></td>
                                    <td><?= escape_html(formatPrice($product['base_price'])) ?></td>
                                    <td><?= (int) $product['stock_quantity'] ?></td>
                                    <td>
                                        <span class="badge text-bg-<?= (int) $product['is_active'] === 1 ? 'success' : 'secondary' ?>">
                                            <?= (int) $product['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="products.php?edit=<?= (int) $product['product_id'] ?>">
                                            <i class="fas fa-pen me-1" aria-hidden="true"></i>Edit
                                        </a>
                                        <?php if ((int) $product['is_active'] === 1): ?>
                                            <form method="post" action="products.php" class="d-inline"
                                                  data-confirm="Delete or deactivate this product? Products with order history will be deactivated.">
                                                <input type="hidden" name="csrf_token" value="<?= escape_html($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="product_id" value="<?= (int) $product['product_id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                                    <i class="fas fa-trash me-1" aria-hidden="true"></i>Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
<?php include __DIR__ . '/includes/admin-footer.php'; ?>