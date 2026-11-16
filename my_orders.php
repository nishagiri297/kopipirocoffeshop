<?php
require_once 'includes/functions.php';
$user = require_role('customer');

$stmt = db()->prepare('SELECT o.*, c.name AS cashier_name FROM orders o LEFT JOIN users c ON c.id = o.cashier_id WHERE o.customer_id = ? ORDER BY o.created_at DESC, o.id DESC');
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();
$itemsByOrder = items_for_orders(array_column($orders, 'id'));
$saved = array_sum(array_column($orders, 'discount'));

$pageTitle = 'My orders';
require 'includes/header.php';
?>

<section class="container section-tight">
    <div class="page-head">
        <div>
            <h1 class="page-title">My orders</h1>
            <p class="muted">You've placed <?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?> and saved <strong class="text-green"><?= money($saved) ?></strong> with your membership.</p>
        </div>
        <a href="menu.php" class="btn btn-primary">New order</a>
    </div>

    <?php if (!$orders): ?>
        <div class="empty-state card">
            <h3>No orders yet</h3>
            <p>Your orders will appear here so you can track them.</p>
            <a href="menu.php" class="btn btn-primary">Browse the menu</a>
        </div>
    <?php endif; ?>

    <div class="order-list">
        <?php foreach ($orders as $o): $items = $itemsByOrder[$o['id']] ?? []; ?>
            <details class="card order-card" <?= in_array($o['status'], ['pending', 'preparing'], true) ? 'open' : '' ?>>
                <summary>
                    <div class="order-id"><strong><?= e($o['order_code']) ?></strong><small class="muted"><?= e(date('d M Y, H:i', strtotime($o['created_at']))) ?></small></div>
                    <span class="muted hide-sm"><?= count($items) ?> item<?= count($items) === 1 ? '' : 's' ?> · <?= e(ucfirst($o['order_type'])) ?></span>
                    <?= status_badge($o['status']) ?>
                    <strong><?= money($o['total']) ?></strong>
                </summary>
                <div class="order-body">
                    <?php include 'includes/order_items.php'; ?>
                    <p class="muted small">Handled by <?= e($o['cashier_name'] ?? '—') ?></p>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
