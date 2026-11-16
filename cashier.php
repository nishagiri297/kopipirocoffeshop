<?php
require_once 'includes/functions.php';
$user = require_role('cashier');

// Update the status of one of MY orders
if (is_post()) {
    verify_csrf();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (in_array($status, ORDER_STATUSES, true)) {
        $stmt = db()->prepare('UPDATE orders SET status = ? WHERE id = ? AND cashier_id = ?');
        $stmt->execute([$status, $orderId, $user['id']]);
        flash($stmt->rowCount() ? 'success' : 'error', $stmt->rowCount() ? 'Order updated to "' . $status . '".' : 'You can only update orders assigned to you.');
    }
    redirect('cashier.php?' . http_build_query(['status' => $_GET['status'] ?? 'open']));
}

$filter = $_GET['status'] ?? 'open';
$where = 'o.cashier_id = ?';
$params = [$user['id']];
if ($filter === 'open') {
    $where .= " AND o.status IN ('pending','preparing')";
} elseif (in_array($filter, ORDER_STATUSES, true)) {
    $where .= ' AND o.status = ?';
    $params[] = $filter;
}

$stmt = db()->prepare("SELECT o.* FROM orders o WHERE $where ORDER BY CASE o.status WHEN 'pending' THEN 0 WHEN 'preparing' THEN 1 ELSE 2 END, o.created_at DESC LIMIT 200");
$stmt->execute($params);
$orders = $stmt->fetchAll();
$itemsByOrder = items_for_orders(array_column($orders, 'id'));

// Small stats for this cashier only
$today = date('Y-m-d 00:00:00');
$stmt = db()->prepare("SELECT
        SUM(CASE WHEN status IN ('pending','preparing') THEN 1 ELSE 0 END) AS open_orders,
        SUM(CASE WHEN status = 'completed' AND created_at >= ? THEN 1 ELSE 0 END) AS done_today,
        SUM(CASE WHEN status = 'completed' AND created_at >= ? THEN total ELSE 0 END) AS revenue_today,
        COUNT(*) AS all_orders
    FROM orders WHERE cashier_id = ?");
$stmt->execute([$today, $today, $user['id']]);
$stats = $stmt->fetch();

$next = ['pending' => ['preparing', 'Start preparing'], 'preparing' => ['completed', 'Mark completed']];

$pageTitle = 'Cashier orders';
$bodyClass = 'dashboard';
require 'includes/header.php';
?>

<section class="container section-tight">
    <div class="page-head">
        <div>
            <span class="eyebrow">Cashier workspace</span>
            <h1 class="page-title">Orders assigned to you</h1>
            <p class="muted">You only see orders that are assigned to <strong><?= e($user['name']) ?></strong>.</p>
        </div>
        <a href="cashier.php?status=<?= e($filter) ?>" class="btn btn-ghost">↻ Refresh</a>
    </div>

    <div class="kpi-grid">
        <div class="kpi"><small>Open orders</small><strong><?= (int)$stats['open_orders'] ?></strong></div>
        <div class="kpi"><small>Completed today</small><strong><?= (int)$stats['done_today'] ?></strong></div>
        <div class="kpi"><small>Collected today</small><strong><?= money($stats['revenue_today'] ?? 0) ?></strong></div>
        <div class="kpi"><small>All-time orders</small><strong><?= (int)$stats['all_orders'] ?></strong></div>
    </div>

    <div class="pill-tabs">
        <?php foreach (['open' => 'Open', 'pending' => 'Pending', 'preparing' => 'Preparing', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'all' => 'All'] as $key => $label): ?>
            <a href="cashier.php?status=<?= $key ?>" class="<?= $filter === $key ? 'active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (!$orders): ?>
        <div class="empty-state card"><h3>No orders here</h3><p>New orders assigned to you will show up in “Open”.</p></div>
    <?php endif; ?>

    <div class="ticket-grid">
        <?php foreach ($orders as $o): $items = $itemsByOrder[$o['id']] ?? []; ?>
            <article class="card ticket status-<?= e($o['status']) ?>">
                <header class="ticket-head">
                    <div>
                        <strong><?= e($o['order_code']) ?></strong>
                        <small class="muted"><?= e(date('d M, H:i', strtotime($o['created_at']))) ?></small>
                    </div>
                    <?= status_badge($o['status']) ?>
                </header>
                <div class="ticket-customer">
                    <span><?= e($o['customer_name']) ?> <?= $o['customer_id'] ? '<span class="role-tag role-customer">member</span>' : '<span class="role-tag">guest</span>' ?></span>
                    <span class="muted"><?= e(ucfirst($o['order_type'])) ?><?= $o['table_no'] ? ' · Table ' . e($o['table_no']) : '' ?></span>
                </div>
                <?php include 'includes/order_items.php'; ?>
                <?php if (isset($next[$o['status']])): ?>
                    <div class="ticket-actions">
                        <form method="post"><?= csrf_field() ?>
                            <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                            <input type="hidden" name="status" value="<?= $next[$o['status']][0] ?>">
                            <button class="btn btn-primary btn-sm" type="submit"><?= $next[$o['status']][1] ?></button>
                        </form>
                        <form method="post" data-confirm="Cancel order <?= e($o['order_code']) ?>?"><?= csrf_field() ?>
                            <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                            <input type="hidden" name="status" value="cancelled">
                            <button class="btn btn-text btn-sm" type="submit">Cancel</button>
                        </form>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
