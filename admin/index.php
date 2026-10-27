<?php
require_once __DIR__ . '/../includes/config.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Dashboard | ' . SITE_NAME;
$page_heading = 'Dashboard Overview';
$active_page = 'dashboard';

$sales = $pdo->query(
    "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid'"
)->fetchColumn();
$orders_count = $pdo->query('SELECT COUNT(order_id) FROM orders')->fetchColumn();
$products_count = $pdo->query('SELECT COUNT(product_id) FROM products')->fetchColumn();
$recent_orders = $pdo->query(
    'SELECT order_number, customer_email, total_amount, order_status
     FROM orders
     ORDER BY created_at DESC
     LIMIT 5'
)->fetchAll();
?>
<?php include __DIR__ . '/includes/admin-header.php'; ?>

        <section class="row g-3 mb-4" aria-label="Store summary">
            <div class="col-sm-6 col-xl-4">
                <article class="card admin-summary-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="admin-summary-icon text-gold" aria-hidden="true">
                            <i class="fas fa-sack-dollar"></i>
                        </span>
                        <div>
                            <p class="text-muted text-uppercase small fw-semibold mb-1">Total Sales (Paid)</p>
                            <p class="h4 mb-0"><?= escape_html(formatPrice($sales)) ?></p>
                        </div>
                    </div>
                </article>
            </div>
            <div class="col-sm-6 col-xl-4">
                <article class="card admin-summary-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="admin-summary-icon text-gold" aria-hidden="true">
                            <i class="fas fa-box"></i>
                        </span>
                        <div>
                            <p class="text-muted text-uppercase small fw-semibold mb-1">Total Orders</p>
                            <p class="h4 mb-0"><?= (int) $orders_count ?></p>
                        </div>
                    </div>
                </article>
            </div>
            <div class="col-sm-6 col-xl-4">
                <article class="card admin-summary-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="admin-summary-icon text-gold" aria-hidden="true">
                            <i class="fas fa-tags"></i>
                        </span>
                        <div>
                            <p class="text-muted text-uppercase small fw-semibold mb-1">Total Products</p>
                            <p class="h4 mb-0"><?= (int) $products_count ?></p>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section class="card admin-table-card">
            <div class="card-header">
                <h2 class="h5 mb-0">Recent Orders</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Order #</th>
                            <th scope="col">Customer Email</th>
                            <th scope="col">Total</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_orders === []): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No orders yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_orders as $order): ?>
                                <?php
                                $status_class = match ($order['order_status']) {
                                    'delivered' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'warning',
                                };
                                ?>
                                <tr>
                                    <td class="fw-semibold">#<?= escape_html($order['order_number']) ?></td>
                                    <td><?= escape_html($order['customer_email']) ?></td>
                                    <td class="fw-semibold"><?= escape_html(formatPrice($order['total_amount'])) ?></td>
                                    <td>
                                        <span class="badge text-bg-<?= $status_class ?>">
                                            <?= escape_html(ucfirst($order['order_status'])) ?>
                                        </span>
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
