<?php
require_once __DIR__ . '/../includes/config.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$statuses = ['new', 'processing', 'shipped', 'delivered', 'cancelled'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    $new_status = $_POST['status'] ?? null;

    if ($order_id === false || $order_id === null || !is_string($new_status) || !in_array($new_status, $statuses, true)) {
        $errors[] = 'The order status could not be updated. Choose a valid order and status.';
    } else {
        $update_stmt = $pdo->prepare('UPDATE orders SET order_status = ? WHERE order_id = ?');
        $update_stmt->execute([$new_status, $order_id]);
        header('Location: orders.php?success=1', true, 303);
        exit;
    }
}

$orders_stmt = $pdo->query(
    'SELECT o.*, u.first_name, u.last_name
     FROM orders o
     LEFT JOIN users u ON o.user_id = u.user_id
     ORDER BY o.created_at DESC'
);
$orders = $orders_stmt->fetchAll();
$items_stmt = $pdo->prepare(
    'SELECT product_name, variant_description, quantity, unit_price, total_price
     FROM order_items
     WHERE order_id = ?'
);
$order_items_by_id = [];
foreach ($orders as $order) {
    $items_stmt->execute([(int) $order['order_id']]);
    $order_items_by_id[(int) $order['order_id']] = $items_stmt->fetchAll();
}

$page_title = 'Manage Orders | ' . SITE_NAME;
$page_heading = 'Manage Orders';
$active_page = 'orders';
?>
<?php include __DIR__ . '/includes/admin-header.php'; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" role="status">Order status updated successfully.</div>
        <?php endif; ?>
        <?php if ($errors !== []): ?>
            <div class="alert alert-danger" role="alert">
                <?php foreach ($errors as $error): ?>
                    <p class="mb-0"><?= escape_html($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <section class="card admin-table-card">
            <div class="card-header">
                <h2 class="h5 mb-0">Orders</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Order #</th>
                            <th scope="col">Date</th>
                            <th scope="col">Customer</th>
                            <th scope="col">Total</th>
                            <th scope="col">Payment</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders === []): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No orders found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <?php
                                $order_id = (int) $order['order_id'];
                                $modal_id = 'order-modal-' . $order_id;
                                $customer_name = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''));
                                ?>
                                <tr>
                                    <td class="fw-semibold">#<?= escape_html($order['order_number']) ?></td>
                                    <td><?= escape_html(date('d M Y, H:i', strtotime($order['created_at']))) ?></td>
                                    <td>
                                        <?= escape_html($customer_name !== '' ? $customer_name : 'Guest') ?>
                                        <small class="d-block text-muted"><?= escape_html($order['customer_email']) ?></small>
                                    </td>
                                    <td class="fw-semibold"><?= escape_html(formatPrice($order['total_amount'])) ?></td>
                                    <td>
                                        <span class="badge text-bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'secondary' ?>">
                                            <?= escape_html(strtoupper($order['payment_status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="post" action="orders.php" class="d-flex align-items-center gap-2">
                                            <input type="hidden" name="order_id" value="<?= $order_id ?>">
                                            <select name="status" class="form-select form-select-sm"
                                                    aria-label="Status for order #<?= escape_html($order['order_number']) ?>">
                                                <?php foreach ($statuses as $status): ?>
                                                    <option value="<?= escape_html($status) ?>" <?= $order['order_status'] === $status ? 'selected' : '' ?>>
                                                        <?= escape_html(ucfirst($status)) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-sm btn-outline-primary text-nowrap" type="submit" name="update_status">
                                                Update
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-secondary" type="button"
                                                data-bs-toggle="modal" data-bs-target="#<?= escape_html($modal_id) ?>"
                                                aria-label="View items for order #<?= escape_html($order['order_number']) ?>">
                                            <i class="fas fa-eye me-1" aria-hidden="true"></i>View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php foreach ($orders as $order): ?>
                <?php
                $order_id = (int) $order['order_id'];
                $modal_id = 'order-modal-' . $order_id;
                $items = $order_items_by_id[$order_id];
                ?>
                <div class="modal fade" id="<?= escape_html($modal_id) ?>" tabindex="-1"
                     aria-labelledby="<?= escape_html($modal_id) ?>-title" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2 class="modal-title h5" id="<?= escape_html($modal_id) ?>-title">
                                    Order Items - #<?= escape_html($order['order_number']) ?>
                                </h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col">Product</th>
                                                <th scope="col">Qty</th>
                                                <th scope="col">Price</th>
                                                <th scope="col">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($items === []): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-3">No items found.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($items as $item): ?>
                                                    <tr>
                                                        <td>
                                                            <?= escape_html($item['product_name']) ?>
                                                            <?php if (!empty($item['variant_description'])): ?>
                                                                <small class="d-block text-muted"><?= escape_html($item['variant_description']) ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= (int) $item['quantity'] ?></td>
                                                        <td><?= escape_html(formatPrice($item['unit_price'])) ?></td>
                                                        <td><?= escape_html(formatPrice($item['total_price'])) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    </main>
<?php include __DIR__ . '/includes/admin-footer.php'; ?>
