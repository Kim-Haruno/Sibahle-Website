<?php
$admin_nav_items = [
    'dashboard' => ['label' => 'Dashboard', 'href' => 'index.php', 'icon' => 'fa-gauge-high'],
    'orders' => ['label' => 'Orders', 'href' => 'orders.php', 'icon' => 'fa-box'],
    'products' => ['label' => 'Products', 'href' => 'products.php', 'icon' => 'fa-tags'],
    'customers' => ['label' => 'Customers', 'href' => 'customers.php', 'icon' => 'fa-users'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escape_html($page_title) ?></title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-page">
<div class="admin-layout">
    <aside class="admin-sidebar">
        <a href="index.php" class="admin-sidebar-brand">
            <span class="admin-brand-name">Sibahle</span>
            <span class="admin-brand-label">Admin</span>
        </a>
        <nav class="nav nav-pills flex-column gap-1" aria-label="Admin navigation">
            <?php foreach ($admin_nav_items as $item_key => $item): ?>
                <a class="nav-link <?= $active_page === $item_key ? 'active' : '' ?>"
                   href="<?= escape_html($item['href']) ?>"
                   <?= $active_page === $item_key ? 'aria-current="page"' : '' ?>>
                    <i class="fas <?= escape_html($item['icon']) ?> me-2" aria-hidden="true"></i>
                    <?= escape_html($item['label']) ?>
                </a>
            <?php endforeach; ?>
            <a class="nav-link admin-store-link" href="../index.php">
                <i class="fas fa-arrow-left me-2" aria-hidden="true"></i>Back to Store
            </a>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-page-header">
            <p class="text-gold text-uppercase small fw-semibold mb-1">Sibahle Store</p>
            <h1 class="h2 mb-0"><?= escape_html($page_heading) ?></h1>
        </header>
