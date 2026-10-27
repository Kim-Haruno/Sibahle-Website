<?php
require_once 'includes/config.php';
$pageTitle = 'My Account | ' . SITE_NAME;

// Force login
requireLogin();

$userId = $_SESSION['user_id'];

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Fetch their orders
$orderStmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$orderStmt->execute([$userId]);
$orders = $orderStmt->fetchAll();

include 'includes/header.php';
?>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body text-center p-4">
                        <div class="bg-gold text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                            <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                        </div>
                        <h4><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h4>
                        <p class="text-muted mb-0"><?= htmlspecialchars($user['email']) ?></p>
                        
                        <hr class="my-4">
                        <a href="logout.php" class="btn btn-outline-danger w-100">Logout</a>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-8">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white p-4 border-0">
                        <h4 class="mb-0 fw-bold">Order History</h4>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($orders): ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><strong>#<?= $order['order_number'] ?></strong></td>
                                            <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                            <td><?= formatPrice($order['total_amount']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $order['order_status'] === 'delivered' ? 'success' : 'warning text-dark' ?>">
                                                    <?= ucfirst($order['order_status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="fas fa-box-open fa-3x mb-3 text-light"></i>
                                            <p>You haven't placed any orders yet.</p>
                                            <a href="shop.php" class="btn btn-gold mt-2">Start Shopping</a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>