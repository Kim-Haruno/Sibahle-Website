<?php
require_once __DIR__ . '/../includes/config.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// --- Handle Role Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $target_user_id = (int)$_POST['user_id'];
    $new_role = $_POST['role'];
    
    if ($target_user_id !== $_SESSION['user_id']) {
        $update_stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        $update_stmt->execute([$new_role, $target_user_id]);
    }
    header('Location: customers.php');
    exit;
}

// --- Handle User Deletion ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $target_user_id = (int)$_POST['user_id'];
    
    // Prevent admin from deleting themselves
    if ($target_user_id !== $_SESSION['user_id']) {
        try {
            $delete_stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $delete_stmt->execute([$target_user_id]);
        } catch (PDOException $e) {
            // If they have orders, the foreign key constraint will block the deletion
            $error_message = "Cannot delete user: They have existing orders in the system.";
        }
    }
    if (!isset($error_message)) {
        header('Location: customers.php');
        exit;
    }
}

$page_title = 'Manage Customers | ' . SITE_NAME;
$page_heading = 'Manage Customers';
$active_page = 'customers';

$users_stmt = $pdo->query(
    'SELECT user_id, first_name, last_name, email, role, created_at
     FROM users
     ORDER BY created_at DESC'
);
$users = $users_stmt->fetchAll();
?>
<?php include __DIR__ . '/includes/admin-header.php'; ?>

        <!-- Show Error Message if Delete Fails -->
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?= escape_html($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <section class="card admin-table-card">
            <div class="card-header">
                <h2 class="h5 mb-0">Customers</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3">User ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Role</th>
                            <th scope="col">Joined</th>
                            <th scope="col" class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users === []): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No customers found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="ps-3">#<?= (int) $user['user_id'] ?></td>
                                    <td><?= escape_html($user['first_name'] . ' ' . $user['last_name']) ?></td>
                                    <td><?= escape_html($user['email']) ?></td>
                                    <td>
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <span class="badge text-bg-danger">Admin</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-success">Customer</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= escape_html(date('d M Y', strtotime($user['created_at']))) ?></td>
                                    <td class="text-end pe-3">
                                        <!-- Role Management Buttons -->
                                        <?php if ($user['user_id'] !== $_SESSION['user_id']): ?>
                                            <div class="d-flex justify-content-end gap-2">
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                    <?php if ($user['role'] === 'admin'): ?>
                                                        <input type="hidden" name="role" value="customer">
                                                        <button type="submit" name="update_role" class="btn btn-sm btn-outline-warning">Remove Admin</button>
                                                    <?php else: ?>
                                                        <input type="hidden" name="role" value="admin">
                                                        <button type="submit" name="update_role" class="btn btn-sm btn-outline-success">Make Admin</button>
                                                    <?php endif; ?>
                                                </form>
                                                
                                                <!-- Delete Button -->
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete this user? This cannot be undone.');">
                                                    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                    <button type="submit" name="delete_user" class="btn btn-sm btn-outline-danger" title="Delete User">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">Current User</span>
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